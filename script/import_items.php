<?php
require "../config/db.php"; // your PDO connection
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        die("Error uploading file.");
    }

    $project_id = $_POST['project_id'] ?? null;
    if (!$project_id) {
        die("Missing project ID.");
    }

    $fileTmpPath = $_FILES['file']['tmp_name'];

    // Open file and process
    if (($handle = fopen($fileTmpPath, "r")) !== false) {
        $row = 0;
        $stmt = $pdo->prepare("INSERT INTO item (project_id, item_name, unit) VALUES (?, ?, ?)");

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($row === 0) {
                // Skip header row
                $row++;
                continue;
            }

            // Columns from CSV
            $item_name = trim($data[0] ?? '');
            $unit = trim($data[1] ?? '');

            if ($item_name === '' || $unit === '') {
                continue; // skip empty rows
            }

            try {
                $stmt->execute([$project_id, $item_name, $unit]);
            } catch (PDOException $e) {
                // log error but continue processing others
                error_log("Import error on row $row: " . $e->getMessage());
            }

            $row++;
        }
        fclose($handle);
        header("Location: ../items.php?id=$project_id&toast=Inserted items successfully&type=success");
    } else {
        die("Unable to open uploaded file.");
    }
} else {
    die("Invalid request.");
}
