<?php
require "../config/db.php";

$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Overall Progress Summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'Pending') AS pending,
            SUM(status = 'Delivered') AS delivered,
            SUM(status = 'Accepted') AS accepted
        FROM deliveries WHERE project_id = $reportId
    ");
    $deliveryStats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($deliveryStats['accepted'] != 0) {
        $deliveryStats['accepted'] = $deliveryStats['accepted'] / 2;
    }
    $overallProgress = $deliveryStats['total'] > 0
        ? round(($deliveryStats['delivered'] + $deliveryStats['accepted']) / $deliveryStats['total'] * 100, 1)
        : 0;

    // Progress per Region
    $stmt = $pdo->query("
        SELECT 
            s.region,
            COUNT(*) AS total,
            SUM(d.status = 'Pending') AS pending,
            SUM(d.status = 'Delivered') AS delivered,
            SUM(d.status = 'Accepted') AS accepted
        FROM deliveries d
        JOIN school s ON s.school_id = d.school_id
        WHERE d.project_id = $reportId
        GROUP BY s.region
        ORDER BY s.region
    ");
    $progressPerRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Progress per Lot
    $stmt = $pdo->query("
        SELECT 
            l.lot_name,
            COUNT(*) AS total,
            SUM(d.status = 'Pending')   AS pending,
            SUM(d.status = 'Delivered') AS delivered,
            SUM(d.status = 'Accepted')  AS accepted
        FROM deliveries d
        JOIN keystage k ON d.keystage_id = k.keystage_id
        JOIN lot l      ON k.lot_id = l.lot_id
        WHERE d.project_id = $reportId
        GROUP BY l.lot_name
        ORDER BY l.lot_name
    ");
    $progressPerLot = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "deliveryStats" => $deliveryStats,
        "overallProgress" => $overallProgress,
        "progressPerRegion" => $progressPerRegion,
        "progressPerLot" => $progressPerLot
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
