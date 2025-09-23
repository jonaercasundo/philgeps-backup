<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    $stmt = $pdo->prepare("UPDATE `lot` 
        SET `lot_name` = ?, 
            `project_id` = ?, 
            `contract_no` = ?
        WHERE `lot_id` = ?");

    $stmt->execute([
        $_POST['lot_name'],
        $_POST['project_id'],
        $_POST['contract_no'],
        $_POST['lot_id'] // WHERE condition
    ]);

    echo json_encode(["success" => true]);
    header("Location: ../lots.php?id=" . $_POST['project_id']);
    exit;
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

