<?php 
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

$allowed_roles = ['Super Admin', 'Office Admin'];
redirectIfNotAuthorized($allowed_roles, 'index.php');

try {

    // KPI - Sales Generation (Projects Listed vs Acquired)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_listed,
            COUNT(CASE WHEN status IN ('Ongoing', 'Delivered', 'Completed') 
                  THEN 1 END) AS total_acquired
        FROM projects
    ");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $projectsWinKPI = $data['total_listed'] > 0 
        ? round(($data['total_acquired'] / $data['total_listed']) * 100, 2) 
        : 0;

    // KPI - Sales Generation ( ABC vs Actual Costing )note: the duration is not included yet
    $stmt = $pdo->query("
        SELECT 
            SUM(p.ABC) AS total_budget,
            SUM(i.supplier_price * pc.qty) AS total_costing
        FROM projects p
        LEFT JOIN item i ON p.project_id = i.project_id
        LEFT JOIN package_content pc ON i.item_id = pc.item_id
        WHERE p.status IN ('Ongoing', 'Delivered', 'Completed')
    ");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalBudget = $data['total_budget'] ?? 0;
    $totalCosting = $data['total_costing'] ?? 0;
    
    // Profit margin: How much profit from the budget
    $financeKPI = $totalBudget > 0 
        ? round((($totalBudget - $totalCosting) / $totalBudget) * 100, 2) 
        : 0;
        
  
    $salesGenerationKPI = round(($projectsWinKPI + $financeKPI) / 2, 2);


    // KPI - Production (Completion Rate)
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT CONCAT(d.school_id, '-', d.lot_id)) as total,
            COUNT(DISTINCT CASE WHEN d.status IN ('delivered', 'accepted') 
                THEN CONCAT(d.school_id, '-', d.lot_id) END) as completed
        FROM deliveries d
        INNER JOIN projects p ON d.project_id = p.project_id
        WHERE p.status = 'ongoing'
        AND d.project_id IN (
            SELECT DISTINCT project_id 
            FROM deliveries 
            WHERE status IN ('delivered', 'accepted', 'pending')
        )
    ");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $completionRateKPI = $data['total'] > 0 
        ? round(($data['completed'] / $data['total']) * 100, 2) 
        : 0;

    // KPI - Production (Logistics performance)
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT CONCAT_WS('-', d.school_id, d.lot_id)) AS total_deliveries,
            COUNT(DISTINCT CASE WHEN d.status IN ('delivered', 'accepted') 
                  THEN CONCAT_WS('-', d.school_id, d.lot_id) END) AS completed_deliveries
        FROM deliveries d
        INNER JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
        INNER JOIN projects p ON d.project_id = p.project_id
        WHERE p.status = 'ongoing'
            AND d.status NOT IN ('cancelled')
    ");

    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $logisticsKPI = $data['total_deliveries'] > 0 
        ? round(($data['completed_deliveries'] / $data['total_deliveries']) * 100, 2) 
        : 0;

    // KPI - Production (Warehouse Performance)
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT CONCAT_WS('-', d.school_id, d.lot_id)) AS expected_deliveries,
            COUNT(DISTINCT CASE WHEN d.status IN ('delivered', 'accepted') 
                  THEN CONCAT_WS('-', d.school_id, d.lot_id) END) AS actual_deliveries
        FROM deliveries d
        INNER JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
        INNER JOIN warehouse w ON ll.warehouse_id = w.warehouse_id
        INNER JOIN projects p ON d.project_id = p.project_id
        WHERE p.status = 'ongoing'
            AND d.status NOT IN ('cancelled')
    ");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $warehouseKPI = $data['expected_deliveries'] > 0 
        ? round(($data['actual_deliveries'] / $data['expected_deliveries']) * 100, 2) 
        : 0;

    // KPI - Production (Delivered vs Paid Deliveries)
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT CONCAT_WS('-', d.school_id, d.lot_id)) AS total_delivered,
            COUNT(DISTINCT CASE WHEN g.status = 'paid' 
                  THEN CONCAT_WS('-', d.school_id, d.lot_id) END) AS total_paid
        FROM deliveries d
        LEFT JOIN (
            SELECT DISTINCT dr_no, group_id 
            FROM billing_grouped
        ) bg ON d.dr_no = bg.dr_no
        LEFT JOIN grouping g ON bg.group_id = g.group_id
        INNER JOIN projects p ON d.project_id = p.project_id
        WHERE d.status = 'delivered'
            AND p.status = 'ongoing'
    ");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $deliveriesKPI = $data['total_delivered'] > 0 
        ? round(($data['total_paid'] / $data['total_delivered']) * 100, 2) 
        : 0;

    $productionKPI = round(($completionRateKPI + $logisticsKPI + $warehouseKPI + $deliveriesKPI) / 4, 2);
    
    // Placeholder KPIs for Finance and Production
    $financeKPI = 81; // Replace with actual calculation

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

