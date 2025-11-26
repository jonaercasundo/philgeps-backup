<?php
session_start();
set_time_limit(0);
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/vendor/autoload.php';
require 'config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set([
    'isRemoteEnabled' => true,
    'isHtml5ParserEnabled' => true,
    'dpi' => 72
]);

// ---- Handle both POST and GET safely ----
$raw_ids = $_POST['school_ids'] ?? $_GET['school_ids'] ?? '';

if (empty($raw_ids)) {
    die("No School IDs provided. Use ?school_ids=134969,134970 or send via POST.");
}

// Normalize to array
if (is_string($raw_ids)) {
    $ids = array_filter(array_map('trim', explode(',', $raw_ids)));
} elseif (is_array($raw_ids)) {
    $ids = array_filter(array_map('trim', $raw_ids));
} else {
    die("Invalid data format for School IDs.");
}

// Ensure all numeric
$ids = array_filter($ids, fn($id) => is_numeric($id));

if (empty($ids)) {
    die("Invalid or empty School IDs provided.");
}

// ---- Continue your logic ----
$logoPath = __DIR__ . "/assets/uploads/logo/logo.webp";
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

$html = "<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <style>
    @page { margin: 20mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
    .header-table { padding-bottom: 8px; }
    .packing-list { padding-top: 6px; font-size: 11px; }
    table.packing-list { border-collapse: collapse; width: 100%; font-size: 12px; }
    table.packing-list th, table.packing-list td { border: 1px solid #000; padding: 6px 8px; }
    .logoimg { max-width: 250px; height: 80px; }
    </style>
</head>
<body>";

$today = date("Y-m-d");
$index = 0;
$total = count($ids);

foreach ($ids as $school_id) {
    $school_id = intval(trim($school_id));
    $index++;
    if (!$school_id) continue;

    // Fetch data for this school
    $stmt = $pdo->prepare("
        SELECT 
            s.school_name,
            s.school_id,
            s.municipality,
            s.division,
            s.region,
            l.lot_name,
            p.package_num,
            i.item_name,
            i.unit,
            pc.qty
        FROM school s
        INNER JOIN deliveries d ON s.school_id = d.school_id
        LEFT JOIN lot l ON d.lot_id = l.lot_id
        INNER JOIN package_status ps ON d.delivery_id = ps.delivery_id
        INNER JOIN package p ON ps.package_id = p.package_id
        INNER JOIN package_content pc ON p.package_id = pc.package_id
        INNER JOIN item i ON pc.item_id = i.item_id
        WHERE s.school_id = :school_id
        ORDER BY l.lot_name, p.package_num, i.item_name
    ");
    $stmt->execute([':school_id' => $school_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$data) continue;

    $first = $data[0];

    // Group data by lot and package
    $grouped = [];
    $package_counts = [];
    
    foreach ($data as $row) {
        $lot_key = $row['lot_name'];
        $package_key = $row['lot_name'] . '|' . $row['package_num'];
        
        if (!isset($grouped[$lot_key])) {
            $grouped[$lot_key] = [
                'lot_name' => $row['lot_name'],
                'packages' => []
            ];
        }
        
        if (!isset($grouped[$lot_key]['packages'][$package_key])) {
            $grouped[$lot_key]['packages'][$package_key] = [
                'package_num' => $row['package_num'],
                'items' => []
            ];
            
            // Count packages per lot
            if (!isset($package_counts[$lot_key])) {
                $package_counts[$lot_key] = 0;
            }
            $package_counts[$lot_key]++;
        }
        
        $grouped[$lot_key]['packages'][$package_key]['items'][] = [
            'item_name' => $row['item_name'],
            'qty' => $row['qty'],
            'unit' => $row['unit']
        ];
    }
    $html .= "
    <table class='packing-list' width='100%'>
        <tr>
            <td colspan='2' style='font-weight:bold; width:20%;'>School</td>
            <td colspan='4' style='text-align:center;'>" . htmlspecialchars($first['school_name'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
            <td colspan='2' style='font-weight:bold;'>School Id</td>
            <td colspan='4' style='text-align:center;'>" . htmlspecialchars($first['school_id'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
            <td colspan='2' style='font-weight:bold;'>Municipality</td>
            <td colspan='4' style='text-align:center;'>" . htmlspecialchars($first['municipality'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
            <td colspan='2' style='font-weight:bold;'>Division</td>
            <td colspan='4' style='text-align:center;'>" . htmlspecialchars($first['division'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
        <tr>
            <td colspan='2' style='font-weight:bold;'>Region</td>
            <td colspan='4' style='text-align:center;'>" . htmlspecialchars($first['region'], ENT_QUOTES, 'UTF-8') . "</td>
        </tr>
    </table>";
    // Build content
    foreach ($grouped as $lot_key => $lot_data) {
        $html .= "
    <div>
        <table class='packing-list' width='100%'>";
        
        $package_count = $package_counts[$lot_key];
        $package_index = 1;
        
        foreach ($lot_data['packages'] as $package) {
            // Calculate total items in this package
            $total_qty = array_sum(array_column($package['items'], 'qty'));
            
            $first_item = true;
            foreach ($package['items'] as $item) {
                $html .= "
            <tr>";
                
                if ($first_item) {
                    $html .= "
                <td rowspan='" . count($package['items']) . "' 
                    style=' font-weight:bold; text-align:center;'>
                    LOT {$lot_data['lot_name']}
                </td>";
            $first_item = false;
                }
                
                $html .= "
                <td>" . htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') . "</td>
                <td style='text-align:center;'>" . $item['qty'] . "</td>
                <td style='text-align:center;'>" . htmlspecialchars($item['unit'], ENT_QUOTES, 'UTF-8') . "</td>
            </tr>";
            }
            
            $package_index++;
        }
        
        $html .= "
        </table>
    </div>
  ";
    }
    if ($index < $total) {
        $html .= "<div style='page-break-after: always;'></div>";
    }
}

$html .= "
</body></html>";

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('Legal', 'Portrait');
$dompdf->render();
$dompdf->stream("labels_batch.pdf", ["Attachment" => false]);