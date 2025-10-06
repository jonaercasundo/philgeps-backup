<?php
header('Content-Type: application/json');
session_start();
require "../config/db.php";

try {
    // Validate required fields
    if (empty($_POST['accept_inventory_id']) || empty($_POST['accept_password'])) {
        echo json_encode(["success" => false, "message" => "Inventory ID and password are required"]);
        exit;
    }

    $inventory_id = $_POST['accept_inventory_id'];
    $password = $_POST['accept_password'];
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

    // Get the inventory record to be accepted
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE inventory_id = ?");
    $stmt->execute([$inventory_id]);
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory) {
        echo json_encode(["success" => false, "message" => "Inventory record not found"]);
        exit;
    }

    // Check if there's an existing approved record with same item_id and warehouse_id
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_id = ? AND warehouse_id = ? AND inventory_status = 'Approved' AND inventory_id != ?");
    $stmt->execute([$inventory['item_id'], $inventory['warehouse_id'], $inventory_id]);
    $existing_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_record) {
        // Update existing approved record by adding quantities
        $new_quantity = $existing_record['qty'] + $inventory['qty'];
        $stmt = $pdo->prepare("UPDATE inventory SET qty = ? WHERE inventory_id = ?");
        $stmt->execute([$new_quantity, $existing_record['inventory_id']]);
        
        // Delete the "For Approval" record since it's been merged
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id = ?");
        $stmt->execute([$inventory_id]);
        
        echo json_encode(["success" => true, "message" => "Inventory accepted and merged with existing record"]);
    } else {
        // No existing approved record, just update status to Approved
        $stmt = $pdo->prepare("UPDATE inventory SET inventory_status = 'Approved' WHERE inventory_id = ?");
        $stmt->execute([$inventory_id]);
        
        // Check if any rows were affected
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Inventory accepted successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "No changes made"]);
        }
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}