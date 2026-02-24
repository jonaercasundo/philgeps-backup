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
        ar.display_label,
        ar.display_school_id
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

    <!-- AR Settings Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">AR Settings</h5>
        </div>

        <div class="card-body">

            <!-- Project Info -->
            <div class="mb-3">
                <label class="form-label fw-bold">Project Name:</label>
                <p><?= htmlspecialchars($arSettings['project_name']) ?></p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Company:</label>
                <p><?= htmlspecialchars($arSettings['company']) ?></p>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Client:</label>
                <p><?= htmlspecialchars($arSettings['client']) ?></p>
            </div>

            <!-- Checkboxes -->
            <form method="POST" action="update_ar_settings.php">
                <input type="hidden" name="project_id" value="<?= $project_id ?>">

                <div class="form-check mb-2">
                    <input class="form-check-input" 
                           type="checkbox" 
                           name="display_label" 
                           value="1"
                           <?= $arSettings['display_label'] ? 'checked' : '' ?>>
                    <label class="form-check-label">
                        Display Label
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" 
                           type="checkbox" 
                           name="display_school_id" 
                           value="1"
                           <?= $arSettings['display_school_id'] ? 'checked' : '' ?>>
                    <label class="form-check-label">
                        Display School ID
                    </label>
                </div>

                <button type="submit" class="btn btn-success">
                    Save Settings
                </button>
            </form>

        </div>
    </div>

    <!-- Example Section -->
    <div class="card mt-4 shadow-sm">
        <div class="card-header">
            Example Settings
        </div>
        <div class="card-body">
            <ul>
                <li>Example 1</li>
                <li>Example 2</li>
                <li>Example 3</li>
            </ul>
        </div>
    </div>
</div>

<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>