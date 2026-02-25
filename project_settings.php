<?php
require "template/header.php";
require "config/db.php"; // your PDO connection
require "script/role_auth.php";
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
?>
<?php
$project_id = $_GET['id'] ?? null;

if (!$project_id) {
    die("Project ID is required.");
}

// Fetch AR settings + fallback project name
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(ar.project_name, p.project_name) AS project_name,
        ar.company,
        ar.client,
        COALESCE(ar.display_label, 0) AS display_label,
        COALESCE(ar.display_school_id, 0) AS display_school_id,
        COALESCE(ar.label_school_id, 0) AS label_school_id,
        COALESCE(ar.label_municipality, 0) AS label_municipality,
        COALESCE(ar.label_division, 0) AS label_division,
        COALESCE(ar.label_region, 0) AS label_region
    FROM AR_settings ar
    LEFT JOIN projects p ON p.project_id = ar.project_id
    WHERE ar.project_id = ?
");
$stmt->execute([$project_id]);
$arSettings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$arSettings) {
    die("No AR settings found for this project.");
}
?>
  <div class="container mt-4">
    <h2 class="mb-4">Project Settings</h2>

<form method="POST" action="update_ar_settings.php">

    <input type="hidden" name="project_id" value="<?= $project_id ?>">

    <!-- AR Settings -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">AR Settings</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Project Name:</label>
                <input type="text" name="project_name" class="form-control" value="<?= htmlspecialchars($arSettings['project_name']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Company:</label>
                <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($arSettings['company']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Client:</label>
                <input type="text" name="client" class="form-control" value="<?= htmlspecialchars($arSettings['client']) ?>">
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="display_label" value="1" <?= (int)$arSettings['display_label'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label">Display Label</label>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="display_school_id" value="1" <?= (int)$arSettings['display_school_id'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label">Display School ID</label>
            </div>
        </div>
    </div>

    <!-- Label Settings -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Label Settings</h5>
        </div>
        <div class="card-body">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="label_school_id" value="1" <?= (int)$arSettings['label_school_id'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label">Display School ID</label>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="label_municipality" value="1" <?= (int)$arSettings['label_municipality'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label">Display Municipality</label>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="label_division" value="1" <?= (int)$arSettings['label_division'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label">Display Division</label>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="label_region" value="1" <?= (int)$arSettings['label_region'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label">Display Region</label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success">Save Settings</button>
</form>
</div>
<br><br>

<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>