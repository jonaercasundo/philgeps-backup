<?php
require "../config/db.php"; // your PDO connection

$projectid = $_GET['projectid'] ?? '';

$response = [
  "lots" => []
];

// Check if projectid is provided and not empty
if (!empty($projectid)) {
    // Use prepared statement to prevent SQL injection
    $stmt = $pdo->prepare("
        SELECT 
            l.lot_id as id, 
            l.lot_name as name, 
            p.agency 
        FROM lot l 
        JOIN projects p ON l.project_id = p.project_id 
        WHERE l.project_id = ?
    ");
    $stmt->execute([$projectid]);
    $response["lots"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

header("Content-Type: application/json");
echo json_encode($response);
?>