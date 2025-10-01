<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['inventory_id']) || empty($_POST['warehouse_id']) || empty($_POST['item_id']) || !isset($_POST['quantity'])) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    $inventory_id = $_POST['inventory_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    // Validate quantity is a positive number
    if (!is_numeric($quantity) || $quantity < 0) {
        echo json_encode(["success" => false, "message" => "Quantity must be a positive number"]);
        exit;
    }

    // Check if inventory_id already exists
    $checkIdStmt = $pdo->prepare("SELECT inventory_id FROM inventory WHERE inventory_id = ?");
    $checkIdStmt->execute([$inventory_id]);
    
    if ($checkIdStmt->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Inventory ID already exists"]);
        exit;
    }

    // Check if inventory record already exists for this item and warehouse
    $checkInventoryStmt = $pdo->prepare("SELECT inventory_id, qty FROM inventory WHERE item_id = ? AND warehouse_id = ?");
    $checkInventoryStmt->execute([$item_id, $warehouse_id]);
    $existingInventory = $checkInventoryStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingInventory) {
        // Update existing inventory - increase quantity
        $newQuantity = $existingInventory['qty'] + $quantity;
        $updateStmt = $pdo->prepare("UPDATE inventory SET qty = ? WHERE item_id = ? AND warehouse_id = ?");
        $updateStmt->execute([$newQuantity, $item_id, $warehouse_id]);
        
        $message = "Inventory quantity updated successfully. New total: " . $newQuantity;
    } else {
        // Insert new inventory record
        $insertStmt = $pdo->prepare("INSERT INTO `inventory`(`inventory_id`, `warehouse_id`, `item_id`, `qty`) VALUES (?,?,?,?)");
        $insertStmt->execute([$inventory_id, $warehouse_id, $item_id, $quantity]);
        
        $message = "New inventory record created successfully";
    }

    echo json_encode(["success" => true, "message" => $message]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
