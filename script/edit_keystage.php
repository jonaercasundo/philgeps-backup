<?php
session_start();
header('Content-Type: application/json');
require "../config/db.php";

try {
    $stmt = $pdo->prepare("UPDATE `keystage` 
        SET  `lot_id` = ?, 
            `keystage_num` = ?, 
             `description` = ?
        WHERE `keystage_id` = ?");

    $stmt->execute([
        $_POST['lotID'],
        $_POST['keystageNum'],
        $_POST['description'],
        $_POST['keystage_id'] // WHERE condition
    ]);

    $stmt = $pdo->prepare("
    SELECT project_name FROM projects
    WHERE project_id = ?");
    $stmt->execute([$_POST['project_id']]);
    $projectName = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
    SELECT lot_name FROM lot l
    WHERE lot_id = ?");
    $stmt->execute([ $_POST['lotID']]);
    $lotNum = $stmt->fetchColumn();

     // Log the activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['name'] . " Edited Lot $lotNum Keystage ".$_POST['keystageNum']." ".$_POST['description']." on project $projectName"
        ]);

    echo json_encode(["success" => true]);
    header("Location: ../keystage.php?id=" . $_POST['project_id']);
    exit;
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}