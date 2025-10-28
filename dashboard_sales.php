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

    // Get income data (from deliveries)
    $incomeQuery = "
        SELECT 
            DATE_FORMAT(d.delivered_date, '%Y-%m') AS month,
            SUM(i.price * pc.qty) AS total_income,
            SUM((i.price - i.supplier_price) * pc.qty) AS total_profit,
            COUNT(DISTINCT d.delivery_id) AS total_deliveries
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
    $stmt = $pdo->query($incomeQuery);
    $incomeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expense data (from inventory)
    $expenseQuery = "
        SELECT 
            DATE_FORMAT(inv.created_at, '%Y-%m') AS month,
            SUM(i.supplier_price * inv.qty) AS total_expense,
            COUNT(DISTINCT inv.inventory_id) AS total_transactions
        FROM inventory inv
        JOIN item i ON inv.item_id = i.item_id
        WHERE inv.created_at IS NOT NULL
            " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
        GROUP BY month
        ORDER BY month;
    ";
    $stmt = $pdo->query($expenseQuery);
    $expenseData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get income by item (from deliveries)
    $incomeByItemQuery = "
        SELECT 
            i.item_name,
            SUM(i.price * pc.qty) AS total_income,
            SUM(pc.qty) AS total_qty_sold
        FROM deliveries d
        JOIN package_status ps ON d.delivery_id = ps.delivery_id
        JOIN package_content pc ON ps.package_id = pc.package_id  
        JOIN item i ON pc.item_id = i.item_id
        WHERE d.delivered_date IS NOT NULL
            AND d.status <> 'pending'
            " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY i.item_id, i.item_name
        ORDER BY total_income DESC
    ";
    $stmt = $pdo->query($incomeByItemQuery);
    $incomeByItem = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expense by item (from inventory)
    $expenseByItemQuery = "
        SELECT 
            i.item_name,
            SUM(i.supplier_price * inv.qty) AS total_expense,
            SUM(inv.qty) AS total_qty_purchased
        FROM inventory inv
        JOIN item i ON inv.item_id = i.item_id
        WHERE inv.created_at IS NOT NULL
            AND inv.inventory_status = 'Approved'
            " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
        GROUP BY i.item_id, i.item_name
        ORDER BY total_expense DESC
    ";
    $stmt = $pdo->query($expenseByItemQuery);
    $expenseByItem = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get income and expense by item 
    $incomeExpenseByItemQuery = "
        SELECT 
            i.item_name,
            COALESCE(SUM(i.price * pc.qty), 0) AS total_income,
            COALESCE(SUM(pc.qty), 0) AS total_qty_sold,
            COALESCE(SUM(i.supplier_price * inv.qty), 0) AS total_expense,
            COALESCE(SUM(inv.qty), 0) AS total_qty_purchased
        FROM item i
        LEFT JOIN (
            SELECT 
                pc.item_id, 
                pc.qty
            FROM package_content pc 
            JOIN package_status ps ON pc.package_id = ps.package_id
            JOIN deliveries d ON ps.delivery_id = d.delivery_id
            WHERE d.delivered_date IS NOT NULL 
              AND d.status <> 'pending'
              " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        ) pc ON i.item_id = pc.item_id
        LEFT JOIN (
            SELECT item_id, qty 
            FROM inventory 
            WHERE created_at IS NOT NULL 
              AND inventory_status = 'Approved'
        ) inv ON i.item_id = inv.item_id
        WHERE 1=1
            " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
        GROUP BY i.item_id, i.item_name
        HAVING 
            SUM(i.price * pc.qty) > 0 
            OR SUM(i.supplier_price * inv.qty) > 0
        ORDER BY (SUM(i.price * pc.qty) + SUM(i.supplier_price * inv.qty)) DESC
        LIMIT 10;
    ";

    $stmt = $pdo->query($incomeExpenseByItemQuery);
    $incomeExpenseByItem = $stmt->fetchAll(PDO::FETCH_ASSOC);
        

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

    <!-- Income & Profit Chart -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">💵 Income & Profit Overview</h6>
          <!-- <a href="report/print_income.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a> -->
        </div>
        <div class="card-body">
          <canvas id="incomeChart" height="250"></canvas>
        </div>
      </div>
    </div>

    <!-- Expense Chart -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">💸 Expense Overview</h6>
          <!-- <a href="report/print_expense.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a> -->
        </div>
        <div class="card-body">
          <canvas id="expenseChart" height="250"></canvas>
        </div>
      </div>
    </div>

    <!-- Income by Item Chart -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">📊 Items by Income</h6>
          <!-- <a href="report/print_income_by_item.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a> -->
        </div>
        <div class="card-body">
          <canvas id="incomeByItemChart" height="250"></canvas>
        </div>
      </div>
    </div>

    <!-- Expense by Item Chart -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">📊 Items by Expense</h6>
          <!-- <a href="report/print_expense_by_item.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a> -->
        </div>
        <div class="card-body">
          <canvas id="expenseByItemChart" height="250"></canvas>
        </div>
      </div>
    </div>

    <!-- Income & Expense by Item Chart -->
    <div class="col-lg-12">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">📊 Income & Expense by Item</h6>
          <!-- <a href="report/print_income_expense_by_item.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a> -->
        </div>
        <div class="card-body">
          <canvas id="incomeExpenseByItemChart" height="250"></canvas>
        </div>
      </div>
    </div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const phpData = {
        incomeData: <?= json_encode($incomeData) ?>,
        expenseData: <?= json_encode($expenseData) ?>,
        incomeByItem: <?= json_encode($incomeByItem) ?>,
        expenseByItem: <?= json_encode($expenseByItem) ?>,
        incomeExpenseByItem: <?= json_encode($incomeExpenseByItem) ?>,
        selectedProject: <?= json_encode($selectedProject) ?>
    };
</script>

<script src="assets/js/charts_sales.js"></script>

<?php require "template/footer.php"; ?>