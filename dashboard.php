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
    if ($selectedProject > 0) {
        $stmt = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM projects WHERE status='Pending Evaluation') AS pendingProjects,
                (SELECT COUNT(*) FROM projects WHERE status='For Award') AS forAwardProjects,
                (SELECT COUNT(*) FROM projects WHERE status='For Implementation') AS forImplementationProjects,
                (SELECT COUNT(*) FROM projects WHERE status='Ongoing') AS activeProjects,
                (SELECT COUNT(*) FROM projects WHERE status='Delivered') AS deliveredProjects,
                (SELECT COUNT(*) FROM projects WHERE status='Completed') AS completedProjects
            FROM projects WHERE project_id = $selectedProject
        ");
    } else {
        $stmt = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM projects WHERE status='Pending Evaluation') AS pendingProjects,
                (SELECT COUNT(*) FROM projects WHERE status='For Award') AS forAwardProjects,
                (SELECT COUNT(*) FROM projects WHERE status='For Implementation') AS forImplementationProjects,
                (SELECT COUNT(*) FROM projects WHERE status='Ongoing') AS activeProjects,
                (SELECT COUNT(*) FROM projects WHERE status='Delivered') AS deliveredProjects,
                (SELECT COUNT(*) FROM projects WHERE status='Completed') AS completedProjects
        ");
    }

    $projectTotals = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalProjects = ($projectTotals['pendingProjects'] ?? 0) + 
                    ($projectTotals['forAwardProjects'] ?? 0) + 
                    ($projectTotals['forImplementationProjects'] ?? 0) + 
                    ($projectTotals['activeProjects'] ?? 0) + 
                    ($projectTotals['deliveredProjects'] ?? 0) + 
                    ($projectTotals['completedProjects'] ?? 0);
    $inEvaluationPercent = $totalProjects > 0 ? round(($projectTotals['pendingProjects'] ?? 0) / $totalProjects * 100, 2) : 0;
    $inEvaluationCount = $projectTotals['pendingProjects'] ?? 0;
     // PROJECT SUMMARY //

    // BUDGET VARIANCE QUERY
    if ($selectedProject > 0) {
        $varianceStmt = $pdo->query("
            SELECT 
                project_name,
                contract_amount,
                ABC,
                (ABC - contract_amount) AS variance_amount,
                CASE 
                    WHEN contract_amount > 0 THEN ROUND(((ABC - contract_amount) / contract_amount) * 100, 2)
                    ELSE 0 
                END AS variance_percent
            FROM projects 
            WHERE project_id = $selectedProject
            AND contract_amount > 0
        ");
    } else {
        $varianceStmt = $pdo->query("
            SELECT 
                project_name,
                contract_amount,
                ABC,
                (ABC - contract_amount) AS variance_amount,
                CASE 
                    WHEN contract_amount > 0 THEN ROUND(((ABC - contract_amount) / contract_amount) * 100, 2)
                    ELSE 0 
                END AS variance_percent
            FROM projects 
            WHERE contract_amount > 0
        ");
    }

    $varianceData = $varianceStmt->fetchAll(PDO::FETCH_ASSOC);

    $overBudgetCount = 0;
    $underBudgetCount = 0;
    foreach ($varianceData as $project) {
        if ($project['variance_percent'] > 0) $overBudgetCount++;
        if ($project['variance_percent'] < 0) $underBudgetCount++;
    }

    $overBudgetPercent = count($varianceData) > 0 ? round(($overBudgetCount / count($varianceData)) * 100, 2) : 0;
    // PROJECT SUMMARY //

    // OPERATION SUMMARY //
    if ($selectedProject > 0) {
        $stmt = $pdo->query("
            SELECT
                (SELECT COUNT(DISTINCT CONCAT_WS('-', school_id, lot_id)) FROM deliveries WHERE status='pending' AND project_id = $selectedProject) AS pending,
                (SELECT COUNT(DISTINCT CONCAT_WS('-', school_id, lot_id)) FROM deliveries WHERE status='accepted' AND project_id = $selectedProject) AS accepted,
                (SELECT COUNT(DISTINCT CONCAT_WS('-', school_id, lot_id)) FROM deliveries WHERE status='delivered' AND project_id = $selectedProject) AS delivered
            FROM projects WHERE project_id = $selectedProject
        ");
    } else {
        $stmt = $pdo->query("
            SELECT
              (SELECT COUNT(DISTINCT CONCAT_WS('-', school_id, lot_id)) FROM deliveries WHERE status='pending') AS pending,
              (SELECT COUNT(DISTINCT CONCAT_WS('-', school_id, lot_id)) FROM deliveries WHERE status='accepted') AS accepted,
              (SELECT COUNT(DISTINCT CONCAT_WS('-', school_id, lot_id)) FROM deliveries WHERE status='delivered') AS delivered
        ");
    }

    $deliveryTotals = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalDeliveries = ($deliveryTotals['pending'] ?? 0) + ($deliveryTotals['accepted'] ?? 0) + ($deliveryTotals['delivered'] ?? 0);
    $completionRate = $totalDeliveries > 0 ? round(($deliveryTotals['delivered'] ?? 0) / $totalDeliveries * 100, 2) : 0;
    $pendingPercent = $totalDeliveries > 0 ? round(($deliveryTotals['pending'] / $totalDeliveries) * 100) : 0;
    $acceptedPercent = $totalDeliveries > 0 ? round(($deliveryTotals['accepted'] / $totalDeliveries) * 100) : 0;
    $deliveredPercent = $totalDeliveries > 0 ? round(($deliveryTotals['delivered'] / $totalDeliveries) * 100) : 0;
    // OPERATION SUMMARY //

    // BILLING SUMMARY //
    $stmt = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM grouping) AS total_groups,
            (SELECT COUNT(DISTINCT dr_no) FROM billing_grouped) AS total_drs,
            (SELECT COUNT(DISTINCT bg.dr_no) FROM grouping g
            JOIN billing_grouped bg ON bg.group_id = g.group_id 
            WHERE status = 'for billing') AS for_billing_count,
            (SELECT COUNT(DISTINCT bg.dr_no) FROM grouping g 
            JOIN billing_grouped bg ON bg.group_id = g.group_id 
            WHERE status = 'billed') AS billed_count,
            (SELECT COUNT(DISTINCT bg.dr_no) FROM grouping g 
            JOIN billing_grouped bg ON bg.group_id = g.group_id 
            WHERE status = 'paid') AS paid_count
    ");
    
    $billingTotals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalGroups = $billingTotals['total_groups'] ?? 0;
    $totalDr = $billingTotals['total_drs'] ?? 0;
    $forBillingPercent = $totalGroups > 0 ? round(($billingTotals['for_billing_count'] / $totalDr) * 100) : 0;
    $billedPercent = $totalGroups > 0 ? round(($billingTotals['billed_count'] / $totalDr) * 100) : 0;
    $paidPercent = $totalGroups > 0 ? round(($billingTotals['paid_count'] / $totalDr) * 100) : 0;
    // BILLING SUMMARY //

    // SALES GENERATION CHARTS //
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

    // OPERATION CHARTS //
    $deliveryStatusQuery = "
        SELECT 
            COUNT(*) AS total,
            CASE d.status
                WHEN 'pending'   THEN 'Pending'
                WHEN 'accepted'  THEN 'Accepted'
                WHEN 'delivered' THEN 'Delivered'
                WHEN 'cancelled' THEN 'Cancelled'
                ELSE d.status
            END AS status
        FROM deliveries d
        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY 
            CASE d.status
                WHEN 'pending'   THEN 'Pending'
                WHEN 'accepted'  THEN 'Accepted'
                WHEN 'delivered' THEN 'Delivered'
                WHEN 'cancelled' THEN 'Cancelled'
                ELSE d.status
            END
        ORDER BY total DESC;
    ";
    $stmt = $pdo->query($deliveryStatusQuery);
    $deliveryStatusOverview = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $monthlyTrendQuery = "
        SELECT 
            DATE_FORMAT(d.delivered_date, '%Y-%m') AS month,
            CASE 
                WHEN d.status = 'warehouse' THEN 'Warehouse'
                WHEN d.status = 'delivered' THEN 'Schools'
                WHEN d.status = 'accepted' THEN COALESCE(lg.logistic_name, 'Logistics')
                ELSE d.status
            END AS status,
            COUNT(*) AS total
        FROM deliveries d
        LEFT JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
        LEFT JOIN logistics lg ON ll.logistics_id = lg.logistic_id
        WHERE d.delivered_date IS NOT NULL
            AND d.status NOT IN ('pending', 'cancelled')
            " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY month, status
        ORDER BY month, status;
    ";
    $stmt = $pdo->query($monthlyTrendQuery);
    $monthlyDeliveryTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $inventoryQuery = "
        SELECT 
            ii.item_name,
            COALESCE(
                (SELECT ih.new_qty
                FROM inventory_history ih
                WHERE ih.item_id = ii.item_id 
                  AND DATE(ih.changed_at) <= :selectedDate
                ORDER BY ih.changed_at DESC 
                LIMIT 1),
                SUM(i.qty)
            ) as total_qty
        FROM inventory i
        JOIN item ii ON i.item_id = ii.item_id
        JOIN warehouse w ON i.warehouse_id = w.warehouse_id
        WHERE i.inventory_status = 'Approved'
        " . ($selectedProject > 0 ? "AND ii.project_id = $selectedProject" : "") . "
        GROUP BY ii.item_id, ii.item_name
        HAVING total_qty > 0
    ";

    $stmt = $pdo->prepare($inventoryQuery);
    $stmt->execute(['selectedDate' => $selectedDate]);
    $inventoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $inventoryHistoryQuery = "
        SELECT 
            DATE(changed_at) AS change_date,
            COUNT(*) AS total_changes
        FROM inventory_history
        GROUP BY DATE(changed_at)
        ORDER BY change_date ASC
        LIMIT 30
    ";
    $stmt = $pdo->query($inventoryHistoryQuery);
    $inventoryHistoryTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $progressPerLotQuery = "
        SELECT 
            l.lot_name,
            COUNT(*) AS total,
            SUM(CASE WHEN d.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN d.status = 'accepted' THEN 1 ELSE 0 END) AS accepted
        FROM deliveries d
        LEFT JOIN keystage k ON d.keystage_id = k.keystage_id
        JOIN lot l ON l.lot_id = COALESCE(d.lot_id, k.lot_id)
        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY l.lot_name
        ORDER BY l.lot_name
    ";

    $stmt = $pdo->query($progressPerLotQuery);
    $progressPerLot = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // OPERATION CHARTS //

    // MAP DATA //
    $divisionDeliveriesQuery = "
        SELECT 
            s.division AS province,
            COUNT(d.delivery_id) AS deliveries_count,
            ROUND(
                (COUNT(d.delivery_id) / (
                    SELECT COUNT(*) 
                    FROM deliveries 
                    WHERE status = 'delivered'
                ) * 100),
                2
            ) AS percentage
        FROM deliveries d
        JOIN school s ON d.school_id = s.school_id
        WHERE d.status = 'delivered'
        GROUP BY s.division
        ORDER BY percentage DESC
    ";

    $stmt = $pdo->query($divisionDeliveriesQuery);
    $divisionDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $deliveriesByDivision = [];
    foreach ($divisionDeliveries as $row) {
        $deliveriesByDivision[$row['province']] = [
            'count' => (int)$row['deliveries_count'],
            'percentage' => (float)$row['percentage']
        ];
    }
    echo "<script>const deliveriesByDivision = " . json_encode($deliveriesByDivision) . ";</script>";
    // MAP DATA //

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
  <h2>Dashboard Overview</h2>
  <div class="btn-group">
    <button class="btn btn-outline-primary btn-sm" id="resetLayout">
      🔄 Reset Layout
    </button>
    <button class="btn btn-outline-secondary btn-sm" id="toggleDrag">
      🔒 Toggle Drag
    </button>
  </div>
</div>

<div class="container-fluid">
  <div class="row g-4 mb-4" id="draggable-dashboard">
    
    <!-- LEFT: Project Summary -->
    <div class="col-md-4 col-12 chart-item" data-chart-id="project-summary">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">🚀 PROJECT SUMMARY</h6>
          <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body p-2">
          <div class="row g-3">
            <div class="col-md-12">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">Projects</h6>
                  <a href="report/print_delivery_status.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
                    <i class="bi bi-printer"></i>
                  </a>
                </div>
                <div class="card-body">
                  <canvas id="projectStatusChart" height="340"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Budget Variance</h6>
                </div>
                <div class="card-body" style="height: 400px; overflow-y: auto;">
                    <canvas id="opportunityChart" width="600" height="340"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Sales Generation Summary -->
    <div class="col-md-8 col-12 chart-item" data-chart-id="sales-generation-summary">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <div>
              <h6 class="mb-0 fw-bold">🪙 SALES GENERATION SUMMARY</h6>
              <a href="dashboard_sales.php<?= isset($_GET['project_id']) ? '?project_id=' . urlencode($_GET['project_id']) : '' ?>"
                class="text-primary small fw-semibold text-decoration-none d-inline-flex align-items-center gap-1 mt-1">
                View Details <i class="bi bi-arrow-right-short fs-5"></i>
              </a>
          </div>
          <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
        </div>

        <!-- Project Filter Inside the Card -->
        <div class="row mb-2">
          <div class="col-12">
            <div class="card shadow-sm border-0 bg-light">
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
        
        <div class="card-body p-2">
          <div class="row g-3">
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
            <div class="col-md-12">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <h6 class="mb-0"> Expected vs Actual deliveries by Warehouse</h6>
                </div>
                <div class="card-body">
                  <canvas id="deliveriesByWarehouseChart" height="300"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LEFT: Production Summary -->
    <div class="col-md-8 col-12 chart-item" data-chart-id="production-summary">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0 fw-bold">🚚 PRODUCTION SUMMARY</h6>
            </div>
          <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body p-2">


          <div id="deliverySummaryContainer" class="sortable-container">
            <div class="row g-2">
              <?php 
              $deliveryCards = [
                  ['title'=>'In Progress','value'=>$deliveryTotals['pending'] ?? 0,'class'=>'danger','icon'=>'⏳', 'percent'=>$pendingPercent],
                  ['title'=>'Accepted','value'=>$deliveryTotals['accepted'] ?? 0,'class'=>'warning','icon'=>'🚚', 'percent'=>$acceptedPercent],
                  ['title'=>'Delivered','value'=>$deliveryTotals['delivered'] ?? 0,'class'=>'success','icon'=>'📦', 'percent'=>$deliveredPercent],
                  ['title'=>'Completion Rate','value'=>$completionRate . '%','class'=>'primary','icon'=>'📊',
                  'percent'=>$completionRate]
              ];
              foreach($deliveryCards as $c): ?>
              <div class="col-md-3 col-6">
                  <div class="card text-bg-<?=$c['class']?> h-100 summary-card" data-card-id="<?=strtolower(str_replace(' ','-',$c['title']))?>">
                    <div class="card-body p-3 text-center">
                      <div style="font-size: 2rem; margin-bottom: 10px;"><?=$c['icon']?></div>
                      <small class="d-block opacity-75"><?= $c['title'] ?></small>
                      <h3 class="mb-2 fw-bold"><?= $c['value'] ?></h3>
                      <?php if (isset($c['percent'])): ?>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $c['percent'] ?>%;"></div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Delivery Status Chart and Monthly Trend -->
          <div class="row g-3 mt-2">
            <a href="dashboard_operation.php<?= isset($_GET['project_id']) ? '?project_id=' . urlencode($_GET['project_id']) : '' ?>"
                class="text-primary small fw-semibold text-decoration-none d-inline-flex align-items-center gap-1 mt-1 mx-2">
                View Details <i class="bi bi-arrow-right-short fs-5"></i>
            </a>
            <div class="col-md-5">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">Delivery Per Status</h6>
                  <a href="report/print_delivery_status.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
                    <i class="bi bi-printer"></i>
                  </a>
                </div>
                <div class="card-body">
                  <canvas id="deliveryStatusChart" height="200"></canvas>
                </div>
              </div>
            </div>
            <div class="col-md-7">
              <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">Deliveries Status by Lot</h6>
                </div>
                <div class="card-body">
                  <canvas id="deliveryStatusPerLotChart" height="200"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Map Summary -->
    <div class="col-md-4 col-12 chart-item" data-chart-id="map-summary">
      <div class="card shadow-sm h-100 d-flex flex-column">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">🗺️ MAP OVERVIEW</h6>
          <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body shadow-sm p-2 flex-fill">
          <div id="leafletMap" class="h-100 w-100"></div>
        </div>
      </div>
    </div>

    <!-- LEFT: Operation Charts Section -->
    <div class="col-md-8 col-12 chart-item" data-chart-id="operation-section">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <div>
        <h6 class="mb-0 fw-bold">🚚 TRACK OPERATIONS</h6>
      </div>
          <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body p-2">
          <!-- Delivery Status by Lot -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h6 class="mb-0">Monthly Delivery Trend</h6>
              <!-- <a href="report/print_monthly_trend.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
                <i class="bi bi-printer"></i>
              </a> -->
            </div>
            <div class="card-body">
              <canvas id="monthlyDeliveryTrendChart" height="250"></canvas>
            </div>
          </div>
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0">Inventory History</h6>
            </div>
            <div class="card-body">
              <canvas id="inventoryHistoryTrendChart" height="250"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Collection Summary -->
    <div class="col-md-4 col-12 chart-item" data-chart-id="collection-summary">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 fw-bold">💰 COLLECTION SUMMARY</h6>
            <a href="dashboard_collection.php<?= isset($_GET['project_id']) ? '?project_id=' . urlencode($_GET['project_id']) : '' ?>"
              class="text-primary small fw-semibold text-decoration-none d-inline-flex align-items-center gap-1 mt-1">
              View More <i class="bi bi-arrow-right-short fs-5"></i>
            </a>
          </div>
          <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body p-2">
          <div id="billingSummaryContainer" class="sortable-container">
            <!-- Total Groups Card -->
            <div class="card text-bg-primary mb-2 summary-card" data-card-id="total-groups">
              <div class="card-body p-3 text-center">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">📊</div>
                <small class="d-block opacity-75">Total Groups</small>
                <h2 class="mb-1 fw-bold"><?= $billingTotals['total_groups'] ?></h2>
                <small class="opacity-75">Total DR no: <?= $billingTotals['total_drs'] ?></small>
              </div>
            </div>

            <?php 
            $billingCards = [
                ['title'=>'For Billing','value'=>$billingTotals['for_billing_count'] ?? 0,'class'=>'danger','icon'=>'⏳', 'percent'=>$forBillingPercent],
                ['title'=>'Billed','value'=>$billingTotals['billed_count'] ?? 0,'class'=>'warning','icon'=>'📄', 'percent'=>$billedPercent],
                ['title'=>'Paid','value'=>$billingTotals['paid_count'] ?? 0,'class'=>'success','icon'=>'💰', 'percent'=>$paidPercent]
            ];
            foreach($billingCards as $c): ?>
            <div class="card text-bg-<?=$c['class']?> mb-2 summary-card" data-card-id="<?=strtolower(str_replace(' ','-',$c['title']))?>">
              <div class="card-body p-3 d-flex align-items-center">
                <div class="me-3" style="font-size: 2rem;"><?=$c['icon']?></div>
                <div class="flex-grow-1">
                  <small class="d-block opacity-75"><?= $c['title'] ?></small>
                  <h4 class="mb-1 fw-bold"><?= $c['value'] ?></h4>
                  <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $c['percent'] ?>%;"></div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
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

<style>
  .drag-handle {
    cursor: grab;
    opacity: 0.7;
    transition: opacity 0.3s ease;
    font-size: 1.2em;
    font-weight: bold;
    padding: 5px;
    border-radius: 3px;
    background: rgba(0,0,0,0.05);
  }

  .drag-handle:hover {
    opacity: 1;
    background: rgba(0,0,0,0.1);
  }

  .drag-handle:active {
    cursor: grabbing;
  }

  .chart-item {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .chart-item:hover {
    transform: translateY(-2px);
  }

  .sortable-ghost {
    opacity: 0.4;
  }

  .sortable-chosen {
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    transform: rotate(2deg);
  }

  .sortable-drag {
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    transform: rotate(5deg);
  }

  .card-header {
    border-bottom: 1px solid #e9ecef;
  }

  .drag-disabled .drag-handle {
    cursor: not-allowed;
    opacity: 0.3;
  }

  #projectSelect {
    max-width: none;
  }

  .input-group .btn {
    z-index: 1;
  }
</style>

<!-- Pass PHP data to JavaScript and load the external script -->
<script>
  const phpData = {      
        deliveryStatusOverview: <?= json_encode($deliveryStatusOverview) ?>,
        monthlyDeliveryTrend: <?= json_encode($monthlyDeliveryTrend) ?>,
        inventoryData: <?= json_encode($inventoryData) ?>,
        selectedProject: <?= json_encode($selectedProject) ?>,
        progressPerLot: <?= json_encode($progressPerLot) ?> ,
        inventoryHistoryTrend: <?= json_encode($inventoryHistoryTrend) ?>,
        projectStatusOverview: <?= json_encode($projectStatusOverview) ?>,
        opportunity: <?= json_encode($opportunity) ?>,
        incomeData: <?= json_encode($incomeData) ?>,
        expenseData: <?= json_encode($expenseData) ?>,
        deliveriesByWarehouse: <?= json_encode($deliveriesByWarehouse) ?>,
    };
</script>


<script src="assets/js/charts.js?=v113"></script>


<?php require "template/footer.php"; ?>

<!-- MAP -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const mapContainer = document.getElementById('leafletMap');
    if (!mapContainer) return;

    // Initialize the map centered on the Philippines
    const defaultCenter = [12.8797, 121.7740];
    const defaultZoom = 6;
    const map = L.map(mapContainer, {
      center: defaultCenter,
      zoom: defaultZoom,
      zoomControl: true
    });

    // --- 🗺️ Base Layers ---
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap contributors'
    });

    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      maxZoom: 19,
      attribution: 'Tiles © Esri'
    });

    const terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      maxZoom: 17,
      attribution: '© OpenTopoMap contributors'
    });

    const dark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap, © CartoDB'
    });

    osm.addTo(map);

    // --- 🔍 Add Search Bar ---
    const geocoder = L.Control.geocoder({ defaultMarkGeocode: false })
      .on('markgeocode', function(e) {
        const center = e.geocode.center;
        L.marker(center).addTo(map)
          .bindPopup(`<b>${e.geocode.name}</b>`).openPopup();
        map.setView(center, 10);
      })
      .addTo(map);

    // --- 📦 Choropleth Data ---
    fetch("ph.json")
      .then(response => response.json())
      .then(geoData => {
        const maxPercent = Math.max(...Object.values(deliveriesByDivision).map(v => v.percentage));

        function getColor(value) {
          if (value === 0 || isNaN(value)) return "#f0f0f0";
          const percent = (value / maxPercent) * 100;
          if (percent >= 75) return "#66bb6a";   // Green - Very High
          if (percent >= 50) return "#fbc02d";   // Yellow - High
          if (percent >= 25) return "#f57c00";   // Orange - Medium
          return "#d32f2f";                      // Red - Low
        }

        const geoLayer = L.geoJSON(geoData, {
          style: function (feature) {
            const name = feature.properties.name;
            const data = deliveriesByDivision[name];
            const percent = data ? data.percentage : 0;
            return {
              color: "#444",
              weight: 1,
              fillColor: getColor(percent),
              fillOpacity: 0.7
            };
          },
          onEachFeature: function (feature, layer) {
            const name = feature.properties.name;
            const data = deliveriesByDivision[name];
            const count = data ? data.count : 0;
            const percent = data ? data.percentage : 0;
            layer.bindPopup(`<b>${name}</b><br>Deliveries: ${count}<br>Share: ${percent}%`);
          }
        }).addTo(map);

        map.fitBounds(geoLayer.getBounds());

        // Layer Controls
        const baseMaps = {
          "🗺️ OpenStreetMap": osm,
          "🛰️ Satellite": satellite,
          "🏔️ Terrain": terrain,
          "🌙 Dark Mode": dark
        };
        const overlayMaps = { "📦 Deliveries by Province": geoLayer };
        L.control.layers(baseMaps, overlayMaps, { collapsed: true }).addTo(map);

        // --- 🧭 Reset Button ---
        const resetControl = L.control({ position: 'topleft' });
        resetControl.onAdd = function () {
          const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
          div.innerHTML = '<button class="btn btn-sm btn-light fw-bold px-2 py-1">↻ Reset</button>';
          div.title = "Reset to Default View";
          div.style.cursor = 'pointer';
          div.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
          div.onclick = function () {
            map.setView(defaultCenter, defaultZoom);
          };
          return div;
        };
        resetControl.addTo(map);

      // --- Legend ---
      const legend = L.control({ position: 'bottomright' });
      legend.onAdd = function () {
        const div = L.DomUtil.create('div', 'info legend');
        div.style.backgroundColor = 'white';
        div.style.padding = '10px';
        div.style.borderRadius = '5px';
        div.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
        
        // Colors: red → orange → yellow → green
        const colors = ["#d32f2f", "#f57c00", "#fbc02d", "#66bb6a"];
        const labels = ['0–25%', '26–50%', '51–75%', '76–100%'];
        
        div.innerHTML = '<strong>Delivery Share</strong><br>';
        
        for (let i = 0; i < colors.length; i++) {
          div.innerHTML +=
            '<i style="background:' + colors[i] + '; width: 18px; height: 18px; display: inline-block; margin-right: 5px; opacity: 0.7;"></i> ' +
            labels[i] + '<br>';
        }
        
        return div;
      };
      legend.addTo(map);

      })
      .catch(error => console.error("Error loading GeoJSON:", error));
  });
</script>


