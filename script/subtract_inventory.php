<?php
session_start();
header('Content-Type: application/json');
require "../config/db.php";
try {
    // Check if we have the required data
    if (empty($_POST['items_json']) || empty($_POST['password'])) {
        echo json_encode(["success" => false, "message" => "Missing items data or password", "toast" => "Missing credentials", "type" => "danger"]);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? null;
    $password = $_POST['password'];
    $items = json_decode($_POST['items_json'], true);

    // Validate session
    if (!$user_id || !$username) {
        echo json_encode(["success" => false, "message" => "User session not found", "toast" => "Session expired", "type" => "danger"]);
        exit;
    }

    // Verify password first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid password!", "toast" => "Invalid password!", "type" => "danger"]);
        exit;
    }

    // Password verified, now process items
    if (!is_array($items) || empty($items)) {
        echo json_encode(["success" => false, "message" => "Invalid items data", "toast" => "Invalid items data", "type" => "danger"]);
        exit;
    }

    $successful_subtractions = 0;
    $errors = [];
    $processed_items = [];

    // Determine warehouse_id from session or default to user's assigned warehouse
    $warehouse_id = $_SESSION['warehouse_id'] ?? $user['warehouse_id'] ?? 1;

    // Validate warehouse_id
    if (!$warehouse_id) {
        echo json_encode(["success" => false, "message" => "No warehouse assigned to user", "toast" => "No warehouse assigned", "type" => "danger"]);
        exit;
    }

    // Validate warehouse exists
    $warehouse_check = $pdo->prepare("SELECT warehouse_id FROM warehouse WHERE warehouse_id = ?");
    $warehouse_check->execute([$warehouse_id]);
    if (!$warehouse_check->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["success" => false, "message" => "Invalid warehouse ID: $warehouse_id", "toast" => "Invalid warehouse", "type" => "danger"]);
        exit;
    }

    foreach ($items as $item) {
        if (empty($item['item_id']) || empty($item['quantity'])) {
            $errors[] = "Missing item_id or quantity";
            continue;
        }

        $item_id = intval($item['item_id']);
        $quantity_to_subtract = intval($item['quantity']);

        if ($quantity_to_subtract <= 0) {
            $errors[] = "Invalid quantity for item $item_id";
            continue;
        }

        // Get item name for activity log
        $item_stmt = $pdo->prepare("SELECT item_name FROM item WHERE item_id = ?");
        $item_stmt->execute([$item_id]);
        $item_data = $item_stmt->fetch(PDO::FETCH_ASSOC);
        $item_name = $item_data ? $item_data['item_name'] : "Item ID: $item_id";

        // Get available approved inventory for this item in current warehouse
        $stmt_inventory = $pdo->prepare("
            SELECT inventory_id, qty
            FROM inventory
            WHERE item_id = :item_id
            AND warehouse_id = :warehouse_id
            AND inventory_status = 'Approved'
            ORDER BY inventory_id
        ");
        $stmt_inventory->execute([
            ':item_id' => $item_id,
            ':warehouse_id' => $warehouse_id
        ]);
        $inventory_records = $stmt_inventory->fetchAll(PDO::FETCH_ASSOC);

        if (empty($inventory_records)) {
            $errors[] = "Item '$item_name' not found in approved inventory for warehouse ID: $warehouse_id";
            continue;
        }

        // Calculate total available quantity
        $total_available = array_sum(array_column($inventory_records, 'qty'));
        if ($total_available < $quantity_to_subtract) {
            $errors[] = "Insufficient quantity for item '$item_name'. Requested: $quantity_to_subtract, Available: $total_available in warehouse ID: $warehouse_id";
            continue;
        }

        $remaining_to_subtract = $quantity_to_subtract;
        $total_subtracted = 0;

        // Subtract from inventory records until we've deducted the full quantity
        foreach ($inventory_records as $inv_record) {
            if ($remaining_to_subtract <= 0) break;

            $available_qty = $inv_record['qty'];
            $inventory_id = $inv_record['inventory_id'];
            $subtracted_from_this_record = 0;

            if ($available_qty >= $remaining_to_subtract) {
                // This record has enough quantity to cover the remainder
                $new_qty = $available_qty - $remaining_to_subtract;
                $subtracted_from_this_record = $remaining_to_subtract;
                $stmt_update = $pdo->prepare("UPDATE inventory SET qty = :new_qty WHERE inventory_id = :inventory_id");
                $stmt_update->execute([
                    ':new_qty' => $new_qty,
                    ':inventory_id' => $inventory_id
                ]);
                $remaining_to_subtract = 0;
                
                // If the new quantity is 0, we can delete the record
                if ($new_qty == 0) {
                    $delete_stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id = :inventory_id");
                    $delete_stmt->execute([':inventory_id' => $inventory_id]);
                }
            } else {
                // This record doesn't have enough, take all of it and move to next record
                $subtracted_from_this_record = $available_qty;

                $stmt_update = $pdo->prepare("UPDATE inventory SET qty = 0 WHERE inventory_id = :inventory_id");
                $stmt_update->execute([':inventory_id' => $inventory_id]);
                $remaining_to_subtract -= $available_qty;
                
                // Delete the record since it's now 0
                $delete_stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id = :inventory_id");
                $delete_stmt->execute([':inventory_id' => $inventory_id]);
            }
            $total_subtracted += $subtracted_from_this_record;

            $stmt_history = $pdo->prepare("
                INSERT INTO inventory_history
                (inventory_id, item_id, warehouse_id, old_qty, new_qty, changed_by, change_type, remarks)
                VALUES
                (:inventory_id, :item_id, :warehouse_id, :old_qty, :new_qty, :changed_by, 'update', :remarks)
            ");
            $stmt_history->execute([
                ':inventory_id' => $inventory_id,
                ':item_id' => $item_id,
                ':warehouse_id' => $warehouse_id,
                ':old_qty' => $available_qty,
                ':new_qty' => $available_qty - $subtracted_from_this_record,
                ':changed_by' => $username,
                ':remarks' => "{$subtracted_from_this_record} pulled out"
            ]);
        }

        if ($total_subtracted > 0) {
            $successful_subtractions++;
            $processed_items[] = "$item_name (-$total_subtracted)";
        }
    }

    // Build success message based on what was processed
    if ($successful_subtractions > 0) {
        $msg = "Successfully subtracted $successful_subtractions item(s) from inventory";
        if (!empty($errors)) {
            $msg .= ". Some errors occurred.";
        }
        $action_message = $username . " subtracted " . count($processed_items) . " items from inventory";

        $details = "Subtracted Items:\n";
        foreach ($processed_items as $item) {
            $details .= "- " . $item . "\n";
        }

        // Add errors to details if any
        if (!empty($errors)) {
            $details .= "\nErrors:\n• " . implode("\n• ", array_slice($errors, 0, 3));
        }

        // Insert into activity logs
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->execute([$user_id, $action_message, $details]);

        echo json_encode(["success" => true, "message" => $msg, "toast" => $msg, "type" => "success"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to subtract items: " . implode(', ', $errors), "toast" => "Failed to subtract items", "type" => "danger"]);
    }

} catch (PDOException $e) {
    error_log("Subtract inventory error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "SQL Error: " . $e->getMessage(),
        "toast" => "Database error occurred",
        "type" => "danger"
    ]);
} catch (Exception $e) {
    error_log("Subtract inventory error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "toast" => "An error occurred",
        "type" => "danger"
    ]);
}