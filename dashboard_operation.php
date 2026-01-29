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

    // Get warehouse list for dropdown
    $warehouses = $pdo->query("SELECT warehouse_id, warehouse_name FROM warehouse ORDER BY warehouse_name")->fetchAll();
    $warehouseId = $_GET['warehouse_id'] ?? ($warehouses[0]['warehouse_id'] ?? 0);

    $projectFilter = $selectedProject > 0 ? "WHERE p.project_id = $selectedProject" : "";
    // Get progress by region data
    $progressPerRegionQuery = "
        SELECT 
            s.region,
            COUNT(*) AS total,
            SUM(CASE WHEN d.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN d.status = 'accepted' THEN 1 ELSE 0 END) AS accepted
        FROM deliveries d
        JOIN school s ON s.school_id = d.school_id
        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY s.region
        ORDER BY s.region
    ";
    $stmt = $pdo->query($progressPerRegionQuery);
    $progressPerRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get progress by lot data
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

    // Get inventory by warehouse data
    $inventoryByWarehouseQuery = "
        SELECT 
            w.warehouse_name,
            i.item_name,
            i.unit,
            IFNULL(ih.new_qty, 0) AS actual_qty,
            IFNULL(eq.expected_qty, 0) AS expected_qty,
            (IFNULL(ih.new_qty, 0) - IFNULL(eq.expected_qty, 0)) AS variance
        FROM (
            SELECT warehouse_id, item_id FROM (
                SELECT DISTINCT ih.warehouse_id, ih.item_id
                FROM inventory_history ih
                JOIN item i ON ih.item_id = i.item_id
                WHERE " . ($selectedProject > 0 ? "i.project_id = $selectedProject AND" : "") . "
                  ih.changed_at = (
                      SELECT MAX(ih2.changed_at)
                      FROM inventory_history ih2
                      WHERE ih2.item_id = ih.item_id 
                        AND ih2.warehouse_id = ih.warehouse_id
                        AND DATE(ih2.changed_at) <= :selectedDate
                  )
                  AND DATE(ih.changed_at) <= :selectedDate
                
                UNION
                
                SELECT DISTINCT w2.warehouse_id, pc.item_id
                FROM (
                    SELECT DISTINCT d.delivery_id, d.school_id, d.project_id, ps.package_id
                    FROM deliveries d
                    JOIN package_status ps ON d.delivery_id = ps.delivery_id
                    WHERE d.status = 'pending' 
                      AND ps.status IN ('pending','for approval')
                      " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
                ) d_packages
                JOIN school s ON d_packages.school_id = s.school_id
                JOIN package_content pc ON d_packages.package_id = pc.package_id
                JOIN warehouse w2 ON (
                    (w2.warehouse_address = 'Pampanga' AND s.region IN ('Region I', 'Region II', 'Region III', 'Region IV-A', 'Region IV-B', 'MIMAROPA', 'Region V', 'CAR', 'NCR')) OR
                    (w2.warehouse_address = 'Cebu' AND s.region IN ('Region VI', 'Region VII', 'Region VIII')) OR
                    (w2.warehouse_address = 'Davao' AND s.region IN ('Region IX', 'Region X', 'Region XI', 'Region XII', 'Region XIII', 'CARAGA', 'BARMM'))
                )
            ) combined
        ) all_items
        JOIN item i ON all_items.item_id = i.item_id
        JOIN warehouse w ON all_items.warehouse_id = w.warehouse_id
        -- Actual Quantity
        LEFT JOIN (
            SELECT ih.warehouse_id, ih.item_id, ih.new_qty
            FROM inventory_history ih
            WHERE ih.changed_at = (
                SELECT MAX(ih2.changed_at)
                FROM inventory_history ih2
                WHERE ih2.item_id = ih.item_id 
                  AND ih2.warehouse_id = ih.warehouse_id
                  AND DATE(ih2.changed_at) <= :selectedDate
            )
            AND DATE(ih.changed_at) <= :selectedDate
        ) ih ON ih.warehouse_id = all_items.warehouse_id AND ih.item_id = all_items.item_id
        -- Expected Quantity
        LEFT JOIN (
            SELECT 
                w2.warehouse_id,
                pc.item_id,
                SUM(pc.qty) AS expected_qty
            FROM (
                SELECT DISTINCT d.delivery_id, d.school_id, d.project_id, ps.package_id
                FROM deliveries d
                JOIN package_status ps ON d.delivery_id = ps.delivery_id
                WHERE d.status = 'pending' 
                  AND ps.status IN ('pending','for approval')
                  " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
            ) d_packages
            JOIN school s ON d_packages.school_id = s.school_id
            JOIN package_content pc ON d_packages.package_id = pc.package_id
            JOIN warehouse w2 ON (
                (w2.warehouse_address = 'Pampanga' AND s.region IN ('Region I', 'Region II', 'Region III', 'Region IV-A', 'Region IV-B', 'MIMAROPA', 'Region V', 'CAR', 'NCR')) OR
                (w2.warehouse_address = 'Cebu' AND s.region IN ('Region VI', 'Region VII', 'Region VIII')) OR
                (w2.warehouse_address = 'Davao' AND s.region IN ('Region IX', 'Region X', 'Region XI', 'Region XII', 'Region XIII', 'CARAGA', 'BARMM'))
            )
            GROUP BY w2.warehouse_id, pc.item_id
        ) eq ON eq.warehouse_id = all_items.warehouse_id AND eq.item_id = all_items.item_id
        WHERE (IFNULL(ih.new_qty, 0) > 0 OR IFNULL(eq.expected_qty, 0) > 0)
        ORDER BY w.warehouse_name, i.item_name
    ";

    $stmt = $pdo->prepare($inventoryByWarehouseQuery);
    $stmt->execute(['selectedDate' => $selectedDate]);
    $inventoryByWarehouse = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get expected vs actual deliveries by warehouse
    $deliveriesByWarehouseQuery = "
        SELECT 
            w.warehouse_id,
            w.warehouse_name,

            -- Expected: all deliveries in warehouse region scope
            COUNT(DISTINCT d.delivery_id) AS expected_deliveries,

            -- Actual: delivered + accepted only
            COUNT(DISTINCT CASE 
                WHEN d.status IN ('delivered','accepted') 
                THEN d.delivery_id 
            END) AS actual_deliveries

        FROM deliveries d
        JOIN school s 
            ON d.school_id = s.school_id

        JOIN warehouse w 
            ON (
                -- LUZON → Pampanga
                (w.warehouse_address = 'Pampanga' AND s.region IN (
                    'Region I','Region II','Region III','Region IV-A','Region IV-B',
                    'MIMAROPA','Region V','CAR','NCR'
                )) OR

                -- VISAYAS → Cebu
                (w.warehouse_address = 'Cebu' AND s.region IN (
                    'Region VI','Region VII','Region VIII'
                )) OR

                -- MINDANAO → Davao
                (w.warehouse_address = 'Davao' AND s.region IN (
                    'Region IX','Region X','Region XI','Region XII',
                    'Region XIII','CARAGA','BARMM'
                ))
            )

        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "

        AND w.warehouse_name IN ('Pampanga','Cebu','Davao')

        GROUP BY w.warehouse_id, w.warehouse_name
        ORDER BY w.warehouse_name
    ";

    $stmt = $pdo->query($deliveriesByWarehouseQuery);
    $deliveriesByWarehouse = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    /** // NOT USED //
    
    $changesPerWarehouseQuery = "
        SELECT 
            w.warehouse_name,
            COUNT(*) AS total_changes
        FROM inventory_history ih
        JOIN warehouse w ON ih.warehouse_id = w.warehouse_id
        GROUP BY w.warehouse_name
        ORDER BY total_changes DESC
    ";
    $stmt = $pdo->query($changesPerWarehouseQuery);
    $changesPerWarehouse = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    $topUpdatedItemsQuery = "
        SELECT 
            i.item_name,
            COUNT(*) AS update_count
        FROM inventory_history ih
        JOIN item i ON ih.item_id = i.item_id
        GROUP BY i.item_name
        ORDER BY update_count DESC
        LIMIT 5
    ";
    $stmt = $pdo->query($topUpdatedItemsQuery);
    $topUpdatedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    
    $stmt = $pdo->query($placesQuery);
    $placesDelivered = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // NOT USED //*/

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
  <h2>Production Performance</h2>
  <div class="btn-group">
    <a href="dashboard_production.php" class="btn btn-outline-primary btn-sm" role="button">
      Back
    </a>
  </div>
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

    
    <!-- Deliveries by Warehouse Chart -->
    <div class="col-md-12 chart-item" data-chart-id="deliveries-by-warehouse">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0 fw-bold">Expected vs Actual deliveries by Warehouse</h6>
            </div>
        </div>
        <div class="card-body">
          <canvas id="deliveriesByWarehouseChart" height="300"></canvas>
        </div>
      </div>
    </div>

    <!-- Inventory by Warehouse -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">📦 Inventory by Warehouse <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h6>
          <a href="report/print_inventory_warehouse.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a>
        </div>
        <div class="card-body">
          <!-- Date Filter Form -->
          <form method="GET" class="row mb-3" id="dateFilterForm">
            <?php if($selectedProject > 0): ?>
              <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
            <?php endif; ?>
            
            <div class="col-md-4">
              <input type="date" class="form-control form-control-sm" id="dateFilter" name="selectedDate" 
                    value="<?php echo htmlspecialchars($selectedDate); ?>">
            </div>
            <div class="col-md-4 align-self-end">
              <button type="submit" class="btn btn-primary btn-sm">Apply Date Filter</button>
            </div>
            <?php if(isset($selectedDate) && $selectedDate !== date('Y-m-d')): ?>
            <div class="col-md-4 align-self-end text-end">
              <small class="text-muted">
                <i class="fas fa-history"></i> Date: <?php echo htmlspecialchars($selectedDate); ?>
              </small>
            </div>
            <?php endif; ?>
          </form>
          
          <div id="warehouseChartsContainer" class="row"></div>
        </div>
      </div>
    </div>

    <!-- Progress by Region Charts -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">✅ Accepted by Region (%)</h6>
          <a href="report/print_accepted_region.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a>
        </div>
        <div class="card-body">
          <canvas id="acceptedPerRegionChart" height="250"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">🚚 Delivered by Region (%)</h6>
          <a href="report/print_delivered_region.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a>
        </div>
        <div class="card-body">
          <canvas id="deliveredPerRegionChart" height="250"></canvas>
        </div>
      </div>
    </div>

    <!-- Progress by Lot Charts -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">✅ Accepted by Lot (%)</h6>
          <a href="report/print_accepted_lot.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a>
        </div>
        <div class="card-body">
          <canvas id="acceptedPerLotChart" height="250"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0">🚚 Delivered by Lot (%)</h6>
          <a href="report/print_accepted_lot.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="text-decoration-none text-dark" target="_blank">
            <i class="bi bi-printer"></i>
          </a>
        </div>
        <div class="card-body">
          <canvas id="deliveredPerLotChart" height="250"></canvas>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const phpData = {
        progressPerRegion: <?= json_encode($progressPerRegion) ?>,
        progressPerLot: <?= json_encode($progressPerLot) ?>,
        inventoryByWarehouse: <?= json_encode($inventoryByWarehouse) ?>,
        deliveriesByWarehouse: <?= json_encode($deliveriesByWarehouse) ?>,
        selectedProject: <?= json_encode($selectedProject) ?>, 
    };
</script>

<script src="assets/js/charts_operation.js"></script>

<?php require "template/footer.php"; ?>