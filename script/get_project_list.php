<?php
require_once "../config/db.php";

header('Content-Type: application/json');

$response = [];

if (isset($pdo) && $pdo !== null) {
    try {
        $sql = "SELECT project_id, project_name FROM projects ORDER BY project_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = $projects;
        
    } catch (Exception $e) {
        $response = ["error" => "DB Query Error: " . $e->getMessage()];
    }
} else {
    $response = ["error" => "Database connection not available."];
}

echo json_encode($response);