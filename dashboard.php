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

    // KPI

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
          <button class="btn kpi-card success-card bg-success bg-opacity-10 border-0 rounded-4 w-100 
                        d-flex flex-column justify-content-center align-items-center"
                        onclick="window.location.href='dashboard_finance.php'">
            <div class="position-relative">
              <div class="kpi-number fw-bold text-success">
                80% <span class="kpi-label text-uppercase fw-semibold">KPI</span>
              </div>
              <div class="kpi-title fw-semibold mt-3">Finance</div>
            </div>
          </button>
        </div>

        <!-- Production -->
        <div class="col-12 col-sm-6 col-md-4">
          <button class="btn kpi-card warning-card bg-warning bg-opacity-10 border-0 rounded-4 w-100 
                        d-flex flex-column justify-content-center align-items-center"
                        onclick="window.location.href='dashboard_production.php'">
            <div class="position-relative">
              <div class="kpi-number fw-bold text-warning">
                50% <span class="kpi-label text-uppercase fw-semibold">KPI</span>
              </div>
              <div class="kpi-title fw-semibold mt-3">Production</div>
            </div>
          </button>
        </div>

        <!-- Sales Gen -->
        <div class="col-12 col-sm-6 col-md-4">
          <button class="btn kpi-card danger-card bg-danger bg-opacity-10 border-0 rounded-4 w-100 
                        d-flex flex-column justify-content-center align-items-center"
                        onclick="window.location.href='dashboard_sales_generation.php'">
            <div class="position-relative">
              <div class="kpi-number fw-bold text-danger">
                30% <span class="kpi-label text-uppercase fw-semibold">KPI</span>
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



