<?php
session_start();
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

    $stmt = $pdo->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt->execute([$_POST['project_id']]);
    $projectName = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO activity_logs 
        (user_id, action) 
        VALUES (?,?)");
    $stmt->execute([
    $_SESSION['user_id'],
    $_SESSION['name']." Added Lot ".$_POST['lot_no']." to ". $projectName
    ]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}