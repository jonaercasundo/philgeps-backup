<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';
require '../config/db.php';

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

$id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 1;

try {
    // First try fetching keystages per project
    $stmt = $pdo->prepare("
        SELECT k.keystage_id AS ref_num, k.keystage_num, k.lot_id , l.lot_name, k.description
        FROM keystage k
        JOIN lot l ON k.lot_id = l.lot_id
        WHERE l.project_id = :project_id
    ");
    $stmt->execute(['project_id' => $id]);
    $lotKeystages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reftable = "keystage_id";

    // Fallback to lots if no keystages exist
    if (!$lotKeystages) {
        $stmt = $pdo->prepare("
            SELECT lot_id AS ref_num, lot_name 
            FROM lot
            WHERE project_id = :project_id
        ");
        $stmt->execute(['project_id' => $id]);
        $lotKeystages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $reftable = "lot_id";
    }
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// Start HTML
$html = "<style>
@page { margin: 20mm; }
body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
h2 { text-align: center; margin: 20px 0; }
table { width: 100%; border-collapse: collapse; }
td { text-align: center; vertical-align: top; padding: 10px; border: 1px solid #000; }
img { width: 120px; height: auto; margin-bottom: 5px; }
small { display: block; font-size: 12px; font-weight: bold; }
</style>";


$currentKeystageKey = null;

foreach ($lotKeystages as $lotKeystage) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM package WHERE $reftable = :ref");
        $stmt->execute(['ref' => $lotKeystage['ref_num']]);
        $packageRefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$packageRefs) {
            continue; // Skip if no packages
        }

        $packageCount = count($packageRefs);

        // Unique key for current keystage
        $keystageKey = isset($lotKeystage['keystage_id']) 
            ? $lotKeystage['keystage_id'] 
            : $lotKeystage['ref_num']; // fallback for lot

        // Add page break if it's a new keystage (not the first)
        if ($currentKeystageKey !== null && $keystageKey !== $currentKeystageKey) {
            $html .= "<div style='page-break-after:always;'></div>";
        }

        // Set group title
        $groupTitle = isset($lotKeystage['keystage_num']) 
            ? "Lot " . $lotKeystage['lot_name'] . " — Keystage " . $lotKeystage['keystage_num'] . " " . $lotKeystage['description']
            : "Lot " . $lotKeystage['lot_name'];

        $html .= "<h2>$groupTitle</h2><table>";

        $col = 0;
        $packageIndex = 1;
        foreach ($packageRefs as $packageRef) {
            if ($col % 2 === 0) $html .= "<tr>";

            $desc = isset($packageRef['description']) ? strtok($packageRef['description'], ' ') : '';

            $qr = Builder::create()
                ->writer(new PngWriter())
                ->data(json_encode([
                    'action' => 'addPackage',
                    'package' => $packageRef['package_id'],
                    'qty' => 1
                ]))
                ->size(150)
                ->margin(0)
                ->build();

            $qrData = base64_encode($qr->getString());

            $html .= "
                <td>
                    <img src='data:image/png;base64,{$qrData}'><br>
                    <small>Package $packageIndex of $packageCount</small>
                </td>
            ";

            $col++;
            $packageIndex++;

            if ($col % 2 === 0) $html .= "</tr>";
        }

        if ($col % 2 !== 0) $html .= "<td></td></tr>";

        $html .= "</table>";

        $currentKeystageKey = $keystageKey;

    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}


// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("deliveries_{$id}.pdf", ["Attachment" => false]);
