<?php
require_once "../config/db.php";

$response = [
    "accepted_by_logistics" => [],
];

if (isset($pdo) && $pdo !== null) {
    try {
        // GRAPH: Count deliveries with "accepted" status grouped by logistic_name
        $stmt_accepted_by_logistics = $pdo->prepare("
            SELECT 
                l.logistic_name,
                COUNT(d.delivery_id) as delivery_count
            FROM deliveries d
            JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
            JOIN logistics l ON ll.logistics_id = l.logistic_id
            WHERE d.status = 'accepted'
            GROUP BY l.logistic_name
            ORDER BY delivery_count DESC
        ");
        $stmt_accepted_by_logistics->execute();
        $accepted_data = $stmt_accepted_by_logistics->fetchAll(PDO::FETCH_ASSOC);

        // Format for Graph - FIXED PROPERTY NAME
        $response["accepted_by_logistics"] = [
            "logistics_names" => array_column($accepted_data, 'logistic_name'),  // Changed to logistics_names
            "delivery_counts" => array_column($accepted_data, 'delivery_count')
        ];

    } catch (Exception $e) {
        $response["error"] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);