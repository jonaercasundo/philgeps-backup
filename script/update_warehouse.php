<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['warehouse_id']) || empty($_POST['warehouse_name']) || empty($_POST['warehouse_address']) || empty($_POST['contact_info'])) {
        echo json_encode(["success" => false, "message" => "All fields are required"]);
        exit;
    }

    $warehouse_id = $_POST['warehouse_id'];
    $warehouse_name = $_POST['warehouse_name'];
    $warehouse_address = $_POST['warehouse_address'];
    $contact_info = $_POST['contact_info'];

    // Prepare and execute update query
    $stmt = $pdo->prepare("UPDATE `warehouse` SET `warehouse_name` = ?, `warehouse_address` = ?, `contact_info` = ? WHERE `warehouse_id` = ?");
    $stmt->execute([
        $warehouse_name,
        $warehouse_address,
        $contact_info,
        $warehouse_id
    ]);

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Warehouse updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No changes made or warehouse not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
