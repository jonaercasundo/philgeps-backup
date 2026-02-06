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

    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $password = $_POST['password'];
    $items = json_decode($_POST['items_json'], true);

    // Verify password first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
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

    foreach ($items as $item) {
        if (empty($item['item_id']) || empty($item['quantity'])) {
            $errors[] = "Missing item_id or quantity";
            continue;
        }

        $warehouse_id = $_SESSION['warehouse_id'] ?? 1;
        $item_id = intval($item['item_id']);
        $quantity = intval($item['quantity']);

        if ($quantity <= 0) {
            $errors[] = "Invalid quantity for item $item_id";
            continue;
        }

        // Get item name for activity log
        $item_stmt = $pdo->prepare("SELECT item_name FROM item WHERE item_id = ?");
        $item_stmt->execute([$item_id]);
        $item_data = $item_stmt->fetch(PDO::FETCH_ASSOC);
        $item_name = $item_data ? $item_data['item_name'] : "Item ID: $item_id";

        // Check current inventory level for this item
        $current_inv_stmt = $pdo->prepare("SELECT inventory_id, qty FROM inventory WHERE item_id = ? AND warehouse_id = ? AND inventory_status = 'Approved'");
        $current_inv_stmt->execute([$item_id, $warehouse_id]);
        $current_inventory = $current_inv_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_inventory) {
            $errors[] = "Item $item_name not found in approved inventory";
            continue;
        }

        $current_qty = $current_inventory['qty'];
        if ($current_qty < $quantity) {
            $errors[] = "Insufficient quantity for item $item_name. Requested: $quantity, Available: $current_qty";
            continue;
        }

        // Perform the subtraction directly
        $new_qty = $current_qty - $quantity;
        
        if ($new_qty == 0) {
            // If the new quantity is 0, we can delete the record
            $delete_stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id = ?");
            if ($delete_stmt->execute([$current_inventory['inventory_id']])) {
                $successful_subtractions++;
                $processed_items[] = "$item_name (-$quantity)";
                
                // Log inventory history
                $history_stmt = $pdo->prepare("INSERT INTO inventory_history (inventory_id, item_id, warehouse_id, old_qty, new_qty, changed_by, change_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $history_stmt->execute([$current_inventory['inventory_id'], $item_id, $warehouse_id, $current_qty, 0, $username, 'update']);
            } else {
                $errors[] = "Failed to delete inventory record for item $item_id";
            }
        } else {
            // Update the existing record with the new quantity
            $update_stmt = $pdo->prepare("UPDATE inventory SET qty = ? WHERE inventory_id = ?");
            if ($update_stmt->execute([$new_qty, $current_inventory['inventory_id']])) {
                $successful_subtractions++;
                $processed_items[] = "$item_name (-$quantity)";
                
                // Log inventory history
                $history_stmt = $pdo->prepare("INSERT INTO inventory_history (inventory_id, item_id, warehouse_id, old_qty, new_qty, changed_by, change_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $history_stmt->execute([$current_inventory['inventory_id'], $item_id, $warehouse_id, $current_qty, $new_qty, $username, 'update']);
            } else {
                $errors[] = "Failed to update inventory for item $item_id";
            }
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
    echo json_encode([
    "success" => false,
    "message" => "SQL Error: " . $e->getMessage(),
    "toast" => $e->getMessage(),
    "type" => "danger"
]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "toast" => "An error occurred",
        "type" => "danger"
    ]);
}