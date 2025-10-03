<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['logistics_id']) || empty($_POST['warehouse_id']) || empty($_POST['region'])) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    $logistics_id = $_POST['logistics_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $region = $_POST['region'];

    // Check if logistics_location record already exists for this logistics and warehouse
    $checkLocationStmt = $pdo->prepare("SELECT logistics_location_id FROM logistics_location WHERE logistics_id = ? AND warehouse_id = ?");
    $checkLocationStmt->execute([$logistics_id, $warehouse_id]);
    $existingLocation = $checkLocationStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingLocation) {
        // Update existing logistics location - update region
        $updateStmt = $pdo->prepare("UPDATE logistics_location SET region = ? WHERE logistics_id = ? AND warehouse_id = ?");
        $updateStmt->execute([$region, $logistics_id, $warehouse_id]);
        
        $message = "Logistics location updated successfully. Region updated to: " . $region;
    } else {
        // Insert new logistics location record
        $insertStmt = $pdo->prepare("INSERT INTO `logistics_location`(`logistics_id`, `warehouse_id`, `region`) VALUES (?,?,?)");
        $insertStmt->execute([$logistics_id, $warehouse_id, $region]);
        
        $message = "New logistics location created successfully";
    }

    echo json_encode(["success" => true, "message" => $message]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}