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

$raw_ids = $_POST['school_ids'] ?? $_GET['school_ids'] ?? '';
if (empty($raw_ids)) die("No School IDs provided.");

$ids = is_string($raw_ids)
    ? array_filter(array_map('trim', explode(',', $raw_ids)))
    : array_filter($raw_ids);
$ids = array_filter($ids, 'is_numeric');
if (empty($ids)) die("Invalid School IDs.");

$project_id = trim($_GET['project_id'] ?? $_POST['project_id'] ?? '');

$placeholders = str_repeat('?,', count($ids) - 1) . '?';

// Build the base query
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
        SUM(pc.qty) as total_qty
    FROM schools_project sp
    INNER JOIN school s          ON s.school_id = sp.school_id
    INNER JOIN deliveries d      ON d.project_id = sp.project_id 
                                AND d.school_id = sp.school_id
    INNER JOIN lot l             ON l.lot_id = d.lot_id
    INNER JOIN package_status ps ON ps.delivery_id = d.delivery_id
    INNER JOIN package p         ON p.package_id = ps.package_id
    INNER JOIN package_content pc ON pc.package_id = p.package_id
    INNER JOIN item i            ON i.item_id = pc.item_id
    WHERE s.school_id IN (" . str_repeat('?,', count($ids) - 1) . "?)
";

// Add project filter only when project_id is provided and not empty
$params = $ids; // school IDs are always bound
if ($project_id !== '') {
    $sql .= " AND sp.project_id = ?";
    $params[] = $project_id;
}

$sql .= "
    GROUP BY 
        s.school_id, l.lot_name, i.item_name, i.unit
    ORDER BY 
        s.school_id, l.lot_name, i.item_name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) die("No data found.");

// Group by school → lot → items
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

// PDF Generation (clean & professional)
$html = "<!DOCTYPE html><html><head><meta charset='utf-8'><style>
    body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th, td { border: 1px solid #000; padding: 8px; }
    .header { background: #f0f0f0; font-weight: bold; text-align: center; }
    .lot-cell { background: #e0e0e0; font-weight: bold; vertical-align: top; text-align:center; vertical-align:middle;}
    .page-break { page-break-after: always; }
</style></head><body>";

$school_count = 0;
$total_schools = count($data);

foreach ($data as $school) {
    $school_count++;
    $i = $school['info'];

    $html .= "<table>
        <tr class='header'>
            <td colspan='4'>SCHOOL: " . htmlspecialchars($i['school_name']) . "</td>
        </tr>
        <tr>
            <td><strong>School ID</strong></td>
            <td colspan='3'>{$i['school_id']}</td>
        </tr>

        <tr>
            <td><strong>Municipality</strong></td>
            <td colspan='3'>" . htmlspecialchars($i['municipality']) . "</td>
        </tr>

        <tr>
            <td><strong>Division</strong></td>
            <td colspan='3'>" . htmlspecialchars($i['division']) . "</td>
        </tr>

        <tr>
            <td><strong>Region</strong></td>
            <td colspan='3'>" . htmlspecialchars($i['region']) . "</td>
        </tr>
    ";

    foreach ($school['lots'] as $lot_name => $items) {
        $itemCount = count($items);
        $firstRow = true;
        
        foreach ($items as $item) {
            $html .= "<tr>";
            
            // First column: LOT name (with rowspan on first item)
            if ($firstRow) {
                $html .= "<td class='lot-cell' rowspan='{$itemCount}'>LOT {$lot_name}</td>";
                $firstRow = false;
            }
            
            // Remaining columns: item details
            $html .= "
                <td>" . htmlspecialchars($item['item_name']) . "</td>
                <td style='text-align:center;'>" . number_format($item['qty']) . "</td>
                <td style='text-align:center;'>" . htmlspecialchars($item['unit']) . "</td>
            </tr>";
        }
    }
    
    $html .= "</table>";
    
    // Add page break except for last school
    if ($school_count < $total_schools) {
        $html .= "<div class='page-break'></div>";
    }
}

$html .= "</body></html>";

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Packing_List_Batch_" . date('Ymd_His') . ".pdf", ["Attachment" => false]);