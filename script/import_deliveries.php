<?php
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form values
    $project_id  = $_POST['project'] ?? null;
    $lot_id      = $_POST['lot'] ?? null;
    $packageType = $_POST['package'] ?? null;
    $keystage_id = !empty($_POST['keystage']) ? $_POST['keystage'] : null;

    if (!$project_id || !$lot_id || !$packageType || !isset($_FILES['csv_file'])) {
        die("Missing required fields or file.");
    }

    // Handle uploaded CSV
    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
    if (!file_exists($fileTmpPath)) {
        die("CSV file upload error.");
    }

    $pdo->beginTransaction();
    try {
        // Open CSV
        if (($handle = fopen($fileTmpPath, "r")) !== false) {
            $rowCount = 0;

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $rowCount++;

                // Skip header row if needed
                if ($rowCount === 1) {
                    continue;
                }

                // Extract CSV fields: delivery_date, school_id, dr_no
                $school_id     = !empty($row[0]) ? $row[0] : null;
                $dr_no         = !empty($row[1]) ? $row[1] : " ";
                $delivery_date = !empty($row[2]) ? $row[2] : 0;

                if (!$school_id) {
                    continue; // Skip invalid rows
                }
                // 1. Insert into deliveries
                $stmt = $pdo->prepare("
                    INSERT INTO deliveries (project_id, lot_id, package_type, keystage_id, delivery_date, school_id, dr_no)
                    VALUES (:project_id, :lot_id, :package_type, :keystage_id, :delivery_date, :school_id, :dr_no)
                ");
                $stmt->execute([
                    ':project_id'   => $project_id,
                    ':lot_id'       => $lot_id,
                    ':package_type' => $packageType,
                    ':keystage_id'  => $keystage_id,
                    ':delivery_date'=> $delivery_date,
                    ':school_id'    => $school_id,
                    ':dr_no'        => $dr_no,
                ]);
                $delivery_id = $pdo->lastInsertId();

                // 2. Get package_id
                if ($keystage_id) {
                    $pkgStmt = $pdo->prepare("SELECT package_id FROM package WHERE keystage_id = :keystage_id");
                    $pkgStmt->execute([':keystage_id' => $keystage_id]);
                } else {
                    $pkgStmt = $pdo->prepare("SELECT package_id FROM package WHERE lot_id = :lot_id");
                    $pkgStmt->execute([':lot_id' => $lot_id]);
                }

                $packages = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($packages as $pkg) {
                    $package_id = $pkg['package_id'];

                    // 3. Insert into package_status
                    $psStmt = $pdo->prepare("
                        INSERT INTO package_status (delivery_id, package_id) 
                        VALUES (:delivery_id, :package_id)
                    ");
                    $psStmt->execute([
                        ':delivery_id' => $delivery_id,
                        ':package_id'  => $package_id,
                    ]);
                }
            }
            fclose($handle);
        }

        $pdo->commit();
         header("Location: ../deliveries.php?id=$project_id&toast=Imported Deliveries successfully&type=success");
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
