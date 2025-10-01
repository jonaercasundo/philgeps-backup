<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['logistic_name']) || empty($_POST['region']) || empty($_POST['warehouse_id'])) {
        echo json_encode(["success" => false, "message" => "Logistics name, region, and warehouse are required"]);
        exit;
    }

    // Insert into logistics table
    $stmt = $pdo->prepare("INSERT INTO `logistics`(`logistic_name`) VALUES (?)");
    $stmt->execute([$_POST['logistic_name']]);
    $logistic_id = $pdo->lastInsertId();

    // Insert into logistics_location table with region AND warehouse_id
    $stmt_location = $pdo->prepare("INSERT INTO `logistics_location`(`logistics_id`, `region`, `warehouse_id`) VALUES (?, ?, ?)");
    $stmt_location->execute([
        $logistic_id,
        $_POST['region'],
        $_POST['warehouse_id']
    ]);

    echo json_encode(["success" => true, "message" => "Logistics added successfully"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}