date_default_timezone_set('Asia/Manila');
$hour = (int) date('H'); // current hour in 24-hour format
if ($hour >= 5 && $hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "Good Afternoon";
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = "Good Evening";
} else {
    $greeting = "Good Night";
}

// Determine KPI color classes
function getKpiClass($kpi) {
    if ($kpi >= 80) return ['success-card', 'success'];
    if ($kpi >= 50) return ['warning-card', 'warning'];
    return ['danger-card', 'danger'];
}

$financeClasses = getKpiClass($financeKPI);
$productionClasses = getKpiClass($productionKPI);
$salesClasses = getKpiClass($salesGenerationKPI);
?>
<style>
  body { background: #f8f9fa; }
  
  .kpi-card {
    aspect-ratio: 1/1;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
  }
  
  .kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.1;
    background: linear-gradient(135deg, currentColor 0%, transparent 100%);
  }
  
  .kpi-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
  }
  
  .kpi-number { font-size: 3.5rem; }
  .kpi-label { color: #6c757d; font-size: 0.875rem; }
  .kpi-title { font-size: 1.5rem; color: #1e293b; }
  
  .success-card { color: #198754; }
  .warning-card { color: #ffc107; }
  .danger-card { color: #dc3545; }
</style>

<div class="container-fluid d-flex flex-column bg-light">
  <div class="container-fluid text-center flex-grow-1">
    <h1 class="mb-5 mt-4 fw-light fs-1">
      <?php echo $greeting . ' ' . htmlspecialchars($_SESSION['name']); ?>! 
    </h1>
    
    <div class="container">
      <div class="row g-4">
        
        <!-- Finance -->
        <div class="col-12 col-sm-6 col-md-4">
          <button class="btn kpi-card <?php echo $financeClasses[0]; ?> bg-<?php echo $financeClasses[1]; ?> bg-opacity-10 border-0 rounded-4 w-100 
                        d-flex flex-column justify-content-center align-items-center"
                        onclick="window.location.href='dashboard_finance.php'">
            <div class="position-relative">
              <div class="kpi-number fw-bold text-<?php echo $financeClasses[1]; ?>">
                <?php echo $financeKPI; ?>% <span class="kpi-label text-uppercase fw-semibold">KPI</span>
              </div>
              <div class="kpi-title fw-semibold mt-3">Finances</div>
            </div>
          </button>
        </div>

        <!-- Production -->
        <div class="col-12 col-sm-6 col-md-4">
          <button class="btn kpi-card <?php echo $productionClasses[0]; ?> bg-<?php echo $productionClasses[1]; ?> bg-opacity-10 border-0 rounded-4 w-100 
                        d-flex flex-column justify-content-center align-items-center"
                        onclick="window.location.href='dashboard_production.php'">
            <div class="position-relative">
              <div class="kpi-number fw-bold text-<?php echo $productionClasses[1]; ?>">
                <?php echo $productionKPI; ?>% <span class="kpi-label text-uppercase fw-semibold">KPI</span>
              </div>
              <div class="kpi-title fw-semibold mt-3">Production</div>
            </div>
          </button>
        </div>

        <!-- Sales Gen -->
        <div class="col-12 col-sm-6 col-md-4">
          <button class="btn kpi-card <?php echo $salesClasses[0]; ?> bg-<?php echo $salesClasses[1]; ?> bg-opacity-10 border-0 rounded-4 w-100 
                        d-flex flex-column justify-content-center align-items-center"
                        onclick="window.location.href='dashboard_sales_generation.php'">
            <div class="position-relative">
              <div class="kpi-number fw-bold text-<?php echo $salesClasses[1]; ?>">
                <?php echo $salesGenerationKPI; ?>% <span class="kpi-label text-uppercase fw-semibold">KPI</span>
              </div>
              <div class="kpi-title fw-semibold mt-3">Sales Generation</div>
            </div>
          </button>
        </div>

      </div>
    </div>
  </div>
</div>


<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<?php require "template/footer.php"; ?>



