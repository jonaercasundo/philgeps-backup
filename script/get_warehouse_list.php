<?php
header('Content-Type: application/json');
require_once "../config/db.php";

$response = [];

if (isset($pdo) && $pdo !== null) {
    try {
        // Simple query to get all warehouses for dropdown
        $sql = "SELECT warehouse_id, warehouse_name FROM warehouse ORDER BY warehouse_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = $warehouses;
        
    } catch (Exception $e) {
        $response = ["error" => "DB Query Error: " . $e->getMessage()];
    }
} else {
    $response = ["error" => "Database connection not available."];
}

echo json_encode($response);
