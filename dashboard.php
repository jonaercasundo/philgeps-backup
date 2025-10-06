<?php 
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');

// Get selected project from URL parameter
$selectedProject = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

try {
    // Get all projects for the dropdown
    $stmt = $pdo->query("SELECT project_id, project_name FROM projects ORDER BY project_name");
    $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DEBUG: Check if we have projects
    echo "<!-- DEBUG: Projects count: " . count($allProjects) . " -->";
    
    // Build WHERE clause based on selected project
    $projectFilter = $selectedProject > 0 ? "WHERE p.project_id = $selectedProject" : "";
    $deliveryProjectFilter = $selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "";
    $invoiceProjectFilter = $selectedProject > 0 ? "WHERE project_id = $selectedProject" : "";

    // Check table names first
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- DEBUG: Available tables: " . implode(', ', $tables) . " -->";
    
    // Check if 'school' or 'schools' table exists
    $schoolTable = in_array('school', $tables) ? 'school' : 'schools';
    echo "<!-- DEBUG: Using table: $schoolTable -->";

    // Places Delivered (distinct schools reached per project/region)
    $placesQuery = "
        SELECT 
            s.region,
            p.project_id,
            p.project_name,
            COUNT(DISTINCT s.school_id) AS total_schools,
            SUM(CASE WHEN d.status = 'Delivered' THEN 1 ELSE 0 END) AS delivered_count
        FROM deliveries d
        JOIN $schoolTable s ON d.school_id = s.school_id
        JOIN projects p ON d.project_id = p.project_id
        " . ($selectedProject > 0 ? "WHERE p.project_id = $selectedProject" : "") . "
        GROUP BY s.region, p.project_id, p.project_name
        ORDER BY p.project_name, s.region
    ";
    
    echo "<!-- DEBUG: Places query: $placesQuery -->";
    
    $stmt = $pdo->query($placesQuery);
    $placesDelivered = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Places delivered count: " . count($placesDelivered) . " -->";
    if (count($placesDelivered) > 0) {
        echo "<!-- DEBUG: Sample place: " . json_encode($placesDelivered[0]) . " -->";
    }

    // Aggregate counts and sums (filtered by project if selected)
    if ($selectedProject > 0) {
        $stmt = $pdo->query("
            SELECT
                SUM(CASE WHEN status='Ongoing' THEN 1 ELSE 0 END) AS activeProjects,
                SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completedProjects,
                (SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='Paid' AND project_id = $selectedProject) AS totalPaid,
                (SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='Pending' AND project_id = $selectedProject) AS totalPending
            FROM projects WHERE project_id = $selectedProject
        ");
    } else {
        $stmt = $pdo->query("
            SELECT
                SUM(CASE WHEN status='Ongoing' THEN 1 ELSE 0 END) AS activeProjects,
                SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completedProjects,
                (SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='Paid') AS totalPaid,
                (SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='Pending') AS totalPending
            FROM projects
        ");
    }
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<!-- DEBUG: Totals: " . json_encode($totals) . " -->";

    // 1. Delivery Status Overview (filtered)
    $deliveryStatusQuery = "
        SELECT 
            COUNT(*) AS total,
            CASE d.status
                WHEN 'pending'   THEN 'Factory'
                WHEN 'accepted'  THEN 'Logistics'
                WHEN 'delivered' THEN 'Schools'
                WHEN 'warehouse' THEN 'Warehouse'
                ELSE d.status
            END AS status
        FROM deliveries d
        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY 
            CASE d.status
                WHEN 'pending'   THEN 'Factory'
                WHEN 'accepted'  THEN 'Logistics'
                WHEN 'delivered' THEN 'Schools'
                WHEN 'warehouse' THEN 'Warehouse'
                ELSE d.status
            END
        ORDER BY total DESC;
    ";
    
    echo "<!-- DEBUG: Delivery status query: $deliveryStatusQuery -->";
    
    $stmt = $pdo->query($deliveryStatusQuery);
    $deliveryStatusOverview = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Delivery status count: " . count($deliveryStatusOverview) . " -->";
    if (count($deliveryStatusOverview) > 0) {
        echo "<!-- DEBUG: Sample delivery status: " . json_encode($deliveryStatusOverview[0]) . " -->";
    }

    // 2. Monthly Delivery Trend (filtered)
    $monthlyTrendQuery = "
        SELECT 
          DATE_FORMAT(d.delivered_date, '%Y-%m') AS month,
          CASE d.status
              WHEN 'warehouse' THEN 'Warehouse'
              WHEN 'accepted'  THEN 'Logistics'
              WHEN 'delivered' THEN 'Schools'
              ELSE d.status
          END AS status,
          COUNT(*) AS total
      FROM deliveries d
      WHERE d.delivered_date IS NOT NULL
        AND d.status <> 'pending'
        " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
      GROUP BY month, status
      ORDER BY month, status;

    ";
    
    
    $stmt = $pdo->query($monthlyTrendQuery);
    $monthlyDeliveryTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Monthly trend count: " . count($monthlyDeliveryTrend) . " -->";

    // 5. Activity Log Actions
    $activityQuery = "
        SELECT action, COUNT(*) as total
        FROM activity_logs
        WHERE action IS NOT NULL
        GROUP BY action
        ORDER BY total DESC
    ";
    
    echo "<!-- DEBUG: Activity query: $activityQuery -->";
    
    $stmt = $pdo->query($activityQuery);
    $activityLogActions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Activity log count: " . count($activityLogActions) . " -->";

    // Add missing variables with empty data for now
    $projectsPerYear = [];
    $projectsByAgency = [];
    $amountPerYear = [];
    $projectProgress = [];
    $topPackageTypes = [];
    $deliveriesPerProject = [];

} catch (PDOException $e) {
    echo "<!-- DEBUG: DB Error: " . $e->getMessage() . " -->";
    die("DB Error: " . $e->getMessage());
}

// Get selected project name for display
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
  <h2>📊 Dashboard </h2>
  <!-- <h2>📊 Dashboard - <?= htmlspecialchars($selectedProjectName) ?></h2> -->
  <div class="btn-group">
    <button class="btn btn-outline-primary btn-sm" id="resetLayout">
      🔄 Reset Layout
    </button>
    <button class="btn btn-outline-secondary btn-sm" id="toggleDrag">
      🔒 Toggle Drag
    </button>
  </div>
</div>

<!-- Project Filter -->
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="card-title mb-3">🎯 Filter by Project</h6>
        <form method="GET" id="projectFilterForm">
          <div class="input-group">
            <select class="form-select" name="project_id" id="projectSelect">
              <option value="0" <?= $selectedProject == 0 ? 'selected' : '' ?>>All Projects</option>
              <?php foreach($allProjects as $project): ?>
                <option value="<?= $project['project_id'] ?>" <?= $selectedProject == $project['project_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($project['project_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">
              🔍 Filter
            </button>
            <?php if($selectedProject > 0): ?>
            <a href="?" class="btn btn-outline-secondary">
              ❌ Clear Filter
            </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Summary Cards (Non-draggable) -->
<div class="row mb-4">
  <?php 
  $cards = [
      ['title'=>'Active Projects','value'=>$totals['activeProjects'],'class'=>'primary','icon'=>'🚀'],
      ['title'=>'Total Value','value'=>'₱'.number_format($totals['totalPaid']),'class'=>'success','icon'=>'💰'],
      ['title'=>'Pending Payments','value'=>'₱'.number_format($totals['totalPending']),'class'=>'warning','icon'=>'⏳'],
      ['title'=>'Completed Projects','value'=>$totals['completedProjects'],'class'=>'dark','icon'=>'✅']
  ];
  foreach($cards as $c): ?>
  <div class="col-md-3 mb-3">
    <div class="card text-bg-<?=$c['class']?> shadow-sm h-100">
      <div class="card-body text-center">
        <div style="font-size: 2rem; margin-bottom: 10px;"><?=$c['icon']?></div>
        <h6 class="card-title"><?= $c['title'] ?></h6>
        <h4 class="mb-0"><strong><?= $c['value'] ?></strong></h4>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Draggable Charts Container -->
<div id="draggable-dashboard" class="row">
  
  <!-- Chart 1: Delivery Status Overview -->
  <div class="col-lg-4 col-md-6 mb-4 chart-item" data-chart-id="delivery-status">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📊 Delivery Status Overview</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="deliveryStatusChart" height="200"></canvas>
      </div>
    </div>
  </div>


  <!-- Chart 4: Monthly Delivery Trend -->
  <div class="col-lg-8 col-md-12 mb-4 chart-item" data-chart-id="monthly-trend">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📈 Monthly Delivery Trend</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="monthlyDeliveryTrendChart" height="150"></canvas>
      </div>
    </div>
  </div>

 <!-- Chart 2: Ongoing Activity Record -->
  <div class="col-lg-4 col-md-6 mb-4 chart-item" data-chart-id="activity-logs">
    <div class="card shadow-sm h-100">
      <div class="feature-overlay">
         <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0"> 🕜 Ongoing Activity Record</h6>
            <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
          </div>
          <div class="text-center p-4 bg-white rounded-3 border-top">
              <p class="fs-2 mb-3 text-primary">🚀</p>
              <p class="h5 fw-bolder text-dark mb-2">Get Ready for Enhanced Logs!</p>
              <p class="small text-muted">This graph will provide insights for all user actions. Stay tuned!</p>
          </div>
      </div>
    </div>
  </div>

  <!-- Chart 10: Places Delivered -->
  <div class="col-lg-8 mb-4 chart-item" data-chart-id="places-delivered">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📍 Places Delivered (Schools Reached by Project & Region)</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="placesDeliveredChart" height="300"></canvas>
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
        projectsPerYear: <?= json_encode($projectsPerYear) ?>,
        projectsByAgency: <?= json_encode($projectsByAgency) ?>,
        amountPerYear: <?= json_encode($amountPerYear) ?>,
        projectProgress: <?= json_encode($projectProgress) ?>,
        placesDelivered: <?= json_encode($placesDelivered) ?>,
        deliveryStatusOverview: <?= json_encode($deliveryStatusOverview) ?>,
        monthlyDeliveryTrend: <?= json_encode($monthlyDeliveryTrend) ?>,
        topPackageTypes: <?= json_encode($topPackageTypes) ?>,
        deliveriesPerProject: <?= json_encode($deliveriesPerProject) ?>,
        activityLogActions: <?= json_encode($activityLogActions) ?>,
        selectedProject: <?= json_encode($selectedProject) ?>
    };
</script>

<script src="assets/js/charts.js"></script>

<?php require "template/footer.php"; ?>