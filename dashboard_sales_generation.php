<?php 
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

$allowed_roles = ['Super Admin', 'Office Admin'];
redirectIfNotAuthorized($allowed_roles, 'index.php');

$selectedProject = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

try {
    $stmt = $pdo->query("SELECT project_id, project_name FROM projects ORDER BY project_name");
    $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $projectFilter = $selectedProject > 0 ? "WHERE p.project_id = $selectedProject" : "";
    $deliveryProjectFilter = $selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "";
    $selectedDate = $_GET['selectedDate'] ?? date('Y-m-d');

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $schoolTable = in_array('school', $tables) ? 'school' : 'schools';

    // PROJECT SUMMARY //
    $projectStatusQuery = "
        SELECT 
            COUNT(*) AS total,
            CASE p.status
                WHEN 'Pending Evaluation' THEN 'Pending Evaluation'
                WHEN 'For Award' THEN 'For Award'
                WHEN 'For Implementation' THEN 'For Implementation'
                WHEN 'Ongoing' THEN 'Ongoing'
                WHEN 'Delivered' THEN 'Delivered'
                WHEN 'Completed' THEN 'Completed'
                ELSE p.status
            END AS status
        FROM projects p
        GROUP BY 
            CASE p.status
                WHEN 'Pending Evaluation' THEN 'Pending Evaluation'
                WHEN 'For Award' THEN 'For Award'
                WHEN 'For Implementation' THEN 'For Implementation'
                WHEN 'Ongoing' THEN 'Ongoing'
                WHEN 'Delivered' THEN 'Delivered'
                WHEN 'Completed' THEN 'Completed'
                ELSE p.status
            END
        ORDER BY total DESC;
    ";

    $stmt = $pdo->query($projectStatusQuery);
    $projectStatusOverview = $stmt->fetchAll(PDO::FETCH_ASSOC);
     // PROJECT SUMMARY //

    // BUDGET VARIANCE QUERY START
    $opportunityQuery = "
        SELECT 
            project_name,
            contract_amount,
            ABC,
            (ABC - contract_amount) AS variance
        FROM projects
        WHERE status IN ('Pending Evaluation', 'For Award', 'For Implementation')
        ORDER BY ABS(ABC - contract_amount) DESC
    ";

    $stmt = $pdo->query($opportunityQuery);
    $opportunity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // BUDGET VARIANCE QUERY END

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$selectedProjectName = "All Projects";
if ($selectedProject > 0) {
    foreach ($allProjects as $project) {
        if ($project['project_id'] == $selectedProject) {
            $selectedProjectName = $project['project_name'];
            break;
        }
    }
}
?>

<link rel="stylesheet" href="./assets/css/dashboard.css">

<!-- Dashboard Header with Controls -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2>Sales Generation Dashboard</h2>
  <div class="btn-group">
    <button class="btn btn-outline-primary btn-sm" id="resetLayout">
      Reset Layout
    </button>
    <button class="btn btn-outline-primary btn-sm" id="toggleDrag">
      Toggle Drag
    </button>
    <a href="dashboard.php" class="btn btn-outline-primary btn-sm" role="button">
      Back
    </a>
  </div>
</div>

<div class="container-fluid">
  <!-- <a href="dashboard_.php<?= isset($_GET['project_id']) ? '?project_id=' . urlencode($_GET['project_id']) : '' ?>"
      class="text-primary small fw-semibold text-decoration-none d-inline-flex align-items-center gap-1 mt-1 mx-2">
      View Project Performance <i class="bi bi-arrow-right-short fs-5"></i>
  </a> -->
  <div class="row g-4 mb-4" id="draggable-dashboard">
    <div class="col-lg-6 col-md-12 chart-item" data-chart-id="project-status">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Projects Status</h6>
          <div class="d-flex gap-2">
            <a href="report/print_delivery_status.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" 
              class="text-decoration-none text-dark" 
              target="_blank" 
              title="Print Report">
              <i class="bi bi-printer"></i>
            </a>
            <span class="drag-handle text-muted" style="cursor: grab;" title="Drag to reorder">⋮⋮</span>
          </div>
        </div>
        <div class="card-body">
          <canvas id="projectStatusChart" height="340"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6 col-md-12 chart-item" data-chart-id="budget-variance">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Budget Variance</h6>
          <span class="drag-handle text-muted" style="cursor: grab;" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body" style="height: 400px; overflow-y: auto;">
            <canvas id="opportunityChart" width="600" height="340"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>


<!-- Pass PHP data to JavaScript and load the external script -->
<script>
  const phpData = {      
        projectStatusOverview: <?= json_encode($projectStatusOverview) ?>,
        opportunity: <?= json_encode($opportunity) ?>,
    };
</script>

<script src="assets/js/sortable.js"></script>
<script src="assets/js/dashboard_sales_generation.js"></script>

<?php require "template/footer.php"; ?>