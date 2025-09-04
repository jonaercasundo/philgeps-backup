<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id   = $_POST['project'] ?? null;
    $lot_id       = $_POST['lot'] ?? null;
    $keystage     = $_POST['keystage'] ?? null;
    $package_type = $_POST['package_type'] ?? null;
    $dateDeliver  = $_POST['dateDeliver'] ?? null;
    $schools_json = $_POST['schools_json'] ?? "[]";
    $schools      = json_decode($schools_json, true);

    // ✅ Check if schools_json decoded properly
    if (!is_array($schools)) {
        header("Location: ../index.php?toast=Invalid schools data&type=danger");
        exit;
    }

    // Get lot/keystage info safely
    $stmt = $pdo->prepare("
        SELECT k.keystage_num, k.description, l.lot_name 
        FROM keystage k 
        LEFT JOIN lot l ON k.lot_id = l.lot_id 
        WHERE k.keystage_id = ?
    ");
    $stmt->execute([$keystage]);
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project_id || empty($schools)) {
        header("Location: ../index.php?toast=Please select project and schools&type=danger");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Prepare delivery insert
        $stmtDelivery = $pdo->prepare("
            INSERT INTO deliveries 
                (project_id, delivery_date, school, remarks, address) 
            VALUES 
                (:project_id, :delivery_date, :school, :remarks, :address)
        ");

        // Prepare delivery_packages insert
        $stmtPackage = $pdo->prepare("
            INSERT INTO delivery_packages (delivery_id, keystage_id) 
            VALUES (:delivery_id, :keystage_id)
        ");

        foreach ($schools as $school) {
            // Insert delivery
            $stmtDelivery->execute([
                ':project_id'    => $project_id,
                ':delivery_date' => $dateDeliver,
                ':school'        => $school['id'] . " " . $school['name'],
                ':remarks'       => $package_type . " LOT " . $lot['lot_name'] . " KS" . $lot['keystage_num'] . " " . $lot['description'],
                ':address'       => $school['address']
            ]);

            // Get last inserted delivery_id
            $deliveryId = $pdo->lastInsertId();

            // Insert into delivery_packages
            $stmtPackage->execute([
                ':delivery_id' => $deliveryId,
                ':keystage_id' => $keystage
            ]);
        }

        $pdo->commit();
        header("Location: ../deliveries.php?toast=Batch deliveries saved successfully&type=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ../deliveries.php?toast=Error saving batch: " . urlencode($e->getMessage()) . "&type=danger");
        exit;
    }
} else {
    header("Location: ../deliveries.php?toast=Invalid request&type=danger");
    exit;
}
