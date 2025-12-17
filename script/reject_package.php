<?php
header('Content-Type: application/json');
session_start();
require "../config/db.php";

try {
    if (empty($_POST['package_status_id']) || empty($_POST['password'])) {
        echo json_encode(["success" => false, "message" => "Package ID and password are required"]);
        exit;
    }

    $package_status_id = $_POST['package_status_id'];
    $password = $_POST['password'];
    $remarks = $_POST['remarks'] ?? '';
    $username = $_SESSION['username'] ?? '';
    $user_id = $_SESSION['user_id'] ?? '';

    if (empty($username)) {
        echo json_encode(["success" => false, "message" => "User not authenticated"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid password!"]);
        exit;
    }

    // Update status to 'rejected' and add remarks
    $stmt = $pdo->prepare("UPDATE package_status SET status = 'accepted', remarks = ? WHERE package_status_id = ?");
    $stmt->execute([$remarks, $package_status_id]);

    if ($stmt->rowCount() > 0) {
        $action_message = $username . " changed a package status to accepted.";
        $details = "Package Status ID: " . $package_status_id . ". Remarks: " . $remarks;
        
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->execute([$user_id, $action_message, $details]);
        
        echo json_encode(["success" => true, "message" => "Package rejected successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No changes made or package not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
}
?>