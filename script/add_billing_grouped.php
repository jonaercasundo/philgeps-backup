<?php
session_start();
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_drs = $_POST['selected_dr'] ?? [];
    $group_name = trim($_POST['group_name'] ?? '');

    if (empty($selected_drs)) {
        echo json_encode(['success' => false, 'toast' => 'No deliveries selected', 'type' => 'danger']);
        exit;
    }

    if (empty($group_name)) {
        echo json_encode(['success' => false, 'toast' => 'Please provide a group name', 'type' => 'danger']);
        exit;
    }

    // Check authorization
    $allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];
    $user_role = $_SESSION['role'] ?? '';
    
    if (!in_array($user_role, $allowed_roles)) {
        echo json_encode(['success' => false, 'toast' => 'Unauthorized access', 'type' => 'danger']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $success_count = 0;
        $duplicate_count = 0;

        // Step 1: Check if group already exists, if not create it
        $check_group_stmt = $pdo->prepare("SELECT group_id FROM grouping WHERE group_name = ?");
        $check_group_stmt->execute([$group_name]);
        
        if ($check_group_stmt->rowCount() > 0) {
            // Group exists, get the group_id
            $group_id = $check_group_stmt->fetchColumn();
        } else {
            // Create new group
            $create_group_stmt = $pdo->prepare("INSERT INTO grouping (group_name, status, created_at) VALUES (?, 'active', NOW())");
            if ($create_group_stmt->execute([$group_name])) {
                $group_id = $pdo->lastInsertId();
            } else {
                throw new Exception("Failed to create group");
            }
        }

        foreach ($selected_drs as $dr_no) {
            $dr_no = trim($dr_no);
            if (empty($dr_no)) continue;

            // Check if DR already exists in any group
            $check_dr_stmt = $pdo->prepare("SELECT id FROM billing_grouped WHERE dr_no = ?");
            $check_dr_stmt->execute([$dr_no]);
            
            if ($check_dr_stmt->rowCount() > 0) {
                $duplicate_count++;
                continue;
            }
            
            // Insert into billing_grouped
            $insert = $pdo->prepare("INSERT INTO billing_grouped (dr_no, group_id, created_at) VALUES (?, ?, NOW())");
            if ($insert->execute([$dr_no, $group_id])) {
                $success_count++;
            }
        }

        // Log the activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['name'] . " added {$success_count} delivery(ies) to billing group '{$group_name}'"
        ]);

        $pdo->commit();

        // Build success message
        if ($success_count > 0 && $duplicate_count > 0) {
            $message = "{$success_count} delivery(ies) added to group '{$group_name}'. {$duplicate_count} duplicate(s) skipped.";
            $type = 'warning';
        } elseif ($success_count > 0) {
            $message = "{$success_count} delivery(ies) successfully added to group '{$group_name}'";
            $type = 'success';
        } elseif ($duplicate_count > 0) {
            $message = "All selected deliveries are already in other billing groups";
            $type = 'info';
        } else {
            $message = "No deliveries were added";
            $type = 'danger';
        }

        echo json_encode([
            'success' => $success_count > 0,
            'toast' => $message,
            'type' => $type,
            'added_count' => $success_count,
            'duplicate_count' => $duplicate_count
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'toast' => 'Database error: ' . $e->getMessage(),
            'type' => 'danger'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'toast' => $e->getMessage(),
            'type' => 'danger'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'toast' => 'Invalid request method',
        'type' => 'danger'
    ]);
}