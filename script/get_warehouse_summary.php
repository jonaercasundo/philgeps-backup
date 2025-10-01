<?php
require_once "../config/db.php"; // adjust the path as needed

$response = [
    "warehouse_count" => "DB ERROR",
    "logistics_count" => "DB ERROR",
    "accepted_by_warehouse" => [],
    "inventory_by_item" => [],
];

if (isset($pdo) && $pdo !== null) {
    try {
        // 1. ❌NOT USED Total Deliveries In Warehouse
        $stmt_warehouse = $pdo->prepare("
            SELECT COUNT(d.delivery_id)
            FROM deliveries d
            JOIN projects p ON d.project_id = p.project_id
            JOIN school s ON d.school_id = s.school_id
            WHERE d.status = 'warehouse'
        ");
        $stmt_warehouse->execute();
        $response["warehouse_count"] = number_format($stmt_warehouse->fetchColumn());

        // 2. ❌NOT USED Total Deliveries Sent to Logistics
        $stmt_logistics = $pdo->prepare("
            SELECT COUNT(d.delivery_id)
            FROM deliveries d
            JOIN projects p ON d.project_id = p.project_id
            JOIN school s ON d.school_id = s.school_id
            WHERE d.status = 'accepted'
        ");
        $stmt_logistics->execute();
        $response["logistics_count"] = number_format($stmt_logistics->fetchColumn());

        // 3. GRAPH 1: Count deliveries with "accepted" status grouped by warehouse_id
        $stmt_accepted_by_warehouse = $pdo->prepare("
            SELECT 
                ll.warehouse_id,
                COUNT(d.delivery_id) as delivery_count
            FROM deliveries d
            JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
            WHERE d.status = 'accepted'
            GROUP BY ll.warehouse_id
            ORDER BY delivery_count DESC
        ");
        $stmt_accepted_by_warehouse->execute();
        $accepted_data = $stmt_accepted_by_warehouse->fetchAll(PDO::FETCH_ASSOC);

        // Format for Graph 1 - we only have warehouse_id now, not warehouse_name
        $response["accepted_by_warehouse"] = [
            "warehouse_ids" => array_column($accepted_data, 'warehouse_id'),
            "delivery_counts" => array_column($accepted_data, 'delivery_count')
        ];

        // 4. NEW GRAPH: Total quantity per item in inventory
        $stmt_inventory_by_item = $pdo->prepare("
            SELECT 
                it.item_name,
                SUM(i.qty) as total_quantity
            FROM inventory i
            JOIN item it ON i.item_id = it.item_id
            GROUP BY it.item_name
            ORDER BY total_quantity DESC
        ");
        $stmt_inventory_by_item->execute();
        $inventory_data = $stmt_inventory_by_item->fetchAll(PDO::FETCH_ASSOC);

        // Format for Graph 4 - total quantity per item
        $response["inventory_by_item"] = [
            "item_names" => array_column($inventory_data, 'item_name'),
            "total_quantities" => array_column($inventory_data, 'total_quantity')
        ];
        
    } catch (Exception $e) {
        $response["error"] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
