<?php
session_start();
header('Content-Type: application/json');
require "../config/db.php";

$keystage = isset($_POST['keystage']) ? $_POST['keystage'] : 0;

$ref_no = !empty($_POST['ref_no']) ? $_POST['ref_no'] : null;
$status = !empty($_POST['status']) ? $_POST['status'] : 'Pending';

try {

    // Start transaction (recommended)
    $pdo->beginTransaction();

    // Insert Project
    $stmt = $pdo->prepare("INSERT INTO projects
        (ref_no, agency, project_name, contract_amount, keystage, start_date, end_date, ABC, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $ref_no,
        $_POST['agency'],
        $_POST['project_name'],
        $_POST['rawNumber'],
        $keystage,
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['rawNumber2'],
        $status
    ]);

    $project_id = $pdo->lastInsertId();

    // ✅ Insert default AR_settings row
    $stmt = $pdo->prepare("INSERT INTO AR_settings (project_id) VALUES (?)");
    $stmt->execute([$project_id]);

    // Insert Activity Log
    $stmt = $pdo->prepare("INSERT INTO activity_logs
        (user_id, action)
        VALUES (?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['name'] . " Added Project " . $_POST['project_name']
    ]);

    $pdo->commit();

    echo json_encode(["success" => true]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}