<?php
session_start();
header('Content-Type: application/json');
require "../config/db.php";

try {
    // Check if we have the required data
    if (empty($_POST['items_json']) || empty($_POST['password'])) {
        echo json_encode(["success" => false, "message" => "Missing items data or password", "toast" => "Missing credentials", "type" => "danger"]);
        exit;
    }

    $username = $_SESSION['username'];
    $password = $_POST['password'];
    $items = json_decode($_POST['items_json'], true);
    
    // Verify password first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Invalid password!", "toast" => "Invalid password!", "type" => "danger"]);
        exit;
    }

    // Password verified, now process items
    if (!is_array($items) || empty($items)) {
        echo json_encode(["success" => false, "message" => "Invalid items data", "toast" => "Invalid items data", "type" => "danger"]);
        exit;
    }

    $success_count = 0;
    $pending_approval_count = 0;
    $errors = [];

    foreach ($items as $item) {
        if (empty($item['item_id']) || empty($item['quantity'])) {
            $errors[] = "Missing item_id or quantity";
            continue;
        }

        $warehouse_id = $item['warehouse_id'] ?? 1;
        $item_id = intval($item['item_id']);
        $quantity = intval($item['quantity']);

        if ($quantity <= 0) {
            $errors[] = "Invalid quantity for item $item_id";
            continue;
        }

        // Check if inventory record exists and is approved
        $check = $pdo->prepare("SELECT qty, inventory_status FROM inventory WHERE item_id = ? AND warehouse_id = ?");
        $check->execute([$item_id, $warehouse_id]);
        $existing = $check->fetch();

        if ($existing && $existing['inventory_status'] === 'Approved') {
            // Update existing APPROVED record - add quantity immediately
            $new_qty = $existing['qty'] + $quantity;
            $update = $pdo->prepare("UPDATE inventory SET qty = ? WHERE item_id = ? AND warehouse_id = ? AND inventory_status = 'Approved'");
            if ($update->execute([$new_qty, $item_id, $warehouse_id])) {
                $success_count++;
            } else {
                $errors[] = "Update failed for item $item_id";
            }
        } else if ($existing && $existing['inventory_status'] === 'For Approval') {
            // Item exists but is pending approval - create new pending record
            $max_id = $pdo->query("SELECT COALESCE(MAX(inventory_id), 0) + 1 as next_id FROM inventory")->fetch()['next_id'];
            
            $insert = $pdo->prepare("INSERT INTO inventory (inventory_id, warehouse_id, item_id, qty, inventory_status) VALUES (?, ?, ?, ?, 'For Approval')");
            if ($insert->execute([$max_id, $warehouse_id, $item_id, $quantity])) {
                $pending_approval_count++;
            } else {
                $errors[] = "Insert failed for item $item_id (pending approval)";
            }
        } else {
            // No existing record or status is neither Approved nor For Approval - create new pending record
            $max_id = $pdo->query("SELECT COALESCE(MAX(inventory_id), 0) + 1 as next_id FROM inventory")->fetch()['next_id'];
            
            $insert = $pdo->prepare("INSERT INTO inventory (inventory_id, warehouse_id, item_id, qty, inventory_status) VALUES (?, ?, ?, ?, 'For Approval')");
            if ($insert->execute([$max_id, $warehouse_id, $item_id, $quantity])) {
                $pending_approval_count++;
            } else {
                $errors[] = "Insert failed for item $item_id";
            }
        }
    }

    // Build success message based on what was processed
    $message_parts = [];
    if ($success_count > 0) {
        $message_parts[] = "Added $success_count items to existing inventory";
    }
    if ($pending_approval_count > 0) {
        $message_parts[] = "$pending_approval_count items pending approval";
    }

    if (!empty($message_parts)) {
        $msg = implode(", ", $message_parts);
        if (!empty($errors)) {
            $msg .= ". Some errors occurred.";
        }
        echo json_encode(["success" => true, "message" => $msg, "toast" => $msg, "type" => "success"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to add items: " . implode(', ', $errors), "toast" => "Failed to add items", "type" => "danger"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage(), "toast" => "Database error", "type" => "danger"]);
}