<?php
// script/get_delivery_packages.php
require "../config/db.php";

header('Content-Type: application/json');

$delivery_id = $_GET['delivery_id'] ?? null;

if (!$delivery_id) {
    echo json_encode([]);
    exit;
}

try {
    // Fetch packages including qty from package_status
    $stmt = $pdo->prepare("
        SELECT 
            ps.package_status_id,
            ps.status,
            ps.qty AS package_qty,
            p.package_num
        FROM package_status ps
        JOIN package p ON p.package_id = ps.package_id
        WHERE ps.delivery_id = ?
        ORDER BY p.package_num
    ");
    $stmt->execute([$delivery_id]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get items for each package
    foreach ($packages as &$package) {
        $stmt = $pdo->prepare("
            SELECT 
                i.item_id,
                i.item_name,
                pc.qty
            FROM package_status ps
            JOIN package_content pc ON pc.package_id = ps.package_id
            JOIN item i ON i.item_id = pc.item_id
            WHERE ps.package_status_id = ?
        ");
        $stmt->execute([$package['package_status_id']]);
        $package['items_detail'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($packages);
} catch (PDOException $e) {
    echo json_encode([]);
}
