<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['logistic_id']) || empty($_POST['logistic_name']) || empty($_POST['region']) || empty($_POST['warehouse_id'])) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    $logistic_id = $_POST['logistic_id'];
    $logistic_name = $_POST['logistic_name'];
    $region = $_POST['region'];
    $warehouse_id = $_POST['warehouse_id'];

    // Update logistics table
    $stmt_logistics = $pdo->prepare("UPDATE `logistics` SET `logistic_name` = ? WHERE `logistic_id` = ?");
    $stmt_logistics->execute([$logistic_name, $logistic_id]);

    // Update logistics_location table
    $stmt_location = $pdo->prepare("UPDATE `logistics_location` SET `region` = ?, `warehouse_id` = ? WHERE `logistics_id` = ?");
    $stmt_location->execute([$region, $warehouse_id, $logistic_id]);

    // Check if any rows were affected
    if ($stmt_logistics->rowCount() > 0 || $stmt_location->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Logistics updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No changes made or logistics not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}