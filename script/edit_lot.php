<?php
session_start();
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

    $stmt = $pdo->prepare("
    SELECT p.project_name FROM lot l
    JOIN projects p on p.project_id = l.project_id
    WHERE l.project_id = ?");
    $stmt->execute([$_POST['project_id']]);
    $projectName = $stmt->fetchColumn();

     // Log the activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['name'] . " Edited LOT ".$_POST['lot_name']." on project $projectName"
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

