<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    $stmt = $pdo->prepare("INSERT INTO `inventory`(`inventory_id`, `warehouse_id`, `item_id`, `qty`) 
    VALUES (?,?,?,?)");
    $stmt->execute([
        $_POST['inventory_id'],
        $_POST['warehouse_id'],
        $_POST['item_id'],
        $_POST['quantity']
    ]);
    $inventory_id = $pdo->lastInsertId();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}