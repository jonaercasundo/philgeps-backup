<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/vendor/autoload.php';
require 'config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;

try {
    // Corrected SQL query to join deliveries and keystage tables
    $stmt = $pdo->prepare("
        SELECT d.*, k.keystage_num, k.description
        FROM deliveries d 
        JOIN keystage k ON k.keystage_id = d.keystage_id 
        WHERE d.dr_no = :id
    ");
    $stmt->execute([':id' => $id]);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$deliveries) {
        die("No deliveries found for DR No $id");
    }
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Details - DR No. <?php echo htmlspecialchars($id); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .packing-list { padding-top: 6px; font-size: 11px; }
        table.packing-list { border-collapse: collapse; width: 100%; font-size: 12px; margin-top: 10px; }
        table.packing-list td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        .photo-container { display: flex; flex-wrap: wrap; justify-content: center; padding: 10px; }
        .photo-box { text-align: center; margin: 10px; }
        .photo-box img { max-width: 150px; height: auto; border: 1px solid #ccc; }
    </style>
</head>
<body>


<?php
// Build photo list as a table
foreach ($deliveries as $delivery) {
    // Use delivery_id to fetch all package statuses for this DR
    $stmt = $pdo->prepare("SELECT * FROM package_status WHERE delivery_id = :delivery_id");
    $stmt->execute([':delivery_id' => $delivery['delivery_id']]);
    $package_status_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(package_id) AS package_count FROM package_status WHERE delivery_id = :delivery_id");
    $stmt->execute([':delivery_id' => $delivery['delivery_id']]);
    $package_count = $stmt->fetch(PDO::FETCH_ASSOC)['package_count'];
    
    echo "
    <br>
    <div style='font-weight: bold; font-size: 14px;'>Keystage " . htmlspecialchars($delivery['keystage_num']) . " " . htmlspecialchars($delivery['package_type']) . " " . htmlspecialchars($delivery['description']) . "</div>
    <div class='packing-list'>
        <table class='packing-list'>
            <tbody>";
    
    $int = 1;
    foreach ($package_status_list as $package) {
        // Use package_status_id to fetch photos for this specific package
        $stmt_photos = $pdo->prepare("
            SELECT * FROM delivery_photo 
            WHERE package_status_id = :package_status_id 
            AND status IN ('accepted', 'delivered')
        ");
        $stmt_photos->execute([':package_status_id' => $package['package_status_id']]);
        $package_photos = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);

        // Separate photos by status
        $accepted_photos = array_filter($package_photos, function($photo) {
            return $photo['status'] === 'accepted';
        });
        $delivered_photos = array_filter($package_photos, function($photo) {
            return $photo['status'] === 'delivered';
        });

        // Determine which photos to display based on priority (Delivered > Accepted)
        $photos_to_display = [];
        $status_text = '';

        if (!empty($delivered_photos)) {
            $photos_to_display = $delivered_photos;
            $status_text = 'Delivered';
        } elseif (!empty($accepted_photos)) {
            $photos_to_display = $accepted_photos;
            $status_text = 'Accepted';
        }

        if (empty($photos_to_display)) {
            echo "<tr><td colspan='2' style='text-align:center;'>The package is not yet accepted.</td></tr>";
        } else {
            echo "
            <tr>
                <td colspan='2' style='font-weight:bold;background:#f0f0f0'>
                    <small>Package " . htmlspecialchars($int) . " of " . htmlspecialchars($package_count) . " : " . $status_text . "</small>
                </td>
            </tr>";
            echo "<tr>
                <td colspan='2' style='vertical-align: top;'>
                    <div class='photo-container'>";
            foreach ($photos_to_display as $photo) {
                $photoPath = __DIR__ . "/" . $photo['delivery_photo'];
                if (file_exists($photoPath)) {
                    $photoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($photoPath));
                    echo "
                    <div class='photo-box'>
                        <img src='$photoBase64' alt='" . $status_text . " Photo'>
                    </div>";
                }
            }
            echo "
                    </div>
                </td>
            </tr>";
        }
        $int++;
    }
    echo "
            </tbody>
        </table>
    </div>";
}
?>

</body>
</html>
