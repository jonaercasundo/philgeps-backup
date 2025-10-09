<?php
session_start();
header('Content-Type: application/json');
require "../config/db.php";

try {
    $stmt = $pdo->prepare("UPDATE `item` 
        SET `item_name` = ?, 
             `unit` = ?
        WHERE `item_id` = ?");

    $stmt->execute([
        $_POST['itemName'],
        $_POST['unit'],
        $_POST['item_id']
    ]);

    $stmt = $pdo->prepare("
    SELECT p.project_name, p.project_id FROM item i
    JOIN projects p ON i.project_id = p.project_id
    WHERE i.item_id = ?");
    $stmt->execute([$_POST['item_id']]);
    $projectName = $stmt->fetch(PDO::FETCH_ASSOC);


     // Log the activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['name'] . " Edited Item ".$_POST['itemName']."on project ".$projectName['project_name']
        ]);

    echo json_encode(["success" => true]);
    header("Location: ../items.php?id=" . $projectName['project_id']."&toast=Edited ".$_POST['itemName']."&type=success");
    exit;
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}