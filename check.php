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
echo "Session started.<br>";

require 'config/db.php';
echo "Database connected.<br>";

// Get POST data safely
$package_status_id = $_POST['id'] ?? null;
$delivery_id = $_POST['delivery_id'] ?? null;
$delivered_date = date('Y-m-d');
$current_status = '';
$next_status = '';

echo "POST data received: id=$package_status_id, delivery_id=$delivery_id<br>";

// Check captcha
if (!isset($_POST['captcha_answer']) || $_POST['captcha_answer'] != ($_SESSION['captcha'] ?? '')) {
    echo "Captcha failed! Provided: " . ($_POST['captcha_answer'] ?? 'NULL') . ", Expected: " . ($_SESSION['captcha'] ?? 'NULL');
    exit;
}
echo "Captcha passed.<br>";

try {
    // Fetch current package status
    $stmt_package_status = $pdo->prepare("SELECT status, delivery_id FROM package_status WHERE package_status_id = :package_status_id");
    $stmt_package_status->execute([':package_status_id' => $package_status_id]);
    $package_status = $stmt_package_status->fetch(PDO::FETCH_ASSOC);

    if (!$package_status) {
        echo "Package status not found.<br>";
        exit;
    }
    echo "Package status fetched: " . json_encode($package_status) . "<br>";

    if ((string)$package_status['delivery_id'] !== $delivery_id) {
        echo "Data mismatch: Delivery ID from DB=" . $package_status['delivery_id'] . " vs POST=" . $delivery_id . "<br>";
        exit;
    }
    echo "Delivery ID matches.<br>";

    $current_status = $package_status['status'];
    $status_map = [
        'pending' => 'warehouse',
        'warehouse' => 'accepted',
        'accepted' => 'delivered',
        'delivered' => 'delivered'
    ];
    $next_status = $status_map[$current_status] ?? $current_status;
    echo "Next status: $next_status<br>";

    // Handle file uploads
    $uploaded_photos = [];
    if (isset($_FILES['photo_upload']) && !empty($_FILES['photo_upload']['name'][0])) {
        $upload_dir = __DIR__ . '/uploads/'; // Absolute path
        echo "Upload directory: $upload_dir<br>";

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_count = count($_FILES['photo_upload']['name']);
        echo "Number of files: $file_count<br>";

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['photo_upload']['error'][$i] === UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['photo_upload']['name'][$i]);
                $unique_filename = uniqid() . "_" . $original_name;
                $target_file = "uploads/". $unique_filename;

                if (move_uploaded_file($_FILES['photo_upload']['tmp_name'][$i], $target_file)) {
                    $uploaded_photos[] = $target_file;
                    echo "Uploaded file: $target_file<br>";
                } else {
                    echo "Failed to move uploaded file: $original_name<br>";
                }
            } else {
                echo "Upload error for file " . $_FILES['photo_upload']['name'][$i] . ": " . $_FILES['photo_upload']['error'][$i] . "<br>";
            }
        }
    } else {
        echo "No files uploaded.<br>";
    }

    // Update package_status
    $stmt = $pdo->prepare("UPDATE package_status SET status = :status WHERE package_status_id = :package_status_id");
    $stmt->execute([
        ':status' => $next_status,
        ':package_status_id' => $package_status_id
    ]);
    echo "Package status updated.<br>";

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
    echo "Package status check: " . json_encode($row) . "<br>";

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
        echo "Delivery updated.<br>";
    }

    // Insert uploaded photos into DB
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
            echo "Inserted photo into DB: $photo_path<br>";
        }
    }

    // Redirect to success page (absolute URL)
    $protocol = $secure ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    echo "Redirecting to $protocol://$host/philgeps/success.php?status=$next_status<br>";
    exit;

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
    exit;
}
?>
