<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/vendor/autoload.php';
require 'config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$logoPath = __DIR__ . "/assets/uploads/logo/logo.webp";
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
try {


    $stmt = $pdo->prepare("
    SELECT d.*, d.project_id, p.project_name, k.keystage_num, k.description, s.school_name, s.address, l.contract_no, l.lot_name
    FROM deliveries d 
    JOIN school s ON s.school_id = d.school_id 
    JOIN keystage k ON k.keystage_id = d.keystage_id 
    JOIN lot l ON l.lot_id = k.lot_id
    JOIN projects p ON p.project_id = d.project_id 
    WHERE d.delivery_id=$id;
    ");
    $stmt->execute();
    $deliveries = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT p.*, pc.item_id, pc.qty, i.item_name
    FROM package p
    JOIN package_content pc ON pc.package_id = p.package_id
    JOIN item i ON i.item_id = pc.item_id
    WHERE keystage_id = ?");
    $stmt->execute([$deliveries['keystage_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT *
    FROM package_status
    WHERE delivery_id = $id");
    $stmt->execute();
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT count(package_id) as package_count
    FROM package_status
    WHERE delivery_id = $id");
    $stmt->execute();
    $package_count = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }


use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

$qrs = []; // initialize array
$int = 1;
foreach ($packages as $package) {
    $url = "http://192.168.0.63/philgeps/scan.php?id=" . $package['package_status_id'];
    $orderId = "Package $int out of ".$package_count['package_count']."<br> ORD-" . str_pad($package['package_status_id'], 5, "0", STR_PAD_LEFT);

    // Generate QR
    $qr = Builder::create()
        ->writer(new PngWriter())
        ->data($url)
        ->size(150)
        ->margin(0)
        ->build();

    $qrBase64 = 'data:image/png;base64,' . base64_encode($qr->getString());

    // store as array entry with orderId
    $qrs[] = [
        'orderId' => $orderId,
        'qr' => $qrBase64
    ];
    $int++;
}
// CSS + HTML
$itemHolder = ""; // initialize as string

$packages = [];
foreach ($items as $item) {
    $packages[$item['package_id']][] = $item;
}

$packageCount = count($packages);
$itemHolder = "";
$index = 1;
foreach ($packages as $packageId => $packageItems) {
    $itemHolder .= "
        <tr><th colspan='2'>Package {$index} of {$packageCount}</th></tr>
    ";

    foreach ($packageItems as $item) {
        $itemHolder .= "
            <tr>
                <td>{$item['item_name']}</td>
                <td>{$item['qty']}</td>
            </tr>
        ";
    }

    $index++;
}

$html = "
<style>
@page {
  margin: 20mm;
}
body {
    font-family: Arial, sans-serif;
    font-size: 12px;
    margin: 0;
    padding: 0;
}
.label {
    width: 100%;
    height:100%;
}
.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  text-align: center;
  font-size: 10px;
  padding-top: 5px;
}
.header-table {
    padding-bottom: 8px;
}

.order-info, .project-text {
    font-size: 13px;
    font-weight: bold;
    
}
.project-text{
    width:50px;
    vertical-align: top
}

.order-info{
    width:100%;
}

.qr img {
    border: 1px solid #000;
    padding: 2px;
}

.section {
    border: 1px solid #000;
    margin-bottom: 8px;
    padding: 6px;
}

.section-title {
    background: #000;
    color: #fff;
    font-size: 11px;
    font-weight: bold;
    padding: 2px 5px;
    display: inline-block;
    margin-bottom: 4px;
}
.packing-list {
    padding-top: 6px;
    font-size: 11px;
}
table.packing-list {
    border-collapse: collapse;
    width: 100%;
    font-size: 12px;
}
table.packing-list th, 
table.packing-list td {
    border: 1px solid #000;
    padding: 6px 8px;
}
table.packing-list th {
    kground-color: #f2f2f2;
    xt-align: left;
}
table.packing-list td {
    cal-align: top;
}

.logo {
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    width: 100%;
    background: #fff;
}

.logo .logoimg {
    max-width: 250px; 
    height: 80px;
}

.logo .qrimg {
    width: 70px;
    height: auto;
    margin-top:25px;
    margin-left:112px;
    padding: 5px;
    border-radius: 6px;
    justify-self: end; /* stick to right */
}

.qrcontainer {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* 2 columns */
    gap: 20px;
    justify-items: center;
    align-items: center;
    margin-top: 20px;
}
.qrbox {
    text-align: center;
}
.qrbox img {
    width: 120px; /* adjust QR size */
    height: auto;
    margin-bottom: 5px;
}
.qrbox small {
    display: block;
    font-size: 12px;
    font-weight: bold;
}

    
</style>
<!-- PAGE 1 : AR -->
<div class='label'>
<div>
    <div class='logo' style='width:100%; text-align:center;'>
        <img class='logoimg' src='$logoBase64'>
    </div><br>
    <div class='date' style='text-align:right;'>
    <small style='right:0;'>Date: ".$deliveries['delivery_date']."</small><br><br>
    </div>
    <!-- HEADER -->
    <table class='header-table' width='100%' cellspacing='0' cellpadding='4'>
    <tr>
        <td style='width:80px; font-size:13px; font-weight:bold; vertical-align: top;'>Project:</td>
        <td style='font-size:13px; font-weight:bold; vertical-align: top;'>
        ".ucfirst($deliveries['project_name'])."
        </td>
    </tr>
    </table>

    <!-- title -->
    <h3 style='border-top: 2px solid #000; padding-top:10px; text-align:center;'>ACKNOWLEDGEMENT OF RECEIPT OF GOODS</h3>

    <p>
    The undersigned hereby acknowledges the receipt of goods pursuant to Contract No. ".$deliveries['contract_no']." (LOT ".$deliveries['lot_name'].") between METRO MOBILIA CORPORATION and DEPARTMENT OF EDUCATION-CENTRAL OFFICE.<br><br>
    Furthermore, the undersigned acknowledges that after due inspection and examination of the procuring party, the goods are all received in good condition, fully compliant with all the technical specifications agreed upon, sans any defect and in proper quantity and quality.<br><br> 
    School Destination: ".$deliveries['address']."<br>
    School ID: ".$deliveries['school_id']."
    </p>

    <!-- PACKING LIST -->
    <div class='packing-list'>
      <b>Packing List</b><br>
        <table class='packing-list'>
            <thead>
            </thead>
            <tbody>
                $itemHolder
            </tbody>
        </table>
    </div>
</div>

<div class='footer'>
  <table width='100%' cellspacing='0' cellpadding='4' style='text-align:center;'>
      <tr>
          <td>Printed Name Over Signature</td>
          <td>".$_SESSION['name']."<br>Metro Mobilia Corporation</td>
      </tr>
  </table>
  <br>
  <small>
    Unit B 15th Floor Asian Star Building, Asean Drive Corner Singapura Lane, 
    Filinvest Corporate City, Alabang, Muntinlupa City 1781, Philippines<br>
    T: +632.8821.7261 | F: +632.8821.7097
  </small>
</div>
</div>
<!-- PAGE BREAK -->
<div style='page-break-after: always;'></div>

<!-- PAGE 2 : QR CODES ONLY -->
<table width='100%' cellspacing='0' cellpadding='10'>
";
$col = 0;
foreach($qrs as $q) {
    if ($col % 2 == 0) {
        $html .= "<tr>";
    }

    $html .= "
        <td align='center' style='border:1px solid #000; padding:10px;'>
            <img src='".$q['qr']."' style='width:120px; height:auto;'><br>
            <small style='font-size:12px; font-weight:bold;'>".$q['orderId']."</small>
        </td>
    ";

    if ($col % 2 == 1) {
        $html .= "</tr>";
    }

 $col++;
}
// close last row if odd
if ($col % 2 != 0) {
    $html .= "<td></td></tr>";
}
$html .= "</table>";


// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'Portrait');
$dompdf->render();
$dompdf->stream("label_$orderId.pdf", ["Attachment" => false]);
