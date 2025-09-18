<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust

try {
    $stmt = $pdo->prepare("INSERT INTO `lot`( `lot_name`, `project_id`, `contract_no`) 
    VALUES (?,?,?)");
    $stmt->execute([
        $_POST['lot_no'],
        $_POST['project_id'],
        $_POST['contract_no']
    ]);
    $project_id = $pdo->lastInsertId();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}