<?php
session_start();
include "../config/db.php";
$raw = $_POST['school_ids']; // e.g. "134967 134969 134970 ..."
$project_id = $_POST['project_id']; // from your form

// Split by spaces or newlines
$ids = preg_split('/\s+/', trim($raw)); 

// Remove duplicates & empty values
$ids = array_filter(array_unique($ids));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO schools_project (school_id, project_id) VALUES (?, ?)");

    foreach ($ids as $id) {
        $stmt->execute([$id, $project_id]);
    }

    $stmt = $pdo->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $projectName = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO activity_logs 
        (user_id, action) 
        VALUES (?,?)");
    $stmt->execute([
    $_SESSION['user_id'],
    $_SESSION['name']." Imported ".count($ids)." schools to ". $projectName
    ]);
        
    $pdo->commit();
    header("Location: ../schools.php?id=$project_id&toast=Inserted ".count($ids)." schools successfully&type=success");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
     header("Location: ../schools.php?id=$project_id&toast=Error saving schools: " . urlencode($e->getMessage()) . "&type=danger");
    exit;
}
