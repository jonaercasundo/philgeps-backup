<?php
session_start();
set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/vendor/autoload.php';
require 'config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set([
    'isRemoteEnabled' => true,
    'isHtml5ParserEnabled' => true,
    'dpi' => 120
]);

// -------------------------------
// PROJECT ID (FIXED)
// -------------------------------
$project_id = $_GET['project_id'] ?? null;

if (!$project_id || !is_numeric($project_id)) {
    die("Invalid Project ID.");
}

$project_id = (int)$project_id;

// -------------------------------
// SETTINGS
// -------------------------------
$stmtSettings = $pdo->prepare("
    SELECT label_school_id, label_municipality, label_division, label_region
    FROM AR_settings
    WHERE project_id = ?
    LIMIT 1
");
$stmtSettings->execute([$project_id]);
$arSettings = $stmtSettings->fetch(PDO::FETCH_ASSOC);

// Default settings
$showSchoolID = false;
$showMunicipality = false;
$showDivision = false;
$showRegion = false;

if ($arSettings) {
    $showSchoolID     = (int)$arSettings['label_school_id'] === 1;
    $showMunicipality = (int)$arSettings['label_municipality'] === 1;
    $showDivision     = (int)$arSettings['label_division'] === 1;
    $showRegion       = (int)$arSettings['label_region'] === 1;
}

// -------------------------------
// QUERY
// -------------------------------
$sql = "
    SELECT
        s.school_id,
        s.school_name,
        s.municipality,
        s.division,
        s.region,
        sp.batch_id,
        l.lot_name,
        i.item_name,
        i.unit,
        SUM(COALESCE(pc.qty, 1) * COALESCE(d.package_qty, 1)) AS total_qty
    FROM schools_project sp
    LEFT JOIN school s ON s.school_id = sp.school_id
    LEFT JOIN deliveries d ON d.project_id = sp.project_id
        AND d.school_id = sp.school_id
    LEFT JOIN lot l ON l.lot_id = d.lot_id
    LEFT JOIN package_status ps ON ps.delivery_id = d.delivery_id
    LEFT JOIN package p ON p.package_id = ps.package_id
    LEFT JOIN package_content pc ON pc.package_id = p.package_id
    LEFT JOIN item i ON i.item_id = pc.item_id
    WHERE sp.project_id = ?
    GROUP BY
        s.school_id,
        s.school_name,
        s.municipality,
        s.division,
        s.region,
        sp.batch_id,
        l.lot_name,
        i.item_name,
        i.unit
    ORDER BY
        s.school_name,
        sp.batch_id,
        l.lot_name,
        i.item_name
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$project_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    die("No data found.");
}

// -------------------------------
// GROUP DATA
// -------------------------------
$data = [];

foreach ($rows as $row) {
    $sid = $row['school_id'];
    $lot = $row['lot_name'];

    if (!isset($data[$sid])) {
        $data[$sid] = [
            'info' => [
                'school_name'  => $row['school_name'],
                'school_id'    => $row['school_id'],
                'municipality' => $row['municipality'],
                'division'     => $row['division'],
                'region'       => $row['region'],
            ],
            'lots' => []
        ];
    }

    if (!isset($data[$sid]['lots'][$lot])) {
        $data[$sid]['lots'][$lot] = [];
    }

    $key = $row['item_name'];

    if (isset($data[$sid]['lots'][$lot][$key])) {
        $data[$sid]['lots'][$lot][$key]['qty'] += (int)$row['total_qty'];
    } else {
        $data[$sid]['lots'][$lot][$key] = [
            'item_name' => $row['item_name'],
            'qty'       => (int)$row['total_qty'],
            'unit'      => $row['unit'],
        ];
    }
}

// -------------------------------
// PDF GENERATION
// -------------------------------
$html = "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
body { font-family: Arial; font-size: 11px; margin: 15px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
th, td { border: 1px solid #000; padding: 8px; }
.header { background: #f0f0f0; font-weight: bold; text-align: center; }
.lot-cell { background: #e0e0e0; font-weight: bold; text-align:center; vertical-align:middle; }
.page-break { page-break-after: always; }
</style>
</head>
<body>";

$total_schools = count($data);
$school_count = 0;

foreach ($data as $school) {
    $school_count++;
    $i = $school['info'];

    $html .= "<table>
        <tr class='header'>
            <td colspan='4'>DISTRICT: " . htmlspecialchars($i['school_name']) . "</td>
        </tr>";

    if ($showDivision) {
        $html .= "<tr>
            <td><strong>Division</strong></td>
            <td colspan='3'>" . htmlspecialchars($i['division']) . "</td>
        </tr>";
    }

    if ($showRegion) {
        $html .= "<tr>
            <td><strong>Region</strong></td>
            <td colspan='3'>" . htmlspecialchars($i['region']) . "</td>
        </tr>";
    }

    foreach ($school['lots'] as $lot_name => $items) {

        $items = array_values($items);
        $itemCount = count($items);
        $first = true;

        foreach ($items as $item) {
            $html .= "<tr>";

            if ($first) {
                $html .= "<td class='lot-cell' rowspan='{$itemCount}'>LOT " . htmlspecialchars($lot_name) . "</td>";
                $first = false;
            }

            $html .= "<td>" . htmlspecialchars($item['item_name']) . "</td>
                      <td style='text-align:center;'>" . number_format($item['qty']) . "</td>
                      <td style='text-align:center;'>" . htmlspecialchars($item['unit']) . "</td>
                    </tr>";
        }
    }

    $html .= "</table>";

    if ($school_count < $total_schools) {
        $html .= "<div class='page-break'></div>";
    }
}

$html .= "</body></html>";

// -------------------------------
// OUTPUT PDF
// -------------------------------
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Packing_List_Batch_" . date('Ymd_His') . ".pdf", [
    "Attachment" => false
]);