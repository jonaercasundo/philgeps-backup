<?php 
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

$allowed_roles = ['Super Admin', 'Office Admin'];
redirectIfNotAuthorized($allowed_roles, 'index.php');

$selectedProject = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$selectedDate = $_GET['selectedDate'] ?? date('Y-m-d');

try {
    $stmt = $pdo->query("SELECT project_id, project_name FROM projects ORDER BY project_name");
    $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get cash flow data
    $cashFlowQuery = "
        SELECT 
            DATE_FORMAT(d.delivered_date, '%Y-%m') AS month,
            SUM(i.price * pc.qty) AS total_revenue,
            SUM(i.supplier_price * pc.qty) AS total_cost,
            SUM((i.price - i.supplier_price) * pc.qty) AS total_profit,
            COUNT(*) AS total_deliveries
        FROM deliveries d
        JOIN package_status ps ON d.delivery_id = ps.delivery_id
        JOIN package_content pc ON ps.package_id = pc.package_id  
        JOIN item i ON pc.item_id = i.item_id
        WHERE d.delivered_date IS NOT NULL
            AND d.status <> 'pending'
            " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY month
        ORDER BY month;
    ";
    $stmt = $pdo->query($cashFlowQuery);
    $cashFlow = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
<!-- Dashboard Header with Controls -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2>Sales Generation Dashboard</h2>
</div>
<div class="container-fluid">
  <!-- Additional Charts Section -->
  <div class="row g-4 mb-4">

    <!-- Project Filter Inside the Card -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="card shadow-sm border-0 bg-light">
          <div class="card-body py-2">
            <h6 class="card-title mb-2">Filter by Project</h6>
            <form method="GET" id="projectFilterForm">
              <div class="input-group input-group-sm">
                <select class="form-select form-select-sm" name="project_id" id="projectSelect" onchange="this.form.submit()">
                  <option value="0" <?= $selectedProject == 0 ? 'selected' : '' ?>>All Projects</option>
                  <?php foreach($allProjects as $project): ?>
                    <option value="<?= $project['project_id'] ?>" <?= $selectedProject == $project['project_id'] ? 'selected' : '' ?>>
                      <?php 
                      $name = htmlspecialchars($project['project_name']);
                      echo strlen($name) > 30 ? substr($name, 0, 80) . '…' : $name; 
                      ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if($selectedProject > 0): ?>
                  <a href="?" class="btn btn-outline-secondary btn-sm">
                    ❌ Clear
                  </a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Cash Flow Charts -->
    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">💰 Cash flow</h6>
          <a href="report/print_cashflow.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a>
        </div>
        <div class="card-body">
          <canvas id="revenueChart" height="250"></canvas>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const phpData = {
        cashFlow: <?= json_encode($cashFlow) ?>,
        selectedProjectName: <?= json_encode($selectedProjectName) ?>
    };
</script>

<script src="assets/js/charts_sales.js"></script>

<?php require "template/footer.php"; ?>