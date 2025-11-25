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
     // PROJECT SUMMARY //

    // BUDGET VARIANCE QUERY START
    $opportunityQuery = "
                SELECT 
            p.project_name,
            '' AS end_user,
            p.agency AS procuring_entity,
            '' AS bid_opening,
            p.ABC,
            (COALESCE(p.contract_amount, 0) / NULLIF(p.ABC, 0)) * 100 AS percent_to_win,
            ((p.ABC - p.contract_amount) / NULLIF(p.ABC, 0)) * 100 AS lcb,
            p.contract_amount,
            0 AS net_sales,
            COALESCE((
                SELECT SUM(i.supplier_price * COALESCE(pc.qty, 0))
                FROM item i
                LEFT JOIN package_content pc ON i.item_id = pc.item_id
                WHERE i.project_id = p.project_id
            ), 0) AS cogs,
            0 AS pf1,
            0 AS pf1_percent,
            0 AS pf2,
            0 AS pf2_percent,
            0 AS ll_com,
            0 AS shipping_brokerage,
            0 AS logistics_door_to_door,
            0 AS warehouse_rental,
            0 AS other_expenses,
            0 AS tax_lawyer_allowance,
            0 AS rd_cost,
            0 AS manpower_others,
            0 AS facilitation,
            0 AS assembly_service_center,
            0 AS interest_dst,
            0 AS business_permit_tax,
            0 AS performance_bond,
            0 AS goods_insurance,
            0 AS total_cost_of_sales,
            0 AS pgp,
            0 AS gpm,
            0 AS opex,
            0 AS income_tax_provision,
            0 AS incentive,
            0 AS ppl,
            0 AS npm,
            (p.ABC - p.contract_amount) AS variance
        FROM projects p
        ORDER BY ABS(p.ABC - p.contract_amount) DESC
    ";

    $stmt = $pdo->query($opportunityQuery);
    $opportunity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // BUDGET VARIANCE QUERY END

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
  <h2>Sales Generation Dashboard</h2>
  <div class="btn-group">
    <button class="btn btn-outline-primary btn-sm" id="resetLayout">
      Reset Layout
    </button>
    <button class="btn btn-outline-primary btn-sm" id="toggleDrag">
      Toggle Drag
    </button>
    <a href="dashboard.php" class="btn btn-outline-primary btn-sm" role="button">
      Back
    </a>
  </div>
</div>

<div class="container-fluid">
  <!-- <a href="dashboard_.php<?= isset($_GET['project_id']) ? '?project_id=' . urlencode($_GET['project_id']) : '' ?>"
      class="text-primary small fw-semibold text-decoration-none d-inline-flex align-items-center gap-1 mt-1 mx-2">
      View Project Performance <i class="bi bi-arrow-right-short fs-5"></i>
  </a> -->
  <div class="row g-4 mb-4" id="draggable-dashboard">
    <div class="col-lg-6 col-md-12 chart-item" data-chart-id="project-status">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Projects Status</h6>
          <div class="d-flex gap-2">
            <a href="report/print_delivery_status.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" 
              class="text-decoration-none text-dark" 
              target="_blank" 
              title="Print Report">
              <i class="bi bi-printer"></i>
            </a>
            <span class="drag-handle text-muted" style="cursor: grab;" title="Drag to reorder">⋮⋮</span>
          </div>
        </div>
        <div class="card-body">
          <canvas id="projectStatusChart" height="340"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6 col-md-12 chart-item" data-chart-id="budget-variance">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Budget Variance</h6>
          <span class="drag-handle text-muted" style="cursor: grab;" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body" style="height: 400px; overflow-y: auto;">
            <canvas id="opportunityChart" width="600" height="340"></canvas>
        </div>
      </div>
    </div>

    <!-- Budget Variance Table -->
    <div class="col-12 chart-item" data-chart-id="budget-variance-table">
      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">Budget Variance Table</h6>
          <span class="drag-handle text-muted" style="cursor: grab;" title="Drag to reorder">⋮⋮</span>
        </div>
        <div class="card-body">
          <div class="table-wrapper">
            <table id="budgetVarianceTable" class="table table-striped table-hover table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th>Project Name</th>
                  <th>End User</th>
                  <th>Procuring Entity</th>
                  <th>Bid Opening</th>
                  <th>ABC</th>
                  <th>% to Win</th>
                  <th>LCB</th>
                  <th>Contract Amount</th>
                  <th>Net Sales</th>
                  <th>COGS</th>
                  <th>PF1</th>
                  <th>PF1 %</th>
                  <th>PF2</th>
                  <th>PF2 %</th>
                  <th>LL COM</th>
                  <th>Shipping/Brokerage</th>
                  <th>Logistics (D2D)</th>
                  <th>Warehouse Rental</th>
                  <th>Other Expenses</th>
                  <th>Allowance for Tax Lawyer</th>
                  <th>R&D</th>
                  <th>Manpower/Others</th>
                  <th>Facilitation</th>
                  <th>Assembly/Service Center</th>
                  <th>Interest & DST for working capital</th>
                  <th>Business Permit / Municipal Tax</th>
                  <th>Performance Bond</th>
                  <th>Goods Insurance</th>
                  <th>Total Cost of Sales</th>
                  <th>PGP</th>
                  <th>GPM</th>
                  <th>OPEX</th>
                  <th>Income Tax Provision</th>
                  <th>Incentive</th>
                  <th>PPL</th>
                  <th>NPM</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($opportunity as $item): ?>
                <tr>
                  <td class="text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip" title="<?= htmlspecialchars($item['project_name']) ?>">
                      <?= htmlspecialchars($item['project_name']) ?>
                  </td>
                  <td><?= htmlspecialchars($item['end_user']) ?></td>
                  <td><?= htmlspecialchars($item['procuring_entity']) ?></td>
                  <td><?= $item['bid_opening'] ? date('Y-m-d', strtotime($item['bid_opening'])) : 'N/A' ?></td>
                  <td>₱<?= number_format($item['ABC'], 2) ?></td>
                  <td><?= number_format($item['percent_to_win'], 2) ?>%</td>
                  <td><?= number_format($item['lcb'], 2) ?>%</td>
                  <td>₱<?= number_format($item['contract_amount'], 2) ?></td>
                  <td>₱<?= number_format($item['net_sales'], 2) ?></td>
                  <td>₱<?= number_format($item['cogs'], 2) ?></td>
                  <td>₱<?= number_format($item['pf1'], 2) ?></td>
                  <td><?= number_format($item['pf1_percent'], 2) ?>%</td>
                  <td>₱<?= number_format($item['pf2'], 2) ?></td>
                  <td><?= number_format($item['pf2_percent'], 2) ?>%</td>
                  <td>₱<?= number_format($item['ll_com'], 2) ?></td>
                  <td>₱<?= number_format($item['shipping_brokerage'], 2) ?></td>
                  <td>₱<?= number_format($item['logistics_door_to_door'], 2) ?></td>
                  <td>₱<?= number_format($item['warehouse_rental'], 2) ?></td>
                  <td>₱<?= number_format($item['other_expenses'], 2) ?></td>
                  <td>₱<?= number_format($item['tax_lawyer_allowance'], 2) ?></td>
                  <td>₱<?= number_format($item['rd_cost'], 2) ?></td>
                  <td>₱<?= number_format($item['manpower_others'], 2) ?></td>
                  <td>₱<?= number_format($item['facilitation'], 2) ?></td>
                  <td>₱<?= number_format($item['assembly_service_center'], 2) ?></td>
                  <td>₱<?= number_format($item['interest_dst'], 2) ?></td>
                  <td>₱<?= number_format($item['business_permit_tax'], 2) ?></td>
                  <td>₱<?= number_format($item['performance_bond'], 2) ?></td>
                  <td>₱<?= number_format($item['goods_insurance'], 2) ?></td>
                  <td>₱<?= number_format($item['total_cost_of_sales'], 2) ?></td>
                  <td>₱<?= number_format($item['pgp'], 2) ?></td>
                  <td><?= number_format($item['gpm'], 2) ?>%</td>
                  <td>₱<?= number_format($item['opex'], 2) ?></td>
                  <td>₱<?= number_format($item['income_tax_provision'], 2) ?></td>
                  <td>₱<?= number_format($item['incentive'], 2) ?></td>
                  <td>₱<?= number_format($item['ppl'], 2) ?></td>
                  <td><?= number_format($item['npm'], 2) ?>%</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th>TOTAL</th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
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
        projectStatusOverview: <?= json_encode($projectStatusOverview) ?>,
        opportunity: <?= json_encode($opportunity) ?>,
    };
