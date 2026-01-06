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
    $next_status = 'delivered'; // The status we are setting to

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE package_status SET status = ? WHERE package_status_id = ?");
    $stmt->execute([$next_status, $package_status_id]);

    if ($stmt->rowCount() > 0) {

        // Check if all packages for this delivery now have the same status
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS matched
            FROM package_status
            WHERE delivery_id = ?
        ");
        $stmt_check->execute([$next_status, $delivery_id]);
        $row = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['total'] > 0 && $row['total'] == $row['matched']) {
            $delivered_date = date('Y-m-d');
            $stmt_update_delivery = $pdo->prepare(
                "UPDATE deliveries SET status = ?, delivered_date = ? WHERE delivery_id = ?"
            );
            $stmt_update_delivery->execute([$next_status, $delivered_date, $delivery_id]);
        }

        $action_message = $username . " marked a package as delivered.";
        $details = "Package Status ID: " . $package_status_id;
        
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->execute([$user_id, $action_message, $details]);
        
        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Package delivered successfully"]);
    } else {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "No changes made or package not found"]);
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>