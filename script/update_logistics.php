<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['logistic_id']) || empty($_POST['logistic_name'])) {
        echo json_encode(["success" => false, "message" => "Logistics ID and name are required"]);
        exit;
    }

    $logistic_id = $_POST['logistic_id'];
    $logistic_name = $_POST['logistic_name'];

    // Update logistics table only
    $stmt = $pdo->prepare("UPDATE `logistics` SET `logistic_name` = ? WHERE `logistic_id` = ?");
    $stmt->execute([
        $logistic_name,
        $logistic_id
    ]);

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Logistics updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No changes made or logistics not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>