<?php
header('Content-Type: application/json');
session_start();
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['reject_inventory_id']) || empty($_POST['reject_password'])) {
        echo json_encode(["success" => false, "message" => "Inventory ID and password are required"]);
        exit;
    }

    $inventory_id = $_POST['reject_inventory_id'];
    $password = $_POST['reject_password'];
    $username = $_SESSION['username'] ?? '';

    // Validate user session
    if (empty($username)) {
        echo json_encode(["success" => false, "message" => "User not authenticated"]);
        exit;
    }

    // Verify user password
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid password!"]);
        exit;
    }

    // Delete the rejected inventory record
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id = ?");
    $stmt->execute([$inventory_id]);

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Inventory rejected and deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No record found to reject"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}