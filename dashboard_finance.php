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

    // SALES GENERATION CHARTS //
    // Get cashflow data
    $cashflowQuery = "
        SELECT 
            DATE_FORMAT(g.paid_at, '%Y-%m') AS month,
            SUM(i.price * pc.qty) AS total_income,
            SUM(i.supplier_price * pc.qty) AS total_expense,
            SUM((i.price - i.supplier_price) * pc.qty) AS net_cashflow
        FROM grouping g
        JOIN billing_grouped bg ON g.group_id = bg.group_id
        JOIN deliveries d ON bg.dr_no = d.dr_no
        JOIN package p ON (d.keystage_id = p.keystage_id AND d.lot_id = p.lot_id)
        JOIN package_content pc ON p.package_id = pc.package_id
        JOIN item i ON pc.item_id = i.item_id
        WHERE g.status = 'paid'
            AND g.paid_at IS NOT NULL 
            AND g.paid_at != ''
            AND d.status NOT IN ('pending', 'cancelled')
            " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY DATE_FORMAT(g.paid_at, '%Y-%m')
        ORDER BY month;
    ";
    $stmt = $pdo->query($cashflowQuery);
    $cashflowData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expected vs actual deliveries by warehouse
    $deliveriesByWarehouseQuery = "
        SELECT 
            w.warehouse_id,
            w.warehouse_name,
            
            -- Expected: Deliveries all status
            COUNT(DISTINCT CONCAT_WS('-', d.school_id, d.lot_id)) AS expected_deliveries,
            
            -- Actual: Deliveries with status delivered and accepted
            COUNT(DISTINCT CASE WHEN d.status IN ('delivered', 'accepted') 
                  THEN CONCAT_WS('-', d.school_id, d.lot_id) END) AS actual_deliveries

        FROM warehouse w
        LEFT JOIN logistics_location ll ON w.warehouse_id = ll.warehouse_id
        LEFT JOIN deliveries d ON ll.logistics_location_id = d.logistics_location_id
        " . ($selectedProject > 0 ? " WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY w.warehouse_id, w.warehouse_name;
    ";

    $stmt = $pdo->query($deliveriesByWarehouseQuery);
    $deliveriesByWarehouse = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // SALES GENERATION CHARTS //

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
  <h2>Finance Dashboard</h2>
  <div class="btn-group">
    <a href="dashboard.php" class="btn btn-outline-primary btn-sm" role="button">
      Back
    </a>
    <button class="btn btn-outline-primary btn-sm" id="resetLayout">
      Reset Layout
    </button>
    <button class="btn btn-outline-primary btn-sm" id="toggleDrag">
      Toggle Drag
    </button>
  </div>
</div>

<div class="container-fluid">

  <!-- Project Filter Inside the Card -->
  <div class="card-body p-2">
    <div class="row mb-2">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body py-2">
            <h6 class="card-title mb-2">Filter by Project</h6>
            <form method="GET" id="projectFilterForm">
                <div class="input-group input-group-sm">
                    <select class="form-select form-select-sm" name="project_id" id="projectSelect">
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
  </div>

  <div class="card-body p-2">
    <a href="dashboard_sales.php<?= isset($_GET['project_id']) ? '?project_id=' . urlencode($_GET['project_id']) : '' ?>"
        class="text-primary small fw-semibold text-decoration-none d-inline-flex align-items-center gap-1 mt-1 mx-2">
        View Sales Performance <i class="bi bi-arrow-right-short fs-5"></i>
    </a>
    <div class="row g-3" id="draggable-dashboard">
      <!-- Cashflow Chart - Actual Expense vs Income -->
      <div class="col-lg-12 chart-item" data-chart-id="cashflow-chart">
        <div class="card shadow-sm">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0 fw-bold">Expense vs Income</h6>
              </div>
              
            <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
          </div>
          <div class="card-body">
            <canvas id="cashflowChart" height="300"></canvas>
          </div>
        </div>
      </div>
      <!-- Deliveries by Warehouse Chart -->
      <div class="col-md-12 chart-item" data-chart-id="deliveries-by-warehouse">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0 fw-bold">Expected vs Actual deliveries by Warehouse</h6>
              </div>
            <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
          </div>
          <div class="card-body">
            <canvas id="deliveriesByWarehouseChart" height="300"></canvas>
          </div>
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
        selectedProject: <?= json_encode($selectedProject) ?>,
        cashflowData: <?= json_encode($cashflowData) ?>,
        deliveriesByWarehouse: <?= json_encode($deliveriesByWarehouse) ?>,
    };
</script>


<script src="assets/js/sortable.js"></script>
<script src="assets/js/dashboard_finance.js"></script>

<?php require "template/footer.php"; ?>