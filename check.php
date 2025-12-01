<?php
// Fix session cookies for HTTPS/IP/domain
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
          || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'path' => '/',
    'domain' => '', // allow any domain
    'secure' => $secure,
    'httponly' => true,
]);
session_start();

require 'config/db.php';

// Get POST data safely
$package_status_id = $_POST['id'] ?? null;
$delivery_id = $_POST['delivery_id'] ?? null;
$delivered_date = date('Y-m-d');
$current_status = '';
$next_status = '';

// Check captcha
if (!isset($_POST['captcha_answer']) || $_POST['captcha_answer'] != ($_SESSION['captcha'] ?? '')) {
    exit("Captcha failed!");
}

try {
    // Fetch current package status
    $stmt_package_status = $pdo->prepare("SELECT status, delivery_id FROM package_status WHERE package_status_id = :package_status_id");
    $stmt_package_status->execute([':package_status_id' => $package_status_id]);
    $package_status = $stmt_package_status->fetch(PDO::FETCH_ASSOC);


    if (!$package_status) exit("Package status not found.");
    if ((string)$package_status['delivery_id'] !== $delivery_id) exit("Data mismatch: Delivery ID does not match.");


    $current_status = $package_status['status'];
    $status_map = [
        'pending' => 'accepted',
        'accepted' => 'delivered',
        'delivered' => 'delivered'
    ];
    $next_status = $status_map[$current_status] ?? $current_status;

        // NEW: Get package items to subtract from inventory
    $warehouse_id = $_SESSION['warehouse_id'] ?? null;
    if (!$warehouse_id) {
        exit("Warehouse not specified in session.");
    }
    $username = $_SESSION['username'] ?? '';
    if (!$username) {
        exit("Username not specified in session.");
    }

    // Get all items in this package
    $stmt_items = $pdo->prepare("
        SELECT pc.item_id, pc.qty, i.item_name
        FROM package_status ps
        JOIN package p ON p.package_id = ps.package_id
        JOIN package_content pc ON pc.package_id = p.package_id
        JOIN item i ON i.item_id = pc.item_id
        WHERE ps.package_status_id = :package_status_id
    ");
    $stmt_items->execute([':package_status_id' => $package_status_id]);
    $package_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Subtract quantities from inventory
    foreach ($package_items as $item) {
        $item_id = $item['item_id'];
        $quantity_to_subtract = $item['qty'];
        
        // Get available approved inventory for this item in current warehouse
        $stmt_inventory = $pdo->prepare("
            SELECT inventory_id, qty 
            FROM inventory 
            WHERE item_id = :item_id 
            AND warehouse_id = :warehouse_id 
            AND inventory_status = 'Approved'
            ORDER BY inventory_id
        ");
        $stmt_inventory->execute([
            ':item_id' => $item_id,
            ':warehouse_id' => $warehouse_id
        ]);
        $inventory_records = $stmt_inventory->fetchAll(PDO::FETCH_ASSOC);
        
        $remaining_to_subtract = $quantity_to_subtract;
        $total_subtracted = 0;
        
        // Subtract from inventory records until we've deducted the full quantity
        foreach ($inventory_records as $inv_record) {
            if ($remaining_to_subtract <= 0) break;
            
            $available_qty = $inv_record['qty'];
            $inventory_id = $inv_record['inventory_id'];
            $subtracted_from_this_record = 0;
            
            if ($available_qty >= $remaining_to_subtract) {
                // This record has enough quantity to cover the remainder
                $new_qty = $available_qty - $remaining_to_subtract;
                $subtracted_from_this_record = $remaining_to_subtract;
                $stmt_update = $pdo->prepare("UPDATE inventory SET qty = :new_qty WHERE inventory_id = :inventory_id");
                $stmt_update->execute([
                    ':new_qty' => $new_qty,
                    ':inventory_id' => $inventory_id
                ]);
                $remaining_to_subtract = 0;
            } else {
                // This record doesn't have enough, take all of it and move to next record
                $subtracted_from_this_record = $available_qty;
                
                $stmt_update = $pdo->prepare("UPDATE inventory SET qty = 0 WHERE inventory_id = :inventory_id");
                $stmt_update->execute([':inventory_id' => $inventory_id]);
                $remaining_to_subtract -= $available_qty;
            }
            $total_subtracted += $subtracted_from_this_record;
            
            $stmt_history = $pdo->prepare("
                INSERT INTO inventory_history 
                (inventory_id, item_id, warehouse_id, old_qty, new_qty, changed_by, change_type, remarks) 
                VALUES 
                (:inventory_id, :item_id, :warehouse_id, :old_qty, :new_qty, :changed_by, 'update', :remarks)
            ");
            $stmt_history->execute([
                ':inventory_id' => $inventory_id,
                ':item_id' => $item_id,
                ':warehouse_id' => $warehouse_id,
                ':old_qty' => $available_qty,
                ':new_qty' => $available_qty - $subtracted_from_this_record,
                ':changed_by' => $username,
                ':remarks' => "{$subtracted_from_this_record} pulled out"
            ]);
        }
        // // Optional: Delete inventory records that now have 0 quantity
        // $stmt_cleanup = $pdo->prepare("DELETE FROM inventory WHERE qty = 0 AND inventory_status = 'Approved'");
        // $stmt_cleanup->execute();
    }

    // Handle file uploads
    $uploaded_photos = [];
    if (isset($_FILES['photo_upload']) && !empty($_FILES['photo_upload']['name'][0])) {
        $upload_dir ='uploads/'; // Absolute path

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_count = count($_FILES['photo_upload']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['photo_upload']['error'][$i] === UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['photo_upload']['name'][$i]);
                $unique_filename = uniqid() . "_" . $original_name;
                $target_file = "uploads/" . $unique_filename;

                if (move_uploaded_file($_FILES['photo_upload']['tmp_name'][$i], $target_file)) {
                    $uploaded_photos[] = $target_file;
                }
            }
        }
    }

    // Update package_status
    $stmt = $pdo->prepare("UPDATE package_status SET status = :status WHERE package_status_id = :package_status_id");
    $stmt->execute([
        ':status' => $next_status,
        ':package_status_id' => $package_status_id
    ]);

    // Check if all packages match the same status
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN status = :status THEN 1 ELSE 0 END) AS matched
        FROM package_status
        WHERE delivery_id = :delivery_id
    ");
    $stmt_check->execute([
        ':status' => $next_status,
        ':delivery_id' => $delivery_id
    ]);
    $row = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['total'] == $row['matched']) {
        $stmt_update_delivery = $pdo->prepare("
            UPDATE deliveries 
            SET status = :status, delivered_date = :delivered_date
            WHERE delivery_id = :delivery_id
        ");
        $stmt_update_delivery->execute([
            ':status' => $next_status,
            ':delivered_date' => $delivered_date,
            ':delivery_id' => $delivery_id
        ]);
    }

    // Insert uploaded photos into DB
    if (!empty($uploaded_photos)) {
        $stmt_photo = $pdo->prepare("
            INSERT INTO delivery_photo (package_status_id, status, delivery_photo)
            VALUES (:package_status_id, :status, :delivery_photo)
        ");
      
        foreach ($uploaded_photos as $photo_file) {
            $stmt_photo->execute([
                ':package_status_id' => $package_status_id,
                ':status' => $next_status,
                ':delivery_photo' => $photo_file
            ]);
        }
    }

    // Redirect to success page (absolute URL)
    $protocol = $secure ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    header("Location: $protocol://$host/philgeps/success.php?status=$next_status");
    exit;

} catch (Exception $e) {
    exit;

}
?>
