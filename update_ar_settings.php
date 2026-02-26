<?php
require "config/db.php";

$project_id = $_POST['project_id'];

$project_name = trim($_POST['project_name']);
$company = trim($_POST['company']);
$client = trim($_POST['client']);

$display_label = isset($_POST['display_label']) ? 1 : 0;
$display_school_id = isset($_POST['display_school_id']) ? 1 : 0;

try {

    $stmt = $pdo->prepare("
        UPDATE AR_settings
        SET 
            project_name = ?,
            company = ?,
            client = ?,
            display_label = ?,
            display_school_id = ?
        WHERE project_id = ?
    ");

    $stmt->execute([
        $project_name,
        $company,
        $client,
        $display_label,
        $display_school_id,
        $project_id
    ]);

    header("Location: project_settings.php?id=$project_id&toast=Settings updated successfully&type=success");
    exit;

} catch (PDOException $e) {

    header("Location: project_settings.php?id=$project_id&toast=Database error occurred&type=danger");
    exit;
}