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

        // Check if inventory record exists
        $check = $pdo->prepare("SELECT qty FROM inventory WHERE item_id = ? AND warehouse_id = ?");
        $check->execute([$item_id, $warehouse_id]);
        $existing = $check->fetch();

        if ($existing) {
            // Update existing record
            $new_qty = $existing['qty'] + $quantity;
            $update = $pdo->prepare("UPDATE inventory SET qty = ? WHERE item_id = ? AND warehouse_id = ?");
            if ($update->execute([$new_qty, $item_id, $warehouse_id])) {
                $success_count++;
            } else {
                $errors[] = "Update failed for item $item_id";
            }
        } else {
            // Insert new record
            $max_id = $pdo->query("SELECT COALESCE(MAX(inventory_id), 0) + 1 as next_id FROM inventory")->fetch()['next_id'];
            
            $insert = $pdo->prepare("INSERT INTO inventory (inventory_id, warehouse_id, item_id, qty) VALUES (?, ?, ?, ?)");
            if ($insert->execute([$max_id, $warehouse_id, $item_id, $quantity])) {
                $success_count++;
            } else {
                $errors[] = "Insert failed for item $item_id";
            }
        }
    }

    if ($success_count > 0) {
        $msg = "Added $success_count items to inventory";
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