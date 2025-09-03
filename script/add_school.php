<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust

try {
    $stmt = $pdo->prepare("INSERT INTO `school`(`school_id`, `school_name`, `project_id`, `address`, `contact_person`, `contact`, `municipality`, `division`, `region`) 
    VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['school_id'],
        $_POST['school_name'],
        $_POST['project_id'],
        $_POST['address'],
        $_POST['person'],
        $_POST['contact'],
        $_POST['municipality'],
        $_POST['division'],
        $_POST['region']
    ]);
    $project_id = $pdo->lastInsertId();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}