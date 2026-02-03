<?php
session_start();
require "../config/db.php"; // your PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        die("Error uploading file.");
    }

    $fileTmpPath = $_FILES['file']['tmp_name'];

    try {
        // Truncate the sales_generation table to remove all existing records
        $pdo->exec("TRUNCATE TABLE sales_generation");
    } catch (PDOException $e) {
        error_log("Error truncating sales_generation table: " . $e->getMessage());
        die("Error preparing for import: " . $e->getMessage());
    }

    // Open file and process
    if (($handle = fopen($fileTmpPath, "r")) !== false) {
        $row = 0;
        $successCount = 0;
        $errorCount = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($row === 0) {
                // Skip header row
                $row++;
                continue;
            }

            // Columns from CSV
            $projectTitle = trim($data[0] ?? '');
            $abc = trim($data[1] ?? '');
            $contractAmount = trim($data[2] ?? '');
            $netSales = trim($data[3] ?? '');
            $cogs = trim($data[4] ?? '');
            $totalCostOfSales = trim($data[5] ?? '');
            $pgp = trim($data[6] ?? '');
            $gpm = trim($data[7] ?? '');
            $opex = trim($data[8] ?? '');
            $ppl = trim($data[9] ?? '');
            $npm = trim($data[10] ?? '');

            if ($projectTitle === '') {
                $errorCount++;
                $row++;
                continue; // skip empty project title rows
            }

            try {
                // Remove currency symbols and percentage signs
                $abc = preg_replace('/[^0-9.]/', '', $abc);
                $contractAmount = preg_replace('/[^0-9.]/', '', $contractAmount);
                $netSales = preg_replace('/[^0-9.]/', '', $netSales);
                $cogs = preg_replace('/[^0-9.]/', '', $cogs);
                $totalCostOfSales = preg_replace('/[^0-9.]/', '', $totalCostOfSales);
                $pgp = preg_replace('/[^0-9.]/', '', $pgp);
                $gpm = preg_replace('/[^0-9.]/', '', $gpm);
                $opex = preg_replace('/[^0-9.]/', '', $opex);
                $ppl = preg_replace('/[^0-9.]/', '', $ppl);
                $npm = preg_replace('/[^0-9.]/', '', $npm);

                // Convert to decimal numbers
                $abc = (float)$abc;
                $contractAmount = (float)$contractAmount;
                $netSales = (float)$netSales;
                $cogs = (float)$cogs;
                $totalCostOfSales = (float)$totalCostOfSales;
                $pgp = (float)$pgp;
                $gpm = (float)$gpm;
                $opex = (float)$opex;
                $ppl = (float)$ppl;
                $npm = (float)$npm;

                // Since we truncated the table, we only need to insert new records
                $stmt = $pdo->prepare("INSERT INTO sales_generation (
                    project_name,
                    abc,
                    contract_amount,
                    net_sales,
                    cogs,
                    total_cost_of_sales,
                    pgp,
                    gpm,
                    opex,
                    ppl,
                    npm
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$projectTitle, $abc, $contractAmount, $netSales, $cogs, $totalCostOfSales, $pgp, $gpm, $opex, $ppl, $npm]);

                $successCount++;

            } catch (PDOException $e) {
                // log error but continue processing others
                error_log("Import error on row $row: " . $e->getMessage());
                $errorCount++;
            }

            $row++;
        }
        fclose($handle);

        // Redirect back to dashboard with success message
        header("Location: ../dashboard_sales_generation.php?toast=Successfully imported $successCount records&type=success");
    } else {
        die("Unable to open uploaded file.");
    }
} else {
    die("Invalid request.");
}
