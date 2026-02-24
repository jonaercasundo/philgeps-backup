<?php
require "config/db.php";

$project_id = $_POST['project_id'];

$display_label = isset($_POST['display_label']) ? 1 : 0;
$display_school_id = isset($_POST['display_school_id']) ? 1 : 0;

$stmt = $pdo->prepare("
    UPDATE AR_settings
    SET display_label = ?, display_school_id = ?
    WHERE project_id = ?
");

$stmt->execute([
    $display_label,
    $display_school_id,
    $project_id
]);

header("Location: project_settings.php?project_id=" . $project_id);
exit;