</script>

<script src="assets/js/sortable.js"></script>
<script src="assets/js/dashboard_sales_generation.js"></script>

<?php require "template/footer.php"; ?>

<script>
$(document).ready(function() {
  var table = $('#budgetVarianceTable').DataTable({
    processing: true,
    scrollX: true,
    scrollY: "53vh",
    scrollCollapse: true,
    paging: true,
    responsive: false,
    fixedColumns: {
      leftColumns: 1
    },
    order: [[4, 'desc']], // Order by ABC
    language: {
      search: "Search projects:",
      lengthMenu: "Show _MENU_ projects per page",
      info: "Showing _START_ to _END_ of _TOTAL_ projects",
      infoEmpty: "No projects available",
      infoFiltered: "(filtered from _MAX_ total projects)",
      emptyTable: "No budget variance records found",
      zeroRecords: "No matching projects found"
    },
    columnDefs: [
      { targets: [4,6,7,8,9,10,12,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,31,32,33,34], className: 'text-end' },
      { targets: [5,11,13,30,35], className: 'text-end' }
    ],
    footerCallback: function(row, data, start, end, display) {
      var api = this.api();
      
      // Helper function to parse currency and calculate total
      var intVal = function(i) {
        return typeof i === 'string' ?
          parseFloat(i.replace(/[₱,]/g, '')) || 0 :
          typeof i === 'number' ? i : 0;
      };

      // Columns to total (indices of monetary columns)
      var columnsToTotal = [4, 7, 8, 9, 10, 12, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 31, 32, 33, 34];
      
      columnsToTotal.forEach(function(colIdx) {
        var total = api
          .column(colIdx, {page: 'current'})
          .data()
          .reduce(function(a, b) {
            return intVal(a) + intVal(b);
          }, 0);
        
        $(api.column(colIdx).footer()).html('₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      });
      
      // For percentage columns, you might want to calculate average instead
      var percentColumns = [5, 6, 11, 13, 30, 35];
      percentColumns.forEach(function(colIdx) {
        var values = api
          .column(colIdx, {page: 'current'})
          .data()
          .toArray()
          .map(function(val) {
            return parseFloat(String(val).replace('%', '')) || 0;
          });
        
        var avg = values.length > 0 ? values.reduce((a, b) => a + b, 0) / values.length : 0;
        $(api.column(colIdx).footer()).html(avg.toFixed(2) + '%');
      });
    }
  });
});
</script>