<?php
require __DIR__ . '/../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $pdo->beginTransaction();
    try {
        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // skip header row

            list($project, $school, $address, $lot, $keystage, $packageType, $drn, $dateDeliver) = $row;

            $stmt = $pdo->prepare("
                INSERT INTO deliveries (project_id, school_id, address, lot, keystage_id, package_type, dr_no, delivery_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$project, $school, $address, $lot, $keystage, $packageType, $drn, $dateDeliver]);
        }
        $pdo->commit();
        echo "<script>alert('Batch deliveries imported successfully'); window.location='../deliveries.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Import failed: " . $e->getMessage());
    }
}
