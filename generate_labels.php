<?php
session_start();
set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/vendor/autoload.php';
require 'config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true, 'dpi' => 120]);

// --- SCHOOL IDs ---
$raw_ids = $_POST['school_ids'] ?? $_GET['school_ids'] ?? '';
if (empty($raw_ids)) die("No School IDs provided.");

$ids = is_string($raw_ids)
    ? array_filter(array_map('trim', explode(',', $raw_ids)))
    : array_filter($raw_ids);
if (empty($ids)) die("Invalid School IDs.");

// --- Get project_id from schools_project ---
$stmtProject = $pdo->prepare("
    SELECT project_id 
    FROM schools_project 
    WHERE school_id = ?
    LIMIT 1
");
$stmtProject->execute([$ids[0]]); // use first school ID
$project_id = $stmtProject->fetchColumn();

if (!$project_id) {
    die("No project found for selected school.");
}

$stmtSettings = $pdo->prepare("
    SELECT label_school_id, label_municipality, label_division, label_region 
    FROM AR_settings 
    WHERE project_id = ?
    LIMIT 1
");
$stmtSettings->execute([$project_id]);
$arSettings = $stmtSettings->fetch(PDO::FETCH_ASSOC);

// Default all to false
$showSchoolID     = false;
$showMunicipality = false;
$showDivision     = false;
$showRegion       = false;

if ($arSettings) {
    $showSchoolID     = (int)$arSettings['label_school_id'] === 1;
    $showMunicipality = (int)$arSettings['label_municipality'] === 1;
    $showDivision     = (int)$arSettings['label_division'] === 1;
    $showRegion       = (int)$arSettings['label_region'] === 1;
}

// --- Prepare SQL ---
$placeholders = str_repeat('?,', count($ids) - 1) . '?';

$sql = "
    SELECT DISTINCT
        s.school_id,
        s.school_name,
        s.municipality,
        s.division,
        s.region,
        l.lot_name,
        i.item_name,
        i.unit,
        SUM(pc.qty * d.package_qty) as total_qty
    FROM schools_project sp
    INNER JOIN school s          ON s.school_id = sp.school_id
    INNER JOIN deliveries d      ON d.project_id = sp.project_id 
                                AND d.school_id = sp.school_id
    INNER JOIN lot l             ON l.lot_id = d.lot_id
    INNER JOIN package_status ps ON ps.delivery_id = d.delivery_id
    INNER JOIN package p         ON p.package_id = ps.package_id
    INNER JOIN package_content pc ON pc.package_id = p.package_id
    INNER JOIN item i            ON i.item_id = pc.item_id
    WHERE s.school_id IN (" . $placeholders . ")
";

$params = $ids;
if ($project_id !== '') {
    $sql .= " AND sp.project_id = ?";
    $params[] = $project_id;
}

$sql .= "
    GROUP BY 
        sp.batch_id, l.lot_name, i.item_name, i.unit
    ORDER BY 
        sp.batch_id, l.lot_name, i.item_name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) die("No data found.");

// --- Group by school → lot → items ---
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
    $data[$sid]['lots'][$lot][] = [
        'item_name' => $row['item_name'],
        'qty'       => (int)$row['total_qty'],
        'unit'      => $row['unit']
    ];
}

// --- PDF Generation ---
$html = "<!DOCTYPE html><html><head><meta charset='utf-8'><style>
body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
th, td { border: 1px solid #000; padding: 8px; }
.header { background: #f0f0f0; font-weight: bold; text-align: center; }
.lot-cell { background: #e0e0e0; font-weight: bold; text-align:center; vertical-align:middle;}
.page-break { page-break-after: always; }
</style></head><body>";

$school_count = 0;
$total_schools = count($data);

foreach ($data as $school) {
    $school_count++;
    $i = $school['info'];

    $html .= "<table>
        <tr class='header'>
            <td colspan='4'>DISTRICT: " . htmlspecialchars($i['school_name']) . "</td>
        </tr>";

   // if ($showSchoolID) {
      //  $html .= "<tr>
       //     <td><strong>School ID</strong></td>
       //     <td colspan='3'>" . htmlspecialchars($i['school_id']) . "</td>
      //  </tr>";
   // }
   // if ($showMunicipality) {
      //  $html .= "<tr>
         //   <td><strong>Municipality</strong></td>
         //   <td colspan='3'>" . htmlspecialchars($i['municipality']) . "</td>
       // </tr>";
   // }
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

        $itemCount = count($items);
        $firstRow = true;

        foreach ($items as $item) {

            $qty = (int)$item['qty'];

            $html .= "<tr>";

            if ($firstRow) {
                $html .= "<td class='lot-cell' rowspan='{$itemCount}'>LOT {$lot_name}</td>";
                $firstRow = false;
            }

            $html .= "
                <td>" . htmlspecialchars($item['item_name']) . "</td>
                <td style='text-align:center;'>" . number_format($qty) . "</td>
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

// --- Render PDF ---
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Packing_List_Batch_" . date('Ymd_His') . ".pdf", ["Attachment" => false]);