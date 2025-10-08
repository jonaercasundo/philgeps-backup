<?php
session_start();
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

    $stmt = $pdo->prepare("
    SELECT p.project_name, l.lot_name FROM lot l 
    JOIN projects p ON l.project_id = p.project_id 
    WHERE lot_id = ?");
    $stmt->execute([$_POST['lot']]);
    $projectName = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("INSERT INTO activity_logs 
        (user_id, action) 
        VALUES (?,?)");
    $stmt->execute([
    $_SESSION['user_id'],
    $_SESSION['name']." Added Keystage ".$_POST['keystage_no']." ". $_POST['description']." to Lot ".$projectName['lot_name']." on ". $projectName['project_name']
    ]);
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}