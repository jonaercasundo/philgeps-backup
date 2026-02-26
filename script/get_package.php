<?php
require "../config/db.php";

$package_id = $_GET['package_id'] ?? null;
if (!$package_id) {
    echo json_encode(['success' => false, 'message' => 'Missing package_id']);
    exit;
}

try {
    // Get package details
    $stmt = $pdo->prepare("
            SELECT
            p.package_id,
            p.package_num,
            p.width,
            p.height,
            p.length,
            p.keystage_id,
            k.keystage_num AS keystage_name,
            k.description,
            p.lot_id,
            l.lot_name
        FROM package p
        LEFT JOIN keystage k ON k.keystage_id = p.keystage_id
        LEFT JOIN lot l ON l.lot_id = p.lot_id
        WHERE p.package_id = 295;
    ");
    $stmt->execute([$package_id]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    // Get package items
    $stmt = $pdo->prepare("
        SELECT pc.item_id, pc.qty, i.item_name 
        FROM package_content pc
        JOIN item i ON pc.item_id = i.item_id
        WHERE pc.package_id = ?");
    $stmt->execute([$package_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'package' => $package, 'items' => $items]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
