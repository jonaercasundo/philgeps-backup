<?php
header('Content-Type: application/json');

require "../config/db.php"; // PDO $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rows'])) {
    $project_id = $_POST['project'] ?? null;
    $rows = $_POST['rows'];

    $stmtDelivery = $pdo->prepare("
        INSERT INTO deliveries (
            project_id, school_id, keystage_id, lot_id, package_type, dr_no, delivery_date
        ) VALUES (
            :project_id, :school_id, :keystage_id, :lot_id, :package_type, :dr_no, :delivery_date
        )
    ");

    // Query for package_id (priority keystage, else lot)
    $stmtPkgByKs = $pdo->prepare("SELECT package_id FROM package WHERE keystage_id = :keystage_id LIMIT 1");
    $stmtPkgByLot = $pdo->prepare("SELECT package_id FROM package WHERE lot_id = :lot_id LIMIT 1");

    // Insert into package_status
    $stmtPkgStatus = $pdo->prepare("
        INSERT INTO package_status (delivery_id, package_id, status)
        VALUES (:delivery_id, :package_id, :status)
    ");

    $inserted = 0;

    try {
        $pdo->beginTransaction();
        $skipped = 0;
        foreach ($rows as $r) {
            // Skip invalid rows (no lot or keystage)
            if (empty($r['keystage_id']) && empty($r['lot_id'])) {
                $skipped++;
                continue;
            }

            $delivery_date = $r['delivery_date'] ?? '0000-00-00';
            if ($delivery_date === '' || $delivery_date === '00-00-0000') {
                $delivery_date = '0000-00-00';
            }
            try {
            // Insert into deliveries
            $stmtDelivery->execute([
                'project_id'    => $project_id,
                'school_id'     => $r['school_id'],
                'keystage_id'   => $r['keystage_id'] ?? null,
                'lot_id'        => $r['lot_id'] ?? null,
                'package_type'  => $r['package_type'],
                'dr_no'         => $r['dr_no'],
                'delivery_date' => $delivery_date,
            ]);
            
            $delivery_id = $pdo->lastInsertId();

            // Get package_id (try keystage first, else lot)
            $package_id = null;
            if (!empty($r['keystage_id'])) {
                $stmtPkgByKs->execute(['keystage_id' => $r['keystage_id']]);
                $package_id = $stmtPkgByKs->fetchColumn();
            }
            if (!$package_id && !empty($r['lot_id'])) {
                $stmtPkgByLot->execute(['lot_id' => $r['lot_id']]);
                $package_id = $stmtPkgByLot->fetchColumn();
            }

            if ($package_id) {
                // Default status = "Pending" (adjust if you want something else)
                $stmtPkgStatus->execute([
                    'delivery_id' => $delivery_id,
                    'package_id'  => $package_id,
                    'status'      => 'pending'
                ]);
            }
            $inserted++;
            } catch (Exception $e) {
                error_log("Row insert failed: " . $e->getMessage());
                continue;
            }
        }

        $pdo->commit();

        header("Location: ../deliveries.php?toast=$inserted inserted, $skipped skipped&type=warning");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ../deliveries.php?toast=Error saving delivery: " . urlencode($e->getMessage()) . "&type=danger");
        exit;
    }
} else {
    echo "No data received.";
}
