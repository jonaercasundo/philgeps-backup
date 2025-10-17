<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/vendor/autoload.php';
require 'config/db.php';

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

// ---- Handle both POST and GET safely ----

// Accept either comma-separated string or array
$raw_ids = $_POST['ids'] ?? $_GET['ids'] ?? '';

if (empty($raw_ids)) {
    die("No DR numbers provided. Use ?ids=4095,4096 or send via POST.");
}

// Normalize to array
if (is_string($raw_ids)) {
    $ids = array_filter(array_map('trim', explode(',', $raw_ids)));
} elseif (is_array($raw_ids)) {
    $ids = array_filter(array_map('trim', $raw_ids));
} else {
    die("Invalid data format for DR numbers.");
}

// Ensure all numeric
$ids = array_filter($ids, fn($id) => is_numeric($id));

if (empty($ids)) {
    die("Invalid or empty DR numbers provided.");
}

// ---- Continue your logic ----
$logoPath = __DIR__ . "/assets/uploads/logo/logo.webp";
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));


$html = "<style>
@page { margin: 20mm; }
body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
.label { width: 100%; height:100%; }
.footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; padding-top: 5px; }
.header-table { padding-bottom: 8px; }
.order-info, .project-text { font-size: 13px; font-weight: bold; }
.packing-list { padding-top: 6px; font-size: 11px; }
table.packing-list { border-collapse: collapse; width: 100%; font-size: 12px; }
table.packing-list th, table.packing-list td { border: 1px solid #000; padding: 6px 8px; }
.logoimg { max-width: 250px; height: 80px; }
.qrbox img { width: 120px; height: auto; margin-bottom: 5px; }
.qrbox small { display: block; font-size: 12px; font-weight: bold; }
</style>";

foreach ($ids as $id) {
    $id = intval(trim($id));
    if (!$id) continue;

    // Fetch deliveries for this DR
    $stmt = $pdo->prepare("
        SELECT d.*, p.project_name, k.keystage_num, k.description, 
               s.school_name, s.address, l.contract_no, l.lot_name
        FROM deliveries d 
        JOIN school s ON s.school_id = d.school_id 
        JOIN keystage k ON k.keystage_id = d.keystage_id 
        JOIN lot l ON l.lot_id = k.lot_id
        JOIN projects p ON p.project_id = d.project_id 
        WHERE d.dr_no = :id
    ");
    $stmt->execute([':id' => $id]);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$deliveries) continue;

    $first = $deliveries[0];
    $today = date("Y-m-d");

    $allGroups = [];
    $allQrs = [];

    foreach ($deliveries as $delivery) {
        // Get package data
        $stmt = $pdo->prepare("
            SELECT p.package_id, pc.item_id, pc.qty, i.item_name
            FROM package p
            JOIN package_content pc ON pc.package_id = p.package_id
            JOIN item i ON i.item_id = pc.item_id
            WHERE (
                (:keystage_id IS NOT NULL AND p.keystage_id = :keystage_id)
                OR (:keystage_id IS NULL AND p.keystage_id IS NULL AND p.lot_id = :lot_id)
            )
        ");
        $stmt->execute([
            ':keystage_id' => $delivery['keystage_id'],
            ':lot_id'      => $delivery['lot_id']
        ]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get package statuses
        $stmt = $pdo->prepare("SELECT * FROM package_status WHERE delivery_id = :delivery_id");
        $stmt->execute([':delivery_id' => $delivery['delivery_id']]);
        $package_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(package_id) AS package_count FROM package_status WHERE delivery_id = :delivery_id");
        $stmt->execute([':delivery_id' => $delivery['delivery_id']]);
        $package_count = $stmt->fetch(PDO::FETCH_ASSOC)['package_count'];

        $group = [
            'keystage' => $delivery['keystage_num'] . ' ' . $delivery['package_type'] . ' ' . $delivery['description'],
            'packages' => []
        ];

        $int = 1;
        foreach ($package_status as $package) {
            // Items for this package
            $stmt = $pdo->prepare("
                SELECT pc.qty, i.item_name
                FROM package_content pc
                JOIN item i ON i.item_id = pc.item_id
                WHERE pc.package_id = :package_id
            ");
            $stmt->execute([':package_id' => $package['package_id']]);
            $package_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $group['packages'][] = [
                'package_num' => "Package $int of $package_count",
                'items'       => $package_items
            ];

            // Generate QR
            $url = "https://mmc.metro-ltd.com/entry.php?id=" . $package['package_status_id'] . "&delivery_id=" . $delivery['delivery_id'];
            $orderId = "Package $int of $package_count<br> ORD-" . str_pad($package['package_status_id'], 5, "0", STR_PAD_LEFT);

            $qr = Builder::create()
                ->writer(new PngWriter())
                ->data($url)
                ->size(150)
                ->margin(0)
                ->build();

            $allQrs[] = [
                'orderId' => $orderId,
                'qr' => 'data:image/png;base64,' . base64_encode($qr->getString()),
                'keystage' => 'Keystage ' . $delivery['keystage_num'] . " " . strtok($delivery['description'], ' ')
            ];

            $int++;
        }

        $allGroups[] = $group;
    }

    // Build packing list
    $itemHolder = "";
    foreach ($allGroups as $group) {
        $itemHolder .= "<br>Keystage {$group['keystage']}
                            <div class='packing-list'>
                                <table class='packing-list'>";
        foreach ($group['packages'] as $pkg) {
            $itemHolder .= "<tr>
                                <td colspan='2' style='font-weight:bold;background:#f0f0f0'>
                                    <small>{$pkg['package_num']}</small>
                                </td>
                            </tr>";
            foreach ($pkg['items'] as $item) {
                $itemHolder .= "<tr>
                                    <td>{$item['item_name']}</td>
                                    <td width='20px' style='text-align:center;'>".$item['qty']."</td>
                                </tr>";
            }
        }
        $itemHolder .= "</table></div>";
    }

    // PAGE 1 (ARG)
    $today = date("Y-M-d");
$html .= "
<div class='label'>
    <div style='text-align:center;'><img class='logoimg' src='$logoBase64'></div>
    <div style='text-align:right;'><small>Date: $today</small><br><small>AR: ".$delivery['dr_no']."</small></div>
    <table class='header-table' width='100%' cellspacing='0' cellpadding='4'>
        <tr>
            <td style='width:80px; font-size:13px; font-weight:bold;'>Project:</td>
            <td style='font-size:13px; font-weight:bold;'>".ucfirst($first['project_name'])."</td>
        </tr>
    </table>
    <h3 style='border-top:2px solid #000; padding-top:10px; text-align:center;'>ACKNOWLEDGEMENT OF RECEIPT OF GOODS</h3>
    <p>
        The undersigned hereby acknowledges the receipt of goods pursuant to Contract No. {$first['contract_no']} 
        (LOT {$first['lot_name']}) between METRO MOBILIA CORPORATION and DEPARTMENT OF EDUCATION-BUREAU OF LEARNING RESOURCES-CEBU (BLR-CEBU).<br><br>
        School Destination: {$first['address']}<br>
        School ID: {$first['school_id']}
    </p>
    
        
            $itemHolder
        
    <div class='footer'>
        <table width='100%' cellspacing='0' cellpadding='4' style='text-align:center;'>
            <tr>
                <td>Printed Name Over Signature</td>
                <td>{$_SESSION['name']}<br>Metro Mobilia Corporation</td>
            </tr>
        </table>
        <small>Unit B 15th Floor Asian Star Building, Asean Drive Corner Singapura Lane, 
        Filinvest Corporate City, Alabang, Muntinlupa City 1781, Philippines<br>
        T: +632.8821.7261 | F: +632.8821.7097</small>
    </div>
</div>
<div style='page-break-after:always;'></div>";
    // PAGE 2 (QR Codes)
    $html .= "
    <div class='label'>
        <div style='text-align:right;'><small>Date: $today</small><br><small>AR: ".$delivery['dr_no']."</small>
    </div>
    <table width='100%' cellspacing='0' cellpadding='10'>";
    $col = 0;
    foreach ($allQrs as $q) {
        if ($col % 2 == 0) $html .= "<tr>";
        $html .= "

            <td align='center' style='border:1px solid #000; padding:10px;'>
                <img src='{$q['qr']}'><br>
                <small>{$q['orderId']}</small><br>
                <small>{$q['keystage']}</small>
            </td>";
        if ($col % 2 == 1) $html .= "</tr>";
        $col++;
    }
    if ($col % 2 != 0) $html .= "<td></td></tr>";
    $html .= "</table><div style='page-break-after:always;'></div>";
}

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'Portrait');
$dompdf->render();
$dompdf->stream("deliveries_batch.pdf", ["Attachment" => false]);
