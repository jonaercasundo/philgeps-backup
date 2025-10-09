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
                (SELECT COUNT(*) FROM deliveries WHERE status='pending' AND project_id = $selectedProject) AS pending,
                (SELECT COUNT(*) FROM deliveries WHERE status='accepted' AND project_id = $selectedProject) AS accepted,
                (SELECT COUNT(*) FROM deliveries WHERE status='delivered' AND project_id = $selectedProject) AS delivered
            FROM projects WHERE project_id = $selectedProject
        ");
    } else {
        $stmt = $pdo->query("
            SELECT
                SUM(CASE WHEN status='Ongoing' THEN 1 ELSE 0 END) AS activeProjects,
                (SELECT COUNT(*) FROM deliveries WHERE status='pending') AS pending,
                (SELECT COUNT(*) FROM deliveries WHERE status='accepted') AS accepted,
                (SELECT COUNT(*) FROM deliveries WHERE status='delivered') AS delivered
            FROM projects
        ");
    }

    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate progress percentages AFTER getting $totals
    $totalDeliveries = ($totals['pending'] ?? 0) + ($totals['accepted'] ?? 0) + ($totals['delivered'] ?? 0);
    $pendingPercent = $totalDeliveries > 0 ? round(($totals['pending'] / $totalDeliveries) * 100) : 0;
    $acceptedPercent = $totalDeliveries > 0 ? round(($totals['accepted'] / $totalDeliveries) * 100) : 0;
    $deliveredPercent = $totalDeliveries > 0 ? round(($totals['delivered'] / $totalDeliveries) * 100) : 0;

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
    
    $stmt = $pdo->query($deliveryStatusQuery);
    $deliveryStatusOverview = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // 3. Today's User Activity by Warehouse/Office
    $todayActivityQuery = "
        SELECT 
          DATE_FORMAT(al.created_at, '%H:%i') as time_label,
          HOUR(al.created_at) as hour,
          MINUTE(al.created_at) as minute,
          CASE 
              WHEN u.warehouse_id IS NULL THEN 'Office'
              ELSE w.warehouse_name
          END AS activity_type,
          COUNT(*) as total_activities,
          CONCAT( al.action ) as activity_list
      FROM activity_logs al
      JOIN users u ON al.user_id = u.user_id
      LEFT JOIN warehouse w ON u.warehouse_id = w.warehouse_id
      WHERE DATE(al.created_at) = CURDATE()
      GROUP BY 
          DATE_FORMAT(al.created_at, '%H:%i'),
          CASE 
              WHEN u.warehouse_id IS NULL THEN 'Office'
              ELSE w.warehouse_name
          END
      ORDER BY hour, minute, activity_type
    ";

    $stmt = $pdo->query($todayActivityQuery);
    $todayUserActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Inventory Quantities (sum by warehouse)
    $inventoryQuery = "
        SELECT 
            ii.item_name, i.qty, w.warehouse_name
        FROM inventory i
        JOIN item ii ON i.item_id = ii.item_id
        JOIN warehouse w ON i.warehouse_id = w.warehouse_id
        WHERE inventory_status = 'Approved'
    ";
    $stmt = $pdo->query($inventoryQuery);
    $inventoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Inventory Items by Warehouse (Only Approved)
    $inventoryByWarehouseQuery = "
        SELECT 
            w.warehouse_name,
            i.item_name,
            i.unit,
            inv.qty
        FROM inventory inv
        JOIN item i ON inv.item_id = i.item_id
        JOIN warehouse w ON inv.warehouse_id = w.warehouse_id
        WHERE inv.inventory_status = 'Approved'
            AND inv.qty > 0
            " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
        ORDER BY w.warehouse_name, i.item_name
    ";

    $stmt = $pdo->query($inventoryByWarehouseQuery);
    $inventoryByWarehouse = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
// Add missing variables with empty data for now
// $projectsPerYear = [];
// $projectsByAgency = [];
// $amountPerYear = [];
// $projectProgress = [];
// $topPackageTypes = [];
// $deliveriesPerProject = [];


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
      ['title'=>'Pending','value'=>$totals['pending'] ?? 0,'class'=>'warning','icon'=>'⏳', 'percent'=>$pendingPercent],
      ['title'=>'Accepted','value'=>$totals['accepted'] ?? 0,'class'=>'info','icon'=>'✅', 'percent'=>$acceptedPercent],
      ['title'=>'Delivered','value'=>$totals['delivered'] ?? 0,'class'=>'success','icon'=>'📦', 'percent'=>$deliveredPercent]
  ];
  foreach($cards as $c): ?>
  <div class="col-md-3 mb-3">
    <div class="card text-bg-<?=$c['class']?> shadow-sm h-100">
      <div class="card-body text-center">
        <div style="font-size: 2rem; margin-bottom: 10px;"><?=$c['icon']?></div>
        <h6 class="card-title"><?= $c['title'] ?></h6>
        <h4 class="mb-0"><strong><?= $c['value'] ?></strong></h4>
        
        <!-- Progress Bar for delivery status cards -->
        <?php if (isset($c['percent'])): ?>
        <div class="mt-2">
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $c['percent'] ?>%;" 
                 aria-valuenow="<?= $c['percent'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <small class="text-light opacity-75"><?= $c['percent'] ?>%</small>
        </div>
        <?php endif; ?>
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


  <!-- Chart 2: Monthly Delivery Trend -->
  <div class="col-lg-6 col-md-12 mb-4 chart-item" data-chart-id="monthly-trend">
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

  <!-- Chart 3: Today's User Activity -->
  <div class="col-lg-4 col-md-6 mb-4 chart-item" data-chart-id="today-activity">
      <div class="card shadow-sm h-100">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h6 class="mb-0">🕜 Today's User Activity</h6>
              <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
          </div>
          <div class="card-body">
              <canvas id="todayActivityChart" height="250"></canvas>
          </div>
      </div>
  </div>

  <!-- Chart 4: Places Delivered -->
  <div class="col-lg-6 mb-4 chart-item" data-chart-id="places-delivered">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📍 School Density</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="placesDeliveredChart" height="300"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart: Inventory Quantities per Warehouse -->
  <div class="col-lg-4 mb-4 chart-item" data-chart-id="inventory-quantities">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📦 Inventory Quantity</h6>
        <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
      </div>
      <div class="card-body">
        <canvas id="inventoryChart" height="250"></canvas>
      </div>
    </div>
  </div>

  <!-- Inventory by Warehouse -->
  <div class="col-12 mb-4 chart-item" data-chart-id="inventory-warehouse">
      <div class="card shadow-sm h-100">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h6 class="mb-0">📦 Inventory by Warehouse <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h6>
              <span class="drag-handle text-muted" title="Drag to reorder">⋮⋮</span>
          </div>
          <div class="card-body">
              <div id="warehouseChartsContainer" class="row"></div>
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
        placesDelivered: <?= json_encode($placesDelivered) ?>,
        deliveryStatusOverview: <?= json_encode($deliveryStatusOverview) ?>,
        monthlyDeliveryTrend: <?= json_encode($monthlyDeliveryTrend) ?>,
        todayUserActivity: <?= json_encode($todayUserActivity) ?>,
        inventoryData: <?= json_encode($inventoryData) ?>,
        inventoryByWarehouse: <?= json_encode($inventoryByWarehouse) ?>,
        selectedProject: <?= json_encode($selectedProject) ?>
    };
</script>

<script src="assets/js/charts.js"></script>

<?php require "template/footer.php"; ?>