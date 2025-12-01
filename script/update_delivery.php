<?php
// script/update_delivery.php
session_start();
require "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $delivery_id = $_POST['delivery_id'];
    $dr_no = $_POST['dr_no'];
    $delivery_date = $_POST['delivery_date'];
    $status = $_POST['status'];
    $warehouse_id = $_POST['warehouse'] ?? null;

    try {
        $pdo->beginTransaction();

        // Store warehouse_id in session for future use
        if ($warehouse_id) {
            $_SESSION['warehouse_id'] = $warehouse_id;
        }

        // Update delivery basic info
        $stmt = $pdo->prepare("UPDATE deliveries SET dr_no=?, delivery_date=? WHERE delivery_id=?");
        $stmt->execute([$dr_no, $delivery_date, $delivery_id]);

        $username = $_SESSION['username'] ?? 'system';
        $package_quantities = $_POST['package_qty'] ?? [];
        $package_status_changes = $_POST['package_status_change'] ?? [];

        // Process new packages (from pending to accepted/delivered)
        if (($status === 'accepted' || $status === 'delivered') && !empty($package_quantities)) {
            if (!$warehouse_id) {
                throw new Exception("No warehouse assigned");
            }

            foreach ($package_quantities as $package_status_id => $num_packages) {
                $num_packages = (int)$num_packages;
                
                if ($num_packages <= 0) continue;

                // Check current status - only process if pending
                $stmt = $pdo->prepare("SELECT status FROM package_status WHERE package_status_id = ?");
                $stmt->execute([$package_status_id]);
                $current_status = $stmt->fetchColumn();

                if ($current_status !== 'pending') {
                    continue;
                }

                // Get items in this package
                $stmt = $pdo->prepare("
                    SELECT pc.item_id, pc.qty, i.item_name
                    FROM package_status ps
                    JOIN package_content pc ON pc.package_id = ps.package_id
                    JOIN item i ON i.item_id = pc.item_id
                    WHERE ps.package_status_id = ?
                ");
                $stmt->execute([$package_status_id]);
                $package_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Subtract inventory (FIFO)
                foreach ($package_items as $item) {
                    $item_id = $item['item_id'];
                    $qty_per_package = $item['qty'];
                    $total_qty_to_subtract = $qty_per_package * $num_packages;
                    
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

                        $stmt = $pdo->prepare("UPDATE inventory SET qty = ? WHERE inventory_id = ?");
                        $stmt->execute([$new_qty, $inv['inventory_id']]);

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
                            "Package #{$package_status_id}: {$subtract} pulled out"
                        ]);

                        $remaining -= $subtract;
                    }

                    if ($remaining > 0) {
                        throw new Exception("Not enough inventory for {$item['item_name']}. Need {$total_qty_to_subtract}, missing {$remaining}");
                    }
                }

                // Update package status
                $stmt = $pdo->prepare("UPDATE package_status SET status = ? WHERE package_status_id = ?");
                $stmt->execute([$status, $package_status_id]);
            }
        }

        // Process status changes for already accepted/delivered packages
        if (!empty($package_status_changes)) {
            foreach ($package_status_changes as $package_status_id => $new_status) {
                if (empty($new_status)) continue; // Skip if no change selected

                // Get current status
                $stmt = $pdo->prepare("SELECT status FROM package_status WHERE package_status_id = ?");
                $stmt->execute([$package_status_id]);
                $current_status = $stmt->fetchColumn();

                // Handle reverting to pending (return inventory)
                if ($new_status === 'pending' && ($current_status === 'accepted' || $current_status === 'delivered')) {
                    if (!$warehouse_id) {
                        throw new Exception("No warehouse assigned for returning inventory");
                    }

                    // Get the last subtracted quantities from inventory_history
                    $stmt = $pdo->prepare("
                        SELECT pc.item_id, pc.qty, i.item_name
                        FROM package_status ps
                        JOIN package_content pc ON pc.package_id = ps.package_id
                        JOIN item i ON i.item_id = pc.item_id
                        WHERE ps.package_status_id = ?
                    ");
                    $stmt->execute([$package_status_id]);
                    $package_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($package_items as $item) {
                        $item_id = $item['item_id'];
                        
                        // Get the total amount that was subtracted for this package
                        $stmt = $pdo->prepare("
                            SELECT SUM(old_qty - new_qty) as total_subtracted
                            FROM inventory_history
                            WHERE item_id = ? 
                            AND warehouse_id = ? 
                            AND remarks LIKE ?
                            AND change_type = 'update'
                        ");
                        $stmt->execute([$item_id, $warehouse_id, "Package #{$package_status_id}:%"]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $qty_to_return = $result['total_subtracted'] ?? 0;

                        if ($qty_to_return > 0) {
                            // Add back to the most recent inventory entry for this item
                            $stmt = $pdo->prepare("
                                SELECT inventory_id, qty 
                                FROM inventory 
                                WHERE item_id = ? AND warehouse_id = ? AND inventory_status = 'Approved'
                                ORDER BY inventory_id DESC
                                LIMIT 1
                            ");
                            $stmt->execute([$item_id, $warehouse_id]);
                            $inv = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($inv) {
                                $old_qty = $inv['qty'];
                                $new_qty = $old_qty + $qty_to_return;

                                $stmt = $pdo->prepare("UPDATE inventory SET qty = ? WHERE inventory_id = ?");
                                $stmt->execute([$new_qty, $inv['inventory_id']]);

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
                                    "Reverted: {$qty_to_return} returned"
                                ]);
                            }
                        }
                    }
                }

                // Update package status (accepted <-> delivered or to pending)
                $stmt = $pdo->prepare("UPDATE package_status SET status = ? WHERE package_status_id = ?");
                $stmt->execute([$new_status, $package_status_id]);
            }
        }

        // Update delivery status based on all packages
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count 
            FROM package_status 
            WHERE delivery_id = ? 
            GROUP BY status
        ");
        $stmt->execute([$delivery_id]);
        $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Determine overall delivery status
        $total_packages = array_sum($status_counts);
        $pending_count = $status_counts['pending'] ?? 0;
        $accepted_count = $status_counts['accepted'] ?? 0;
        $delivered_count = $status_counts['delivered'] ?? 0;

        // Set to 'delivered' only if ALL packages are delivered
        if ($delivered_count == $total_packages) {
            $delivery_status = 'delivered';
        }
        // Set to 'accepted' only if there are NO pending packages (can have mix of accepted/delivered)
        elseif ($pending_count == 0 && $total_packages > 0) {
            $delivery_status = 'accepted';
        }
        // Otherwise keep as pending or current status
        else {
            $delivery_status = 'pending';
        }

        $stmt = $pdo->prepare("UPDATE deliveries SET status = ? WHERE delivery_id = ?");
        $stmt->execute([$delivery_status, $delivery_id]);

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