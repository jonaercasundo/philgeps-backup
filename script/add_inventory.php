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

    $merged_count = 0;
    $new_count = 0;
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

        // Check if there's an existing "For Approval" record
        $check = $pdo->prepare("SELECT inventory_id, qty FROM inventory WHERE item_id = ? AND warehouse_id = ? AND inventory_status = 'For Approval'");
        $check->execute([$item_id, $warehouse_id]);
        $existing = $check->fetch();

        if ($existing) {
            // Add quantity to existing "For Approval" record
            $new_qty = $existing['qty'] + $quantity;
            $update = $pdo->prepare("UPDATE inventory SET qty = ? WHERE inventory_id = ?");
            if ($update->execute([$new_qty, $existing['inventory_id']])) {
                $merged_count++;
                $processed_items[] = "$item_name (Merged: +$quantity)";
            } else {
                $errors[] = "Update failed for pending item $item_id";
            }
        } else {
            // No existing "For Approval" record - create new one
            $max_id = $pdo->query("SELECT COALESCE(MAX(inventory_id), 0) + 1 as next_id FROM inventory")->fetch()['next_id'];
            
            $insert = $pdo->prepare("INSERT INTO inventory (inventory_id, warehouse_id, item_id, qty, inventory_status) VALUES (?, ?, ?, ?, 'For Approval')");
            if ($insert->execute([$max_id, $warehouse_id, $item_id, $quantity])) {
                $new_count++;
                $processed_items[] = "$item_name (New: $quantity)";
            } else {
                $errors[] = "Insert failed for item $item_id";
            }
        }
    }

    // Build success message based on what was processed
    $message_parts = [];
    if ($merged_count > 0) {
        $message_parts[] = "Merged $merged_count items with existing pending requests";
    }
    if ($new_count > 0) {
        $message_parts[] = "Created $new_count new pending requests";
    }

    if (!empty($message_parts)) {
        $msg = implode(", ", $message_parts);
        if (!empty($errors)) {
            $msg .= ". Some errors occurred.";
        }
        $action_message = $username . " added " . count($processed_items) . " items for approval";

        $details = "Item details:\n";
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
        echo json_encode(["success" => false, "message" => "Failed to add items: " . implode(', ', $errors), "toast" => "Failed to add items", "type" => "danger"]);
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
