<?php
header('Content-Type: application/json');
require_once "../config/db.php";

$response = [];

if (isset($pdo) && $pdo !== null) {
    try {
        // Simple query to get all logistics for dropdown
        $sql = "SELECT logistic_id, logistic_name FROM logistics ORDER BY logistic_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $logistics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = $logistics;
        
    } catch (Exception $e) {
        $response = ["error" => "DB Query Error: " . $e->getMessage()];
    }
} else {
    $response = ["error" => "Database connection not available."];
}

echo json_encode($response);