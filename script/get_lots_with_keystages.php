<?php
header('Content-Type: application/json');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../config/db.php';  // adjust if needed

$response = [
    'success' => false,
    'lots' => []
];

try {
    // Get the package_id
    if (!isset($_GET['package_id'])) {
        throw new Exception("Missing package_id");
    }

    $package_id = $_GET['package_id'];

    // Get lots and keystages related to this package
    $sql = "
        SELECT 
            l.lot_id,
            l.lot_name,
            k.keystage_id,
            k.keystage_num,
            k.description AS keystage_desc,
            COUNT(p.package_id) AS qty_packages
        FROM package p
        LEFT JOIN keystage k ON p.keystage_id = k.keystage_id
        LEFT JOIN lot l ON k.lot_id = l.lot_id
        WHERE p.package_id = :package_id
        GROUP BY l.lot_id, l.lot_name, k.keystage_id, k.keystage_num, k.description
        ORDER BY l.lot_name, k.keystage_num
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['package_id' => $package_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        if (empty($row['lot_id'])) continue;

        $lotEntry = [
            'lot_id' => $row['lot_id'],
            'lot_name' => $row['lot_name'],
            'qty' => (int)$row['qty_packages']
        ];

        if (!empty($row['keystage_id'])) {
            $lotEntry['keystage_id'] = $row['keystage_id'];
            $lotEntry['keystage_num'] = $row['keystage_num'];
            $lotEntry['description'] = $row['keystage_desc'];
        }

        $response['lots'][] = $lotEntry;
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = "Error: " . $e->getMessage();
}

echo json_encode($response);
exit();
