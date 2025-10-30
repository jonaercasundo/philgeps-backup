<?php
session_start();
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['edit_inventory_id']) || empty($_POST['edit_quantity'])) {
        echo json_encode(["success" => false, "message" => "Inventory ID and quantity are required"]);
        exit;
    }

    $inventory_id = $_POST['edit_inventory_id'];
    $quantity = $_POST['edit_quantity'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';

    // Validate quantity is a positive number
    if (!is_numeric($quantity) || $quantity < 0) {
        echo json_encode(["success" => false, "message" => "Quantity must be a positive number"]);
        exit;
    }

    // First, get the current item details for the activity log
    $stmt = $pdo->prepare("SELECT i.item_id, it.item_name, w.warehouse_name, i.qty as old_quantity 
                            FROM inventory i 
                            JOIN item it ON i.item_id = it.item_id 
                            JOIN warehouse w ON i.warehouse_id = w.warehouse_id 
                            WHERE i.inventory_id = ?");
    $stmt->execute([$inventory_id]);
    $inventory_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory_data) {
        echo json_encode(["success" => false, "message" => "Inventory item not found"]);
        exit;
    }

    // Prepare and execute update query
    $stmt = $pdo->prepare("UPDATE `inventory` SET `qty` = ? WHERE `inventory_id` = ?");
    $stmt->execute([
        $quantity,
        $inventory_id
    ]);

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        // Prepare activity log message
        $action_message = $_SESSION['name'] . " updated inventory: " . 
                            $inventory_data['item_name'] . " in " . 
                            $inventory_data['warehouse_name'] . 
                            " (Quantity: " . $inventory_data['old_quantity'] . " → " . $quantity . ")";
        
        // Add remarks to the details field if provided
        $details = null;
        if (!empty($remarks)) {
            $details = $remarks;
        }

        // Insert into activity logs
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $action_message,
            $details
        ]);

        echo json_encode(["success" => true, "message" => "Inventory updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No changes made or inventory not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
