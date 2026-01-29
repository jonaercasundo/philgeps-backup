<?php
session_start();
header('Content-Type: application/json');
require "../config/db.php";

$keystage = isset($_POST['keystage']) ? $_POST['keystage'] : 0;

// Set ref_no to NULL if empty or not set
$ref_no = !empty($_POST['ref_no']) ? $_POST['ref_no'] : null;
$status = !empty($_POST['status']) ? $_POST['status'] : 'Pending'; // Default to Pending if not provided

try {
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

    $sales_stmt = $pdo->prepare(
        "INSERT INTO sales_generation (project_id, net_sales, cogs, total_cost_of_sales, pgp, gpm, opex, ppl, npm)
        VALUES (?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00)"
    );
    $sales_stmt->execute([$project_id]);

    $stmt = $pdo->prepare("INSERT INTO activity_logs
        (user_id, action)
        VALUES (?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['name'] . " Added Project " . $_POST['project_name']
    ]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
