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
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

// FIX 1: Initialize Dompdf ONCE with options — do NOT re-instantiate later
$options = new Options();
$options->set([
    'isRemoteEnabled'    => true,
    'isHtml5ParserEnabled' => true,
    'dpi'                => 72
]);
$dompdf = new Dompdf($options);

// --- Accept DR numbers (ids) ---
$raw_ids = $_POST['ids'] ?? $_GET['ids'] ?? '';
if (empty($raw_ids)) {
    die("No DR numbers provided. Use ?ids=4095,4096 or send via POST.");
}

if (is_string($raw_ids)) {
    $ids = array_filter(array_map('trim', explode(',', $raw_ids)));
} elseif (is_array($raw_ids)) {
    $ids = array_filter(array_map('trim', $raw_ids));
} else {
    die("Invalid data format for DR numbers.");
}

$ids = array_filter($ids, fn($id) => is_numeric($id));
if (empty($ids)) die("Invalid or empty DR numbers provided.");

// FIX 2: Accept project_id from request to prevent wrong-project matches
// dr_no is NOT unique across projects — must filter by project_id
$project_id = $_POST['project_id'] ?? $_GET['project_id'] ?? null;
if (empty($project_id) || !is_numeric($project_id)) {
    die("No project_id provided. Use ?ids=1&project_id=502703");
}
$project_id = (int)$project_id;

// FIX 5: Guard session name
$signerName = $_SESSION['name'] ?? 'Authorized Representative';

