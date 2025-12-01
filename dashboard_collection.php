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

            // Get income data (from paid deliveries)
    $incomeQuery = "
        SELECT 
            DATE_FORMAT(g.paid_at, '%Y-%m') AS month,
            SUM(i.price * pc.qty) AS total_income,
            SUM((i.price - i.supplier_price) * pc.qty) AS total_profit,
            COUNT(DISTINCT d.delivery_id) AS total_deliveries
        FROM grouping g
        JOIN billing_grouped bg ON g.group_id = bg.group_id
        JOIN deliveries d ON bg.dr_no = d.dr_no
        JOIN package p ON (d.keystage_id = p.keystage_id AND d.lot_id = p.lot_id)
        JOIN package_content pc ON p.package_id = pc.package_id
        JOIN item i ON pc.item_id = i.item_id
        WHERE g.status = 'paid'
            AND g.paid_at IS NOT NULL 
            AND g.paid_at != ''
            AND DATE(g.paid_at) IS NOT NULL
            AND d.status NOT IN ('pending', 'cancelled')
            " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY month
        ORDER BY month;
    ";
    $stmt = $pdo->query($incomeQuery);
    $incomeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expense data (from paid deliveries - cost of goods sold)
    $expenseQuery = "
        SELECT 
            DATE_FORMAT(g.paid_at, '%Y-%m') AS month,
            SUM(i.supplier_price * pc.qty) AS total_expense,
            COUNT(DISTINCT d.delivery_id) AS total_transactions
        FROM grouping g
        JOIN billing_grouped bg ON g.group_id = bg.group_id
        JOIN deliveries d ON bg.dr_no = d.dr_no
        JOIN package p ON (d.keystage_id = p.keystage_id AND d.lot_id = p.lot_id)
        JOIN package_content pc ON p.package_id = pc.package_id
        JOIN item i ON pc.item_id = i.item_id
        WHERE g.status = 'paid'
            AND g.paid_at IS NOT NULL 
            AND g.paid_at != ''
            AND DATE(g.paid_at) IS NOT NULL
            AND d.status NOT IN ('pending', 'cancelled')
            " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY month
        ORDER BY month;
    ";
    $stmt = $pdo->query($expenseQuery);
    $expenseData = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <h2>Collection Dashboard</h2>
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
    <!-- Cashflow Chart - Actual Expense vs Income -->
    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">💰 Cashflow - Actual Expense vs Income (Timeline)</h6>
        </div>
        <div class="card-body">
          <canvas id="cashflowChart" height="300"></canvas>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0"> Income & Profit Overview</h6>
        </div>
        <div class="card-body">
          <canvas id="incomeChart" height="300"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0"> Expense Overview</h6>
        </div>
        <div class="card-body">
          <canvas id="expenseChart" height="300"></canvas>
        </div>
      </div>
    </div>


  </div>
</div>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const phpData = {
        cashflowData: <?= json_encode($cashflowData) ?>,
        incomeData: <?= json_encode($incomeData) ?>,
        expenseData: <?= json_encode($expenseData) ?>,
        selectedProject: <?= json_encode($selectedProject) ?>
    };
</script>

<script src="assets/js/charts_collection.js"></script>

<?php require "template/footer.php"; ?>