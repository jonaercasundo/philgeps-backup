<?php
// script/update_delivery.php
session_start();
require "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $delivery_id = $_POST['delivery_id'];
    $dr_no = $_POST['dr_no'];
    $delivery_date = $_POST['delivery_date'];
    $status = $_POST['status'];
    $selected_packages = $_POST['packages'] ?? [];
    $warehouse_id = $_POST['warehouse'] ?? null;

    try {
        $pdo->beginTransaction();

        // Store warehouse_id in session for future use
        if ($warehouse_id) {
            $_SESSION['warehouse_id'] = $warehouse_id;
        }

        // Update delivery basic info including logistics_location_id (warehouse)
        if ($warehouse_id) {
            // First, get or create logistics_location_id
            $stmt = $pdo->prepare("
                SELECT logistics_location_id 
                FROM logistics_location 
                WHERE warehouse_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$warehouse_id]);
            $logistics_location = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($logistics_location) {
                $logistics_location_id = $logistics_location['logistics_location_id'];
            } else {
                // Create a new logistics_location entry if none exists
                // You might need to adjust this based on your logistics table structure
                $stmt = $pdo->prepare("
                    INSERT INTO logistics_location (logistics_id, warehouse_id, region) 
                    VALUES (1, ?, 'Default Region')
                ");
                $stmt->execute([$warehouse_id]);
                $logistics_location_id = $pdo->lastInsertId();
            }

            // Update delivery with logistics_location_id
            $stmt = $pdo->prepare("
                UPDATE deliveries 
                SET dr_no=?, delivery_date=?, logistics_location_id=?
                WHERE delivery_id=?
            ");
            $stmt->execute([$dr_no, $delivery_date, $logistics_location_id, $delivery_id]);
        } else {
            // Update without warehouse
            $stmt = $pdo->prepare("
                UPDATE deliveries 
                SET dr_no=?, delivery_date=?
                WHERE delivery_id=?
            ");
            $stmt->execute([$dr_no, $delivery_date, $delivery_id]);
        }

        // Update delivery basic info
        $stmt = $pdo->prepare("UPDATE deliveries SET dr_no=?, delivery_date=? WHERE delivery_id=?");
        $stmt->execute([$dr_no, $delivery_date, $delivery_id]);

        // If status is "accepted" or "delivered", process packages with quantities
        if (($status === 'accepted' || $status === 'delivered')) {
            //  $warehouse_id = 2;
            // $warehouse_id = $_SESSION['warehouse_id'] ?? null;
            $username = $_SESSION['username'] ?? 'system';
            $package_quantities = $_POST['package_qty'] ?? [];

            $current_warehouse_id = $warehouse_id;

            if (!$warehouse_id) {
                throw new Exception("No warehouse assigned");
            }

            foreach ($package_quantities as $package_status_id => $num_packages) {
                $num_packages = (int)$num_packages;
                
                if ($num_packages <= 0) continue; // Skip if 0 packages

                // Check current status - only process if pending
                $stmt = $pdo->prepare("SELECT status FROM package_status WHERE package_status_id = ?");
                $stmt->execute([$package_status_id]);
                $current_status = $stmt->fetchColumn();

                if ($current_status !== 'pending') {
                    continue; // Skip non-pending packages (already processed)
                }

                // Get items in this package from package_content
                $stmt = $pdo->prepare("
                    SELECT pc.item_id, pc.qty, i.item_name
                    FROM package_status ps
                    JOIN package_content pc ON pc.package_id = ps.package_id
                    JOIN item i ON i.item_id = pc.item_id
                    WHERE ps.package_status_id = ?
                ");
                $stmt->execute([$package_status_id]);
                $package_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Subtract inventory for each item (qty per package * number of packages)
                foreach ($package_items as $item) {
                    $item_id = $item['item_id'];
                    $qty_per_package = $item['qty'];
                    $total_qty_to_subtract = $qty_per_package * $num_packages;
                    
                    // Get approved inventory (FIFO)
                    $stmt = $pdo->prepare("
                        SELECT inventory_id, qty 
                        FROM inventory 
                        WHERE item_id = ? AND warehouse_id = ? AND inventory_status = 'Approved'
                        ORDER BY inventory_id
                    ");
                    $stmt->execute([$item_id, $warehouse_id]);
                    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $remaining = $total_qty_to_subtract;

                    foreach ($inventory as $inv) {
                        if ($remaining <= 0) break;

                        $old_qty = $inv['qty'];
                        $subtract = min($remaining, $old_qty);
                        $new_qty = $old_qty - $subtract;

                        // Update inventory
                        $stmt = $pdo->prepare("UPDATE inventory SET qty = ? WHERE inventory_id = ?");
                        $stmt->execute([$new_qty, $inv['inventory_id']]);

                        // Log history
                        $stmt = $pdo->prepare("
                            INSERT INTO inventory_history 
                            (inventory_id, item_id, warehouse_id, old_qty, new_qty, changed_by, change_type, remarks)
                            VALUES (?, ?, ?, ?, ?, ?, 'update', ?)
                        ");
                        $stmt->execute([
                            $inv['inventory_id'], 
                            $item_id, 
                            $warehouse_id, 
                            $old_qty, 
                            $new_qty, 
                            $username,
                            "{$subtract} pulled out"
                        ]);

                        $remaining -= $subtract;
                    }

                    if ($remaining > 0) {
                        throw new Exception("Not enough inventory for {$item['item_name']}. Need {$total_qty_to_subtract}, missing {$remaining}");
                    }
                }

                // Update package status to match delivery status (accepted or delivered)
                $stmt = $pdo->prepare("UPDATE package_status SET status = ? WHERE package_status_id = ?");
                $stmt->execute([$status, $package_status_id]);
            }

            // Check if all packages match the new status, then update delivery status
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as matched
                FROM package_status 
                WHERE delivery_id = ?
            ");
            $stmt->execute([$status, $delivery_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] == $result['matched']) {
                $stmt = $pdo->prepare("UPDATE deliveries SET status = ? WHERE delivery_id = ?");
                $stmt->execute([$status, $delivery_id]);
            }
        }

        $pdo->commit();
        header("Location: ../deliveries.php?toast=Delivery updated successfully&type=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ../deliveries.php?toast=" . urlencode($e->getMessage()) . "&type=danger");
        exit;
    }
}
?>