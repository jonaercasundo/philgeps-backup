<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';
require '../config/db.php';

use Dompdf\Dompdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

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
td { text-align: center; vertical-align: top; padding: 10px; border: 1px solid #000; height: 150px; }
img { height: 60px; width: auto; max-width: 100%; margin-bottom: 5px; }
small { display: block; font-size: 12px; font-weight: bold; }
</style>";

$currentKeystageKey = null;
$generator = new BarcodeGeneratorPNG();

foreach ($lotKeystages as $lotKeystage) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM package WHERE $reftable = :ref");
        $stmt->execute(['ref' => $lotKeystage['ref_num']]);
        $packageRefs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$packageRefs) {
            continue; // Skip if no packages
        }

        $packageCount = count($packageRefs);

        $keystageKey = isset($lotKeystage['keystage_id']) 
            ? $lotKeystage['keystage_id'] 
            : $lotKeystage['ref_num'];

        if ($currentKeystageKey !== null && $keystageKey !== $currentKeystageKey) {
            $html .= "<div style='page-break-after:always;'></div>";
        }

        $groupTitle = isset($lotKeystage['keystage_num']) 
            ? "Lot " . $lotKeystage['lot_name'] . " — Keystage " . $lotKeystage['keystage_num'] . " " . $lotKeystage['description']
            : "Lot " . $lotKeystage['lot_name'];

        $html .= "<h2>$groupTitle</h2><table>";

        $col = 0;
        $packageIndex = 1;
        foreach ($packageRefs as $packageRef) {
            if ($col % 2 === 0) $html .= "<tr>";

            $barcodeImage = base64_encode($generator->getBarcode($packageRef['package_id'], $generator::TYPE_CODE_128, 2, 50));
            
            $html .= "
                <td>
                    <img src='data:image/png;base64,{$barcodeImage}'><br>
                    <small>Package $packageIndex of $packageCount</small>
                    <small>".$packageRef['package_id']."</small>
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
$dompdf->stream("barcodes_{$id}.pdf", ["Attachment" => false]);
?>
