<?php
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['logistic_name'])) {
        echo json_encode(["success" => false, "message" => "Logistics name is required"]);
        exit;
    }

    // Insert into logistics table only
    $stmt = $pdo->prepare("INSERT INTO `logistics`(`logistic_name`) VALUES (?)");
    $stmt->execute([$_POST['logistic_name']]);
    $logistic_id = $pdo->lastInsertId();

    echo json_encode(["success" => true, "message" => "Logistics added successfully"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>