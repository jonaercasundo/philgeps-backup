<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['location_id']) || empty($_POST['edit_region'])) {
        echo json_encode(["success" => false, "message" => "Location ID and region are required"]);
        exit;
    }

    $location_id = $_POST['location_id'];
    $region = trim($_POST['edit_region']);

    // Validate region is not empty after trimming
    if (empty($region)) {
        echo json_encode(["success" => false, "message" => "Region cannot be empty"]);
        exit;
    }

    // Check if location exists
    $checkStmt = $pdo->prepare("SELECT logistics_location_id FROM logistics_location WHERE logistics_location_id = ?");
    $checkStmt->execute([$location_id]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(["success" => false, "message" => "Logistics location not found"]);
        exit;
    }

    // Prepare and execute update query
    $stmt = $pdo->prepare("UPDATE `logistics_location` SET `region` = ? WHERE `logistics_location_id` = ?");
    $stmt->execute([$region, $location_id]);

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Logistics location updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No changes made"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}