<?php
require "../config/db.php";

try {
    // ==============================
    // 1️⃣ DELIVERY STATUS OVERVIEW
    // ==============================
    $stmt = $pdo->query("
        SELECT 
            CASE d.status
                WHEN 'pending'   THEN 'Pending'
                WHEN 'accepted'  THEN 'Accepted'
                WHEN 'delivered' THEN 'Delivered'
                WHEN 'cancelled' THEN 'Cancelled'
                ELSE d.status
            END AS status,
            COUNT(*) AS total
        FROM deliveries d
        GROUP BY status
        ORDER BY total DESC
    ");
    $deliveryStatusOverview = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==============================
    // 2️⃣ MONTHLY DELIVERY TREND
    // ==============================
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(d.delivered_date, '%Y-%m') AS month,
            CASE d.status
                WHEN 'warehouse' THEN 'Warehouse'
                WHEN 'accepted'  THEN 'Logistics'
                WHEN 'delivered' THEN 'Schools'
                ELSE d.status
            END AS status,
            COUNT(*) AS total
        FROM deliveries d
        WHERE d.delivered_date IS NOT NULL
        GROUP BY month, status
        ORDER BY month, status
    ");
    $monthlyDeliveryTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==============================
    // 3️⃣ INVENTORY DATA (ALL ITEMS)
    // ==============================
    $stmt = $pdo->query("
        SELECT 
            i.item_name,
            SUM(inv.qty) AS total_qty
        FROM inventory inv
        JOIN item i ON inv.item_id = i.item_id
        WHERE inv.inventory_status = 'Approved'
        GROUP BY i.item_name
        HAVING total_qty > 0
        ORDER BY total_qty DESC
    ");
    $inventoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==============================
    // 4️⃣ INVENTORY BY WAREHOUSE
    // ==============================
    $stmt = $pdo->query("
        SELECT 
            w.warehouse_name,
            i.item_name,
            i.unit,
            SUM(inv.qty) AS qty
        FROM inventory inv
        JOIN warehouse w ON inv.warehouse_id = w.warehouse_id
        JOIN item i ON inv.item_id = i.item_id
        WHERE inv.inventory_status = 'Approved'
        GROUP BY w.warehouse_id, i.item_id
        HAVING qty > 0
        ORDER BY w.warehouse_name, i.item_name
    ");
    $inventoryByWarehouse = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==============================
    // 5️⃣ PROGRESS PER REGION
    // ==============================
    $stmt = $pdo->query("
        SELECT 
            s.region,
            COUNT(*) AS total,
            SUM(CASE WHEN d.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN d.status = 'accepted' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) AS delivered
        FROM deliveries d
        JOIN school s ON d.school_id = s.school_id
        GROUP BY s.region
        ORDER BY s.region
    ");
    $progressPerRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==============================
    // 6️⃣ PROGRESS PER LOT
    // ==============================
    $stmt = $pdo->query("
        SELECT 
            l.lot_name,
            COUNT(*) AS total,
            SUM(CASE WHEN d.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN d.status = 'accepted' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) AS delivered
        FROM deliveries d
        LEFT JOIN keystage k ON d.keystage_id = k.keystage_id
        JOIN lot l ON l.lot_id = COALESCE(d.lot_id, k.lot_id)
        GROUP BY l.lot_name
        ORDER BY l.lot_name
    ");
    $progressPerLot = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Return everything as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'deliveryStatusOverview' => $deliveryStatusOverview,
        'monthlyDeliveryTrend'   => $monthlyDeliveryTrend,
        'inventoryData'          => $inventoryData,
        'inventoryByWarehouse'   => $inventoryByWarehouse,
        'progressPerRegion'      => $progressPerRegion,
        'progressPerLot'         => $progressPerLot
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
