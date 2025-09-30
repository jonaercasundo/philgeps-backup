<?php
require_once "../config/db.php"; // adjust the path as needed

$response = [
    "warehouse_count" => "DB ERROR",
    "logistics_count" => "DB ERROR"
];

if (isset($pdo) && $pdo !== null) {
    try {
        // 1. Total Deliveries In Warehouse
        $stmt_warehouse = $pdo->prepare("
            SELECT COUNT(d.delivery_id)
            FROM deliveries d
            JOIN projects p ON d.project_id = p.project_id
            JOIN school s ON d.school_id = s.school_id
            WHERE d.status = 'warehouse'
        ");
        $stmt_warehouse->execute();
        $response["warehouse_count"] = number_format($stmt_warehouse->fetchColumn());

        // 2. Total Deliveries Sent to Logistics
        $stmt_logistics = $pdo->prepare("
            SELECT COUNT(d.delivery_id)
            FROM deliveries d
            JOIN projects p ON d.project_id = p.project_id
            JOIN school s ON d.school_id = s.school_id
            WHERE d.status = 'accepted'
        ");
        $stmt_logistics->execute();
        $response["logistics_count"] = number_format($stmt_logistics->fetchColumn());

    } catch (Exception $e) {
        $response["error"] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
