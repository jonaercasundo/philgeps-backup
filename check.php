<?php
session_start();
require 'config/db.php';

// Get POST and GET data
$package_status_id = $_POST['id'];
$dr_no = $_POST['dr_no'];
$delivered_date = date('Y-m-d');
$current_status = '';
$next_status = '';

// Check if the captcha is correct
if ($_POST['captcha_answer'] == $_SESSION['captcha']) {
    try {
        // First, get the current status and delivery_id from the package_status table
        $stmt_package_status = $pdo->prepare("SELECT status, delivery_id FROM package_status WHERE package_status_id = :package_status_id");
        $stmt_package_status->execute([':package_status_id' => $package_status_id]);
        $package_status = $stmt_package_status->fetch(PDO::FETCH_ASSOC);

        // Get the delivery_id from the deliveries table using dr_no
        $stmt_deliveries = $pdo->prepare("SELECT delivery_id FROM deliveries WHERE dr_no = :dr_no");
        $stmt_deliveries->execute([':dr_no' => $dr_no]);
        $delivery_dr = $stmt_deliveries->fetch(PDO::FETCH_ASSOC);

        if ($package_status && $delivery_dr) {
            // Check for data integrity: if the delivery_id from both tables do not match, return an error
            if ($package_status['delivery_id'] !== $delivery_dr['delivery_id']) {
                echo json_encode(["success" => false, "message" => "Data mismatch: Delivery ID from package status does not match delivery ID from DR number."]);
                exit;
            }

            $current_status = $package_status['status'];
            $delivery_id = $package_status['delivery_id'];

            // Use a switch statement to determine the next status
            switch ($current_status) {
                case 'pending':
                    $next_status = 'accepted';
                    break;
                // case 'pending':
                //     $next_status = 'warehouse';
                //     break;
                // case 'warehouse':
                //     $next_status = 'accepted';
                //     break;
                case 'accepted':
                    $next_status = 'delivered';
                    break;
                case 'delivered':
                    // If already delivered, no change is needed.
                    $next_status = 'delivered';
                    break;
                default:
                    // For any unexpected status, keep it as is.
                    $next_status = $current_status;
                    break;
            }

            // Update the deliveries table with the new status and other details
            $stmt = $pdo->prepare("UPDATE deliveries 
                                   SET status = :status,
                                       delivered_date = :delivered_date
                                   WHERE delivery_id = :delivery_id");
            $stmt->execute([
                ':status' => $next_status,
                ':delivered_date' => $delivered_date,
                ':delivery_id' => $delivery_id
            ]);

            // Update the package_status table with the new status
            $stmt = $pdo->prepare("UPDATE package_status 
                                   SET status = :status
                                   WHERE package_status_id = :package_status_id");
            $stmt->execute([
                ':status' => $next_status,
                ':package_status_id' => $package_status_id
            ]);

            // Redirect to a success page with the updated status
            header('Location: success.php?status=' . urlencode($next_status));
            exit;

        } else {
            // Handle case where package_status_id or dr_no is not found
            echo json_encode(["success" => false, "message" => "Package status or DR number not found."]);
        }

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
} else {
    echo "Captcha failed!";
}

