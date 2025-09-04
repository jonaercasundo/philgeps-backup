<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust

$keystage = $_POST['keystage'] ?? null;

$stmt = $pdo->prepare("
    SELECT k.keystage_num, k.description, l.lot_name 
    FROM keystage k 
    LEFT JOIN lot l ON k.lot_id = l.lot_id 
    WHERE k.keystage_id = ?
");
$stmt->execute([$keystage]);
$lot = $stmt->fetch(PDO::FETCH_ASSOC);

try {
    $pdo->beginTransaction();

    // Insert into deliveries
    $stmtDelivery = $pdo->prepare("
        INSERT INTO deliveries (project_id, dr_no, delivery_date, school, remarks, address) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtDelivery->execute([
        $_POST['project'],
        $_POST['DRN'],
        $_POST['dateDeliver'],
        $_POST['school'],
        $_POST['package_type'] . " LOT " . $lot['lot_name'] . " KS" . $lot['keystage_num'] . " " . $lot['description'],
        $_POST['address']
    ]);

    // Get the new delivery_id
    $deliveryId = $pdo->lastInsertId();

    // Insert into delivery_packages
    $stmtPackage = $pdo->prepare("
        INSERT INTO delivery_packages (delivery_id, keystage_id) 
        VALUES (?, ?)
    ");
    $stmtPackage->execute([$deliveryId, $keystage]);

    $pdo->commit();

    header("Location: ../deliveries.php?toast=Delivery saved successfully&type=success");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: ../deliveries.php?toast=Error saving delivery: " . urlencode($e->getMessage()) . "&type=danger");
    exit;
}