$html = "<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <style>
    @page { margin: 20mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
    .label { width: 100%; height: 100%; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; padding-top: 5px; }
    .header-table { padding-bottom: 8px; }
    .order-info, .project-text { font-size: 13px; font-weight: bold; }
    .packing-list { padding-top: 6px; font-size: 11px; }
    table.packing-list { border-collapse: collapse; width: 100%; font-size: 12px; }
    table.packing-list th, table.packing-list td { border: 1px solid #000; padding: 6px 8px; }
    .logoimg { max-width: 250px; height: 80px; }
    .qrbox img { width: 120px; height: auto; margin-bottom: 5px; }
    .qrbox small { display: block; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>";

foreach ($ids as $id) {
    $id = intval(trim($id));
    if (!$id) continue;

    // FIX 2 (applied): Filter by BOTH dr_no AND project_id to prevent cross-project collisions
    $stmt = $pdo->prepare("
        SELECT d.*, p.project_name, k.keystage_num, k.description,
               s.school_name, s.address, l.contract_no, l.lot_name
        FROM deliveries d
        JOIN school s       ON s.school_id   = d.school_id
        LEFT JOIN keystage k ON k.keystage_id = d.keystage_id
        LEFT JOIN lot l      ON l.lot_id      = COALESCE(k.lot_id, d.lot_id)
        JOIN projects p      ON p.project_id  = d.project_id
        WHERE d.dr_no      = :id
          AND d.project_id = :project_id
    ");
    $stmt->execute([':id' => $id, ':project_id' => $project_id]);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$deliveries) continue;

    $first = $deliveries[0];

    // Fetch AR Settings
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(ar.project_name, p.project_name) AS project_name,
            ar.company,
            ar.client,
            ar.ar_company_footer,
            ar.ar_address_footer,
            ar.ar_contact_footer,
            COALESCE(ar.display_label,     0)          AS display_label,
            COALESCE(ar.display_school_id, 0)          AS display_school_id,
            COALESCE(ar.ar_logo, 'logo.webp')          AS ar_logo
        FROM projects p
        LEFT JOIN AR_settings ar ON ar.project_id = p.project_id
        WHERE p.project_id = ?
    ");
    $stmt->execute([$first['project_id']]);
    $ar = $stmt->fetch(PDO::FETCH_ASSOC);

    // Logo handling
    $arLogoFile = !empty($ar['ar_logo']) ? $ar['ar_logo'] : 'logo.webp';
    $logoPath   = __DIR__ . "/assets/uploads/logo/" . $arLogoFile;
    if (!file_exists($logoPath)) {
        $logoPath = __DIR__ . "/assets/uploads/logo/logo.webp";
    }
    $mimeType    = mime_content_type($logoPath);
    $logoBase64  = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($logoPath));

    // Safe defaults
    $projectName     = $ar['project_name']     ?? $first['project_name'];
    $company         = $ar['company']           ?? '';
    $client          = $ar['client']            ?? '';
    $displayLabel    = (int)($ar['display_label']    ?? 0);
    $displaySchoolId = (int)($ar['display_school_id'] ?? 0);

    $arCompanyFooter = !empty($ar['ar_company_footer'])
        ? htmlspecialchars($ar['ar_company_footer'], ENT_QUOTES, 'UTF-8')
        : 'Metro Mobilia Corporation';

    $arAddressFooter = !empty($ar['ar_address_footer'])
        ? htmlspecialchars($ar['ar_address_footer'], ENT_QUOTES, 'UTF-8')
        : 'Unit B 15th Floor Asian Star Building, Asean Drive Corner Singapura Lane,
           Filinvest Corporate City, Alabang, Muntinlupa City 1781, Philippines';

    $arContactFooter = !empty($ar['ar_contact_footer'])
        ? htmlspecialchars($ar['ar_contact_footer'], ENT_QUOTES, 'UTF-8')
        : 'T: +632.8821.7261 | F: +632.8821.7097';

    $today    = date("Y-M-d");
    $allGroups = [];
    $allQrs    = [];

    foreach ($deliveries as $delivery) {
        // FIX 3: Store multiplier inside each group so it is not stale in the HTML loop
        $multiplier = 1;
        if (!empty($delivery['package_type'])) {
            $numeric    = preg_replace('/[^0-9]/', '', $delivery['package_type']);
            $multiplier = $numeric !== '' ? (int)$numeric : 1;
        }

        // Get package statuses
        $stmt = $pdo->prepare("SELECT * FROM package_status WHERE delivery_id = :delivery_id");
        $stmt->execute([':delivery_id' => $delivery['delivery_id']]);
        $package_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(package_id) AS package_count FROM package_status WHERE delivery_id = :delivery_id");
        $stmt->execute([':delivery_id' => $delivery['delivery_id']]);
        $package_count = $stmt->fetch(PDO::FETCH_ASSOC)['package_count'];

        // FIX 3: multiplier stored in group
        $group = [
            'keystage'   => $delivery['keystage_num']
                ? $delivery['keystage_num'] . ' ' . $delivery['package_type'] . ' ' . ($delivery['description'] ?? '')
                : '',
            'multiplier' => $multiplier,
            'packages'   => []
        ];

        $int = 1;
        foreach ($package_status as $package) {
            // Package dimensions
            $stmt = $pdo->prepare("
                SELECT length, width, height
                FROM package
                WHERE package_id = :package_id
            ");
            $stmt->execute([':package_id' => $package['package_id']]);
            $dimensions = $stmt->fetch(PDO::FETCH_ASSOC);

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
                'dimensions'  => $dimensions,
                'items'       => $package_items
            ];

            // Generate QR
            $url     = "https://mmc.metro-ltd.com/entry.php?id=" . $package['package_status_id'] . "&delivery_id=" . $delivery['delivery_id'];
            $orderId = "Package $int of $package_count<br> ORD-" . str_pad($package['package_status_id'], 5, "0", STR_PAD_LEFT);

            $qr = Builder::create()
                ->writer(new PngWriter())
                ->data($url)
                ->size(150)
                ->margin(0)
                ->build();

            // FIX 4: Use keystage label from data instead of hardcoded Textbook/Teacher's Manual
            $allQrs[] = [
                'orderId'  => $orderId,
                'qr'       => 'data:image/png;base64,' . base64_encode($qr->getString()),
                'keystage' => $delivery['keystage_num']
                    ? 'Keystage ' . $delivery['keystage_num'] . ' ' . strtok($delivery['description'] ?? '', ' ')
                    : ''
            ];

            $int++;
        }

        $allGroups[] = $group;
    }

    // Build packing list HTML
    $itemHolder = "";
    foreach ($allGroups as $group) {
        if (!empty($group['keystage'])) {
            $itemHolder .= "<br>Keystage " . htmlspecialchars($group['keystage']);
        }
        $itemHolder .= "<div class='packing-list'><table class='packing-list'>";

        foreach ($group['packages'] as $pkg) {
            if ($pkg['dimensions'] && isset($pkg['dimensions']['length'])) {
                $l = $pkg['dimensions']['length'] ?? 'N/A';
                $w = $pkg['dimensions']['width']  ?? 'N/A';
                $h = $pkg['dimensions']['height'] ?? 'N/A';
                $dimensionText = "{$l} cm × {$w} cm × {$h} cm";
            } else {
                $dimensionText = "Dimensions: N/A";
            }

            $itemHolder .= "<tr>
                <td style='width:50%;'><small>{$pkg['package_num']}</small></td>
                <td style='width:50%; text-align:center;'><small>{$dimensionText}</small></td>
            </tr>";

            foreach ($pkg['items'] as $item) {
                // FIX 3: use $group['multiplier'] — not the stale outer $multiplier
                $qty = $item['qty'] * $group['multiplier'];
                $itemHolder .= "<tr>
                    <td style='width:80%;'>" . htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') . "</td>
                    <td style='width:20%; text-align:center;'>{$qty}</td>
                </tr>";
            }
        }
        $itemHolder .= "</table></div>";
    }

    // FIX 2 (applied): use $first for school_id and dr_no — NOT $delivery which is stale
    // PAGE 1 — Acknowledgement of Receipt
    $html .= "
<div class='label'>
    <div style='text-align:center;'><img class='logoimg' src='{$logoBase64}'></div>
    <div style='text-align:right;'>
        <small>Date: {$today}</small><br>
        <small>AR: " . htmlspecialchars($first['school_id'], ENT_QUOTES, 'UTF-8') . "</small>
    </div>
    <table class='header-table' width='100%' cellspacing='0' cellpadding='4'>
        <tr>
            <td style='width:80px; font-size:13px; font-weight:bold;'>Project:</td>
            <td style='font-size:13px; font-weight:bold;'>" . ucfirst(htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8')) . "</td>
        </tr>
    </table>
    <h3 style='border-top:2px solid #000; padding-top:10px; text-align:center;'>ACKNOWLEDGEMENT OF RECEIPT OF GOODS</h3>
    <p>
        The undersigned hereby acknowledges the receipt of goods pursuant to Contract No. {$first['contract_no']}"
        . (!empty($first['keystage_num']) ? ' (LOT ' . htmlspecialchars($first['lot_name'], ENT_QUOTES, 'UTF-8') . ')' : '')
        . " between " . htmlspecialchars($company, ENT_QUOTES, 'UTF-8')
        . " and " . htmlspecialchars($client, ENT_QUOTES, 'UTF-8') . ".<br><br>";

    if ($displayLabel === 1) {
        $html .= "School Name: "    . htmlspecialchars($first['school_name'], ENT_QUOTES, 'UTF-8') . "<br>";
        $html .= "School Address: " . htmlspecialchars($first['address'],     ENT_QUOTES, 'UTF-8') . "<br>";

        if ($displaySchoolId === 1) {
            $html .= "School ID: " . htmlspecialchars($first['school_id'], ENT_QUOTES, 'UTF-8') . "<br>";
        }
    }

    $html .= "
    </p>
    {$itemHolder}
    <div class='footer'>
        <table width='100%' cellspacing='0' cellpadding='4' style='text-align:center;'>
            <tr>
                <td>Printed Name Over Signature</td>
                <td>{$signerName}<br>{$arCompanyFooter}</td>
            </tr>
        </table>
        <small>{$arAddressFooter}<br>{$arContactFooter}</small>
    </div>
</div>
<div style='page-break-after:always;'></div>";

    // PAGE 2 — QR Codes
    $html .= "
<div class='label'>
    <div style='text-align:right;'>
        <small>Date: {$today}</small><br>
        <small>DR: " . htmlspecialchars($first['dr_no'], ENT_QUOTES, 'UTF-8') . "</small>
    </div>
    <table width='100%' cellspacing='0' cellpadding='10'>";

    $col = 0;
    foreach ($allQrs as $q) {
        if ($col % 2 === 0) $html .= "<tr>";

        // FIX 4: Use actual keystage label from data, not hardcoded Textbook/Teacher's Manual
        $label = !empty($q['keystage']) ? htmlspecialchars($q['keystage'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($q['orderId'], ENT_QUOTES, 'UTF-8');

        $html .= "<td align='center' style='border:1px solid #000; padding:10px;'>
            <img src='{$q['qr']}'>
            <br><small><b>{$label}</b></small>
        </td>";

        if ($col % 2 === 1) $html .= "</tr>";
        $col++;
    }
    if ($col % 2 !== 0) $html .= "<td></td></tr>";

    $html .= "</table><div style='page-break-after:always;'></div></div>";
}

$html .= "</body></html>";

// FIX 1: Use the $dompdf instance created at the top (with $options) — not a new bare one
$dompdf->loadHtml($html);
$dompdf->setPaper('Legal', 'Portrait');
$dompdf->render();
$dompdf->stream("deliveries_batch.pdf", ["Attachment" => false]);