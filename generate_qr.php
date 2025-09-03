<?php
require __DIR__ . '/vendor/autoload.php';
require 'config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$logoPath = __DIR__ . "/assets/uploads/logo/logo.webp";
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
try {


    $stmt = $pdo->prepare("
    SELECT d.*, p.project_id, p.project_name, dp.keystage_id
    FROM deliveries d 
    JOIN projects p ON p.project_id = d.project_id 
    JOIN delivery_packages dp ON dp.delivery_id = d.delivery_id 
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

    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;


$url = "http://localhost/philgeps/scan.php?id=" . $id;

$orderId = "ORD-" . str_pad($id, 5, "0", STR_PAD_LEFT);

// Generate QR
$qr = Builder::create()
    ->writer(new PngWriter())
    ->data($url)
    ->size(150)
    ->margin(0)
    ->build();

$qrBase64 = 'data:image/png;base64,' . base64_encode($qr->getString());

// CSS + HTML
$itemHolder = ""; // initialize as string

foreach ($items as $item) {
    $itemHolder .= "
        <tr>
        <td>".$item['item_name']."</td>
        <td>".$item['qty']."</td>
        </tr>
    ";
}

$html = "
<style>
@page {
  margin: 10mm;
}
body {
    font-family: Arial, sans-serif;
    font-size: 12px;
}
.label {
    border: 2px solid #000;
    padding: 12px;
    width: 480px;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #000;
    padding-bottom: 8px;
    margin-bottom: 8px;
}
.order-info {
    font-size: 13px;
    font-weight: bold;
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
    border-bottom: 2px solid #000;
}

.logo .logoimg {
    max-width: 250px; 
    height: 80px;
}

.logo .qrimg {
    width: 70px;
    height: auto;
    border: 2px solid #000;
    margin-top:25px;
    margin-left:112px;
    padding: 5px;
    border-radius: 6px;
    justify-self: end; /* stick to right */
}


    
</style>

<div class='label'>

    <div class='logo'>
        <img class='logoimg' src='$logoBase64'>
        <img class='qrimg' src='$qrBase64'>
    </div><br>

    <!-- HEADER -->
    <div class='header'>
        <div class='order-info'>
            ".ucfirst($deliveries['project_name'])." <br><br>
            Order ID: $orderId <br>
            ".ucfirst($deliveries['remarks'])." <br><br>
        </div>
    </div>

    <!-- BUYER -->
    <div class='section'>
        <div class='section-title'>BUYER</div><br>
        ".$deliveries['school']."<br>
        ".$deliveries['address']."<br>
    </div>

    <!-- SELLER -->
    <div class='section'>
        <div class='section-title'>SELLER</div><br>
        Metro Mobilia Corporation<br>
        15/F Asian Star Building, Asean Drive Corner, Singapura Lane<br>
        Filinvest City, Alabang, Muntinlupa
    </div>

    <!-- PACKING LIST -->
    <div class='packing-list'>
      <b>Packing List</b><br>
      <table class='packing-list''>
      <tr>
        <th>Item</th><th>Quantity</th>
      </tr>
        $itemHolder
      </table>
    </div>
</div>
";

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'Portrait');
$dompdf->render();
$dompdf->stream("label_$orderId.pdf", ["Attachment" => false]);
