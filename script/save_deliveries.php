<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust
$keystage = $_POST['keystage'];
$stmt = $pdo->prepare("SELECT k.keystage_num, k.description, l.lot_name FROM keystage k LEFT JOIN lot l ON k.lot_id = l.lot_id WHERE k.keystage_id = $keystage");
        $stmt->execute();
        $lot = $stmt->fetch(PDO::FETCH_ASSOC);
try {
    $stmt = $pdo->prepare("INSERT INTO `deliveries`(`project_id`, `dr_no`, `delivery_date`,`school`,`remarks`, `address`) 
    VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['project'],
        $_POST['DRN'],
        $_POST['dateDeliver'],
        $_POST['school'],
        $_POST['package_type']." LOT ".$lot['lot_name']." KS".$lot['keystage_num']." ".$lot['description'],
        $_POST['address']
    ]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}