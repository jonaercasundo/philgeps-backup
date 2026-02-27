<?php
require "config/db.php";

$project_id = $_POST['project_id'];

$project_name = trim($_POST['project_name']);
$company      = trim($_POST['company']);
$client       = trim($_POST['client']);
$ar_company_footer = trim($_POST['ar_company_footer'] ?? '');
$ar_address_footer = trim($_POST['ar_address_footer'] ?? '');
$display_label      = isset($_POST['display_label']) ? 1 : 0;
$display_school_id  = isset($_POST['display_school_id']) ? 1 : 0;
$ar_contact_footer = trim($_POST['ar_address_footer'] ?? '');
$label_school_id    = isset($_POST['label_school_id']) ? 1 : 0;
$label_municipality = isset($_POST['label_municipality']) ? 1 : 0;
$label_division     = isset($_POST['label_division']) ? 1 : 0;
$label_region       = isset($_POST['label_region']) ? 1 : 0;

// --- Handle Logo ---
$logoName = $_POST['ar_logo'] ?? 'logo.webp'; // default from dropdown

if (!empty($_FILES['new_logo']['name']) && $_FILES['new_logo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/assets/uploads/logo/";
    $fileTmp   = $_FILES['new_logo']['tmp_name'];
    $fileName  = time() . "_" . basename($_FILES['new_logo']['name']);
    $fileExt   = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowed = ['png', 'jpg', 'jpeg', 'webp'];

    if (in_array($fileExt, $allowed)) {
        if (move_uploaded_file($fileTmp, $uploadDir . $fileName)) {
            $logoName = $fileName; // override dropdown
        }
    }
}

try {

    $stmt = $pdo->prepare("
        UPDATE AR_settings
        SET 
            project_name = ?,
            company = ?,
            client = ?,
            ar_company_footer = ?,
            ar_address_footer = ?,
            display_label = ?,
            display_school_id = ?,
            label_school_id = ?,
            label_municipality = ?,
            label_division = ?,
            label_region = ?,
            ar_logo = ?,
            ar_contact_footer = ?
        WHERE project_id = ?
    ");

    $stmt->execute([
        $project_name,
        $company,
        $client,
        $ar_company_footer,
        $ar_address_footer,
        $display_label,
        $display_school_id,
        $label_school_id,
        $label_municipality,
        $label_division,
        $label_region,
        $logoName,
        $ar_contact_footer,
        $project_id
    ]);

    header("Location: project_settings.php?id=$project_id&toast=Settings updated successfully&type=success");
    exit;

} catch (PDOException $e) {

    header("Location: project_settings.php?id=$project_id&toast=Database error occurred&type=danger");
    exit;
}