<?php
require '../config/db.php';

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id > 0) {
    $stmt = $pdo->prepare("
        SELECT school_id, school_name, address 
        FROM schools_project
        JOIN school USING(school_id) 
        WHERE project_id = :project_id 
        ORDER BY school_name ASC
    ");
    $stmt->execute([':project_id' => $project_id]);
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($schools);
} else {
    echo json_encode([]);
}
