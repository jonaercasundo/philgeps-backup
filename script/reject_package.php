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

    // Get delivery_id from package_status before starting transaction
    $stmt_get_delivery_id = $pdo->prepare("SELECT delivery_id FROM package_status WHERE package_status_id = ?");
    $stmt_get_delivery_id->execute([$package_status_id]);
    $package_info = $stmt_get_delivery_id->fetch(PDO::FETCH_ASSOC);

    if (!$package_info) {
        echo json_encode(["success" => false, "message" => "Package status not found."]);
        exit;
    }
    $delivery_id = $package_info['delivery_id'];

    $pdo->beginTransaction();

    // Update status to 'accepted' and add remarks
    $stmt_update_pkg = $pdo->prepare("UPDATE package_status SET status = 'accepted', remarks = ? WHERE package_status_id = ?");
    $stmt_update_pkg->execute([$remarks, $package_status_id]);

    if ($stmt_update_pkg->rowCount() > 0) {
        
        // After rejection, check if any packages are still 'delivered' to set correct delivery status.
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM package_status 
            WHERE delivery_id = ? AND status = 'delivered'
        ");
        $stmt_check->execute([$delivery_id]);
        $delivered_count = $stmt_check->fetchColumn();

        $new_delivery_status = ($delivered_count > 0) ? 'partially delivered' : 'accepted';

        $stmt_update_delivery = $pdo->prepare("UPDATE deliveries SET status = ? WHERE delivery_id = ?");
        $stmt_update_delivery->execute([$new_delivery_status, $delivery_id]);

        $action_message = $username . " changed a package status to accepted.";
        $details = "Package Status ID: " . $package_status_id . ". Remarks: " . $remarks;
        
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->execute([$user_id, $action_message, $details]);
        
        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Package rejected successfully"]);
    } else {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "No changes made or package not found"]);
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
}
?>