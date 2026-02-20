<?php
require "../config/db.php"; // PDO $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rows'])) {
    $project_id = $_POST['project'] ?? null;
    $rows = $_POST['rows'];

    // Insert into deliveries
    $stmtDelivery = $pdo->prepare("
        INSERT INTO deliveries (
            project_id, school_id, keystage_id, lot_id, package_type, dr_no, delivery_date
        ) VALUES (
            :project_id, :school_id, :keystage_id, :lot_id, :package_type, :dr_no, :delivery_date
        )
    ");

    // Get all packages for keystage or lot
    $stmtPkgs = $pdo->prepare("
    SELECT package_id
    FROM package
    WHERE 
        (keystage_id = :keystage_id)
        OR (:keystage_id IS NULL AND keystage_id IS NULL AND lot_id = :lot_id)
    ");

    // Insert into package_status
    $stmtPkgStatus = $pdo->prepare("
        INSERT INTO package_status (delivery_id, package_id, status)
        VALUES (:delivery_id, :package_id, :status)
    ");

    $inserted = 0;
    $skipped = 0;

    try {
        $pdo->beginTransaction();

        foreach ($rows as $r) {
            if (empty($r['lot_id'])) {
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
                    'keystage_id'   => $r['keystage_id'] ?: null,
                    'lot_id'        => $r['lot_id'] ?: null,
                    'package_type'  => $r['package_type'],
                    'dr_no'         => $r['dr_no'],
                    'delivery_date' => $delivery_date,
                ]);
                $delivery_id = $pdo->lastInsertId();

                // Fetch all matching packages
                $stmtPkgs->execute([
                    'keystage_id' => !empty($r['keystage_id']) ? $r['keystage_id'] : null,
                    'lot_id'      => $r['lot_id'] ?? null,
                ]);
                $packages = $stmtPkgs->fetchAll(PDO::FETCH_COLUMN);

                foreach ($packages as $pkgId) {
                    $stmtPkgStatus->execute([
                        'delivery_id' => $delivery_id,
                        'package_id'  => $pkgId,
                        'status'      => 'pending',
                    ]);
                }

                $inserted++;
            } catch (Exception $e) {
                throw $e; // Re-throw to outer catch for proper rollback
            }
        }

        $pdo->commit();

        $toastMsg = "$inserted inserted, $skipped skipped";
        $toastType = "success";
        if ($skipped > 0) {
            $toastType = "warning";
        }

        header("Location: ../deliveries.php?toast=" . urlencode($toastMsg) . "&type=$toastType");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ../deliveries.php?toast=" . urlencode("Error: " . $e->getMessage()) . "&type=danger");
        exit;
    }
} else {
    header("Location: ../deliveries.php?toast=" . urlencode("No data received.") . "&type=danger");
    exit;
}
