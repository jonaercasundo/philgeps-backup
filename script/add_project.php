<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust
if (isset($_POST['keystage'])) {
    $keystage = $_POST['keystage']; // "1"
} else {
    $keystage = 0; // or NULL, depending on your logic
}
try {
    $stmt = $pdo->prepare("INSERT INTO projects 
        (ref_no, agency, project_name, contract_amount, keystage, start_date, end_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['ref_no'],
        $_POST['agency'],
        $_POST['project_name'],
        $_POST['rawNumber'],
        $keystage,
        $_POST['start_date'],
        $_POST['end_date']
    ]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
