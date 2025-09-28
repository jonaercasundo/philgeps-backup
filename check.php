<?php
session_start();
require 'config/db.php';

// Get POST data
$package_status_id = $_POST['id'];
$delivery_id = $_POST['delivery_id'];
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

        if (!$package_status) {
            echo json_encode(["success" => false, "message" => "Package status not found."]);
            exit;
        }

        // Check for data integrity
        if ((string)$package_status['delivery_id'] !== $delivery_id) {
            echo json_encode(["success" => false, "message" => "Data mismatch: Delivery ID from package status does not match ID from URL."]);
            exit;
        }

        $current_status = $package_status['status'];

        // Use a switch statement to determine the next status
        switch ($current_status) {
            case 'pending':
                $next_status = 'warehouse';
                break;
            case 'warehouse':
                $next_status = 'accepted';
                break;
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

        // Handle multiple file uploads
        $uploaded_photos = [];
        if (isset($_FILES['photo_upload']) && !empty($_FILES['photo_upload']['name'][0])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Loop through each uploaded file
            $file_count = count($_FILES['photo_upload']['name']);
            for ($i = 0; $i < $file_count; $i++) {
                // Check if there was an upload error for the current file
                if ($_FILES['photo_upload']['error'][$i] === UPLOAD_ERR_OK) {
                    $original_name = basename($_FILES['photo_upload']['name'][$i]);
                    $unique_filename = uniqid() . "_" . $original_name;
                    $target_file = $upload_dir . $unique_filename;

                    // Move the uploaded file
                    if (move_uploaded_file($_FILES['photo_upload']['tmp_name'][$i], $target_file)) {
                        $uploaded_photos[] = $target_file;
                    } else {
                        // Log or handle the error, but don't exit to allow other files to upload
                        error_log("Failed to move uploaded file: " . $original_name);
                    }
                } else {
                    error_log("Upload error for file " . $_FILES['photo_upload']['name'][$i] . ": " . $_FILES['photo_upload']['error'][$i]);
                }
            }
        }

            // 1. Update the package_status table with the new status
        $stmt = $pdo->prepare("UPDATE package_status 
                            SET status = :status 
                            WHERE package_status_id = :package_status_id");
        $stmt->execute([
            ':status' => $next_status,
            ':package_status_id' => $package_status_id
        ]);

        // 2. Check if all package_status rows for this delivery are now equal to $next_status
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

        // 3. If all packages match the same status → update deliveries
        if ($row && $row['total'] == $row['matched']) {
            $stmt_update_delivery = $pdo->prepare("
                UPDATE deliveries 
                SET status = :status,
                    delivered_date = :delivered_date
                WHERE delivery_id = :delivery_id
            ");
            $stmt_update_delivery->execute([
                ':status' => $next_status,
                ':delivered_date' => $delivered_date,
                ':delivery_id' => $delivery_id
            ]);
        }


        // Insert a new row for each uploaded photo into the delivery_photo table
        if (!empty($uploaded_photos)) {
            $stmt_photo = $pdo->prepare("
                INSERT INTO delivery_photo (package_status_id, status, delivery_photo)
                VALUES (:package_status_id, :status, :delivery_photo)
            ");
            foreach ($uploaded_photos as $photo_path) {
                $stmt_photo->execute([
                    ':package_status_id' => $package_status_id,
                    ':status' => $next_status,
                    ':delivery_photo' => $photo_path
                ]);
            }
        }
        
        // Redirect to a success page with the updated status
        header('Location: success.php?status=' . urlencode($next_status));
        exit;

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
        exit;
    }
} else {
    echo "Captcha failed!";
}
?>
