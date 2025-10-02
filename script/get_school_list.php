<?php
require_once "../config/db.php";

header('Content-Type: application/json');

$response = [];

if (isset($pdo) && $pdo !== null) {
    try {
        $sql = "SELECT school_id, school_name FROM school ORDER BY school_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = $schools;
        
    } catch (Exception $e) {
        $response = ["error" => "DB Query Error: " . $e->getMessage()];
    }
} else {
    $response = ["error" => "Database connection not available."];
}

echo json_encode($response);