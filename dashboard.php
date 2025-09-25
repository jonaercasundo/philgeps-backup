<?php 
require "template/header.php"; 
require "config/db.php";

// Get selected project from URL parameter
$selectedProject = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

  try {
    // Get all projects for the dropdown
    $stmt = $pdo->query("SELECT project_id, project_name FROM projects ORDER BY project_name");
    $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $params = [];
    $whereClause = "";
    if ($selectedProject > 0) {
      $whereClause = " WHERE p.project_id = :project_id";
      $params[':project_id'] = $selectedProject;
    }

    // Build WHERE clause based on selected project
    $projectFilter = $selectedProject > 0 ? "WHERE p.project_id = $selectedProject" : "";
    $deliveryProjectFilter = $selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "";
    $invoiceProjectFilter = $selectedProject > 0 ? "WHERE project_id = $selectedProject" : "";

    // Project Progress (%)
    $stmt = $pdo->query("
        SELECT 
            p.project_id,
            p.project_name,
            COUNT(d.delivery_id) AS total,
            SUM(d.status='Delivered') AS delivered,
            SUM(d.status='Accepted') AS accepted,
            ROUND(
                ( (SUM(d.status='Delivered') + (SUM(d.status='Accepted')/2)) / COUNT(d.delivery_id) ) * 100
            , 1) AS progress
        FROM projects p
        LEFT JOIN deliveries d ON p.project_id = d.project_id
        $projectFilter
        GROUP BY p.project_id, p.project_name
        ORDER BY p.project_name
    ");
    $projectProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Places Delivered (distinct schools reached per project/region)
    $stmt = $pdo->query("
        SELECT 
            s.region,
            p.project_id,
            p.project_name,
            COUNT(DISTINCT s.school_id) AS total_schools,
            SUM(CASE WHEN d.status = 'Delivered' THEN 1 ELSE 0 END) AS delivered_count
        FROM deliveries d
        JOIN school    s ON d.school_id    = s.school_id
        JOIN projects p ON d.project_id  = p.project_id
        " . ($selectedProject > 0 ? "WHERE p.project_id = $selectedProject" : "") . "
        GROUP BY s.region, p.project_id, p.project_name
        ORDER BY p.project_name, s.region
    ");
    $placesDelivered = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // Projects per Year (filtered)
    $stmt = $pdo->query("
        SELECT YEAR(start_date) AS year, COUNT(*) AS total 
        FROM projects 
        WHERE status='Ongoing' " . ($selectedProject > 0 ? "AND project_id = $selectedProject" : "") . "
        GROUP BY YEAR(start_date) 
        ORDER BY year
    ");
    $projectsPerYear = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Projects by Agency (filtered)
    $stmt = $pdo->query("
        SELECT agency, COUNT(*) AS total 
        FROM projects 
        " . ($selectedProject > 0 ? "WHERE project_id = $selectedProject" : "") . "
        GROUP BY agency 
        ORDER BY total DESC
    ");
    $projectsByAgency = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total Contract Amount per Year (filtered)
    $stmt = $pdo->query("
        SELECT YEAR(start_date) AS year, SUM(contract_amount) AS total 
        FROM projects 
        WHERE status='Ongoing' " . ($selectedProject > 0 ? "AND project_id = $selectedProject" : "") . "
        GROUP BY YEAR(start_date) 
        ORDER BY year
    ");
    $amountPerYear = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 1. Delivery Status Overview (filtered)
    $stmt = $pdo->query("
        SELECT d.status, COUNT(*) as total 
        FROM deliveries d
        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY d.status 
        ORDER BY total DESC
    ");
    $deliveryStatusOverview = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Monthly Delivery Trend (filtered)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(d.delivery_date, '%Y-%m') AS month, 
            COUNT(*) as total
        FROM deliveries d
        WHERE d.delivery_date IS NOT NULL " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY month
        ORDER BY month
    ");
    $monthlyDeliveryTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Top Package Types Delivered (filtered)
    $stmt = $pdo->query("
        SELECT d.package_type, COUNT(*) as total 
        FROM deliveries d
        WHERE d.package_type IS NOT NULL " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY d.package_type 
        ORDER BY total DESC
    ");
    $topPackageTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Deliveries per Project (filtered or all)
    if ($selectedProject > 0) {
      $stmt = $pdo->query("
          SELECT 
              p.project_name,
              p.project_id,
              COUNT(d.delivery_id) as total 
          FROM deliveries d
          JOIN projects p ON d.project_id = p.project_id
          WHERE p.project_id = $selectedProject
          GROUP BY p.project_id, p.project_name
          ORDER BY total DESC
      ");
    } else {
      $stmt = $pdo->query("
          SELECT 
              p.project_name,
              p.project_id,
              COUNT(d.delivery_id) as total 
          FROM deliveries d
          JOIN projects p ON d.project_id = p.project_id
          GROUP BY p.project_id, p.project_name
          ORDER BY total DESC
      ");
    }
    $deliveriesPerProject = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Activity Log Actions (filtered if project is selected)
    $stmt = $pdo->query("
      SELECT action, COUNT(*) as total
      FROM activity_logs
      WHERE action IS NOT NULL
      GROUP BY action
      ORDER BY total DESC;

    ");
    $activityLogActions = $stmt->fetchAll(PDO::FETCH_ASSOC);

  } catch (PDOException $e) {
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

  <!-- Chart 2: Projects by Agency -->
  <div class="col-lg-4 col-md-6 mb-4 chart-item" data-chart-id="projects-agency">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">🏢 Projects by Agency</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="projectsByAgencyChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 3: Top Package Types -->
  <div class="col-lg-4 col-md-6 mb-4 chart-item" data-chart-id="package-types">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📦 Top Package Types</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="topPackageTypesChart" height="200"></canvas>
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

  <!-- Chart 5: Activity Log Actions -->
  <div class="col-lg-4 col-md-6 mb-4 chart-item" data-chart-id="activity-logs">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📋 Activity Log Actions</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="activityLogActionsChart" height="150"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 6: Project Progress -->
  <div class="col-lg-6 col-md-6 mb-4 chart-item" data-chart-id="project-progress">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">✅ Project Progress (%)</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="projectProgressChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 7: Deliveries per Project -->
  <div class="col-lg-6 col-md-6 mb-4 chart-item" data-chart-id="deliveries-project">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">🚚 Deliveries per Project</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="deliveriesPerProjectChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 8: Projects per Year -->
  <div class="col-lg-6 col-md-6 mb-4 chart-item" data-chart-id="projects-year">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📅 Projects per Year</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="projectsPerYearChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 9: Total Contract Amount per Year -->
  <div class="col-lg-6 col-md-6 mb-4 chart-item" data-chart-id="amount-year">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">💰 Total Contract Amount per Year</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="amountPerYearChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 10: Places Delivered -->
  <div class="col-12 mb-4 chart-item" data-chart-id="places-delivered">
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
  // This object contains all the data fetched by PHP, encoded as JSON.
  // It acts as the bridge between your backend and the external JS file.
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