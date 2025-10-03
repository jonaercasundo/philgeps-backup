<?php
header('Content-Type: application/json');
require_once "../config/db.php";

$response = [];

if (isset($pdo) && $pdo !== null) {
    try {
        // Simple query to get all items for dropdown
        $sql = "SELECT item_id, item_name FROM item ORDER BY item_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = $items;
        
    } catch (Exception $e) {
        $response = ["error" => "DB Query Error: " . $e->getMessage()];
    }
} else {
    $response = ["error" => "Database connection not available."];
}

echo json_encode($response);
