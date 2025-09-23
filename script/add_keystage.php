<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust

try {
    $stmt = $pdo->prepare("INSERT INTO `keystage`( `keystage_num`, `lot_id`, `description`) 
    VALUES (?,?,?)");
    $stmt->execute([
        $_POST['keystage_no'],
        $_POST['lot'],
        $_POST['description']
    ]);
    $project_id = $pdo->lastInsertId();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}