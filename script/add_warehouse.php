<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust

try {
    $stmt = $pdo->prepare("INSERT INTO `warehouse`(`warehouse_name`, `warehouse_address`, `contact_info`) 
    VALUES (?,?,?)");
    $stmt->execute([
        $_POST['warehouse_name'],
        $_POST['warehouse_address'],
        $_POST['contact_info']
    ]);
    $warehouse_id = $pdo->lastInsertId();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}