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
    $user_id = $_SESSION['user_id'] ??'';

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
    $stmt = $pdo->prepare("SELECT i.*, it.item_name, w.warehouse_name 
                            FROM inventory i 
                            JOIN item it ON i.item_id = it.item_id 
                            JOIN warehouse w ON i.warehouse_id = w.warehouse_id 
                            WHERE i.inventory_id = ?");
    $stmt->execute([$inventory_id]);
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory) {
        echo json_encode(["success" => false, "message" => "Inventory record not found"]);
        exit;
    }

    $item_name = $inventory['item_name'];
    $warehouse_name = $inventory['warehouse_name'];
    $quantity = $inventory['qty'];

    // Delete the rejected inventory record
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id = ?");
    $stmt->execute([$inventory_id]);

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        // Prepare activity log for rejection
        $action_message = $username . " rejected inventory item";
        
        // Build details with remarks included
        $details = "Rejected (-" . $quantity . ") " . $item_name . " in " . $warehouse_name;
        if (!empty($remarks)) {
            $details .= $remarks;
        }
        
        // Insert into activity logs
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->execute([$user_id, $action_message, $details]);

        echo json_encode(["success" => true, "message" => "Inventory rejected and deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "No record found to reject"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}