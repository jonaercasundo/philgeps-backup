<?php
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

    // Validate quantity is a positive number
    if (!is_numeric($quantity) || $quantity < 0) {
        echo json_encode(["success" => false, "message" => "Quantity must be a positive number"]);
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
        echo json_encode(["success" => true, "message" => "Inventory updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No changes made or inventory not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
