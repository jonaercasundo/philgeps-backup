<?php 
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

$allowed_roles = ['Super Admin', 'Office Admin'];
redirectIfNotAuthorized($allowed_roles, 'index.php');

$selectedProject = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

try {
    // Fetch all projects with their status
    $stmt = $pdo->query("SELECT project_id, project_name, status FROM projects ORDER BY project_name");
    $allProjectsWithStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $allProjects = $allProjectsWithStatus; // For compatibility

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
                WHEN 'Pending Evaluation' THEN 'Upcoming'
                WHEN 'For Award' THEN 'For Award'
                WHEN 'For Implementation' THEN 'For Implementation'
                WHEN 'Ongoing' THEN 'Ongoing'
                WHEN 'Delivered' THEN 'Completed'
                WHEN 'Completed' THEN 'Collected'
                ELSE p.status
            END AS status
        FROM projects p
        GROUP BY 
            CASE p.status
                WHEN 'Pending Evaluation' THEN 'Upcoming'
                WHEN 'For Award' THEN 'For Award'
                WHEN 'For Implementation' THEN 'For Implementation'
                WHEN 'Ongoing' THEN 'Ongoing'
                WHEN 'Delivered' THEN 'Completed'
                WHEN 'Completed' THEN 'Collected'
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
            project_name,
            contract_amount,
            ABC,
            (ABC - contract_amount) AS variance
        FROM projects
        ORDER BY ABS(ABC - contract_amount) DESC
    ";

    $stmt = $pdo->query($opportunityQuery);
    $opportunity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // BUDGET VARIANCE QUERY END

    // PROJECT SALES QUERY //
    $projectSalesQuery = "
        SELECT 
            sg.sales_gen_id,
            p.project_id,
            p.project_name,
            p.ABC as abc,
            p.contract_amount,
            sg.net_sales,
            sg.cogs,
            sg.total_cost_of_sales,
            sg.pgp,
            sg.gpm,
            sg.opex,
            sg.ppl,
            sg.npm
        FROM sales_generation sg
        INNER JOIN projects p ON sg.project_id = p.project_id
        $projectFilter
        ORDER BY p.ABC DESC
    ";

    $stmt = $pdo->query($projectSalesQuery);
    $projectSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <table id="projectSalesTable" class="table table-striped table-hover table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th>Project Title</th>
                  <th>ABC</th>
                  <th>Contract Amount</th>
                  <th>Net Sales</th>
                  <th>COGS</th>
                  <th>Total Cost of Sales</th>
                  <th>PGP</th>
                  <th>GPM</th>
                  <th>OPEX</th>
                  <th>PPL</th>
                  <th>NPM</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($projectSales as $item): ?>
                <tr data-sales-gen-id="<?= $item['sales_gen_id'] ?>">
                  <td contenteditable="true" class="text-truncate" style="max-width: 250px;">
                    <?= htmlspecialchars($item['project_name']) ?>
                  </td>
                  <td contenteditable="true">₱<?= number_format($item['abc'], 2) ?></td>
                  <td contenteditable="true">₱<?= number_format($item['contract_amount'], 2) ?></td>
                  <td contenteditable="true" data-field="net_sales"><?= number_format($item['net_sales'], 2) ?>%</td>
                  <td contenteditable="true" data-field="cogs">₱<?= number_format($item['cogs'], 2) ?></td>
                  <td contenteditable="true" data-field="total_cost_of_sales">₱<?= number_format($item['total_cost_of_sales'], 2) ?></td>
                  <td contenteditable="true" data-field="pgp">₱<?= number_format($item['pgp'], 2) ?></td>
                  <td contenteditable="true" data-field="gpm"><?= number_format($item['gpm'], 2) ?>%</td>
                  <td contenteditable="true" data-field="opex">₱<?= number_format($item['opex'], 2) ?></td>
                  <td contenteditable="true" data-field="ppl">₱<?= number_format($item['ppl'], 2) ?></td>
                  <td contenteditable="true" data-field="npm"><?= number_format($item['npm'], 2) ?>%</td>
                  <td>
                    <button class="btn btn-sm btn-primary save-btn">Save</button>
                  </td>
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
        allProjectsWithStatus: <?= json_encode($allProjectsWithStatus) ?>
    };
</script>

<script src="assets/js/sortable.js"></script>
<script src="assets/js/dashboard_sales_generation.js"></script>

<!-- Project List Modal -->
<div class="modal fade" id="projectListModal" tabindex="-1" aria-labelledby="projectListModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectListModalLabel">Projects</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group" id="projectList">
          <!-- Project items will be injected here by JavaScript -->
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php require "template/footer.php"; ?>

<script>
$(document).ready(function() {
  var table = $('#projectSalesTable').DataTable({
    processing: true,
    scrollX: true,
    scrollY: "60vh",
    scrollCollapse: true,
    paging: true,
    responsive: false,
    fixedColumns: {
      leftColumns: 1
    },
    order: [[1, 'desc']],
    pageLength: 25,
    language: {
      search: "Search projects:",
      lengthMenu: "Show _MENU_ projects per page",
      info: "Showing _START_ to _END_ of _TOTAL_ projects",
      infoEmpty: "No projects available",
      infoFiltered: "(filtered from _MAX_ total projects)",
      emptyTable: "No project sales records found",
      zeroRecords: "No matching projects found"
    },
    
    footerCallback: function(row, data, start, end, display) {
      var api = this.api();
      
      var intVal = function(i) {
        return typeof i === 'string' ?
          parseFloat(i.replace(/[₱,%]/g, '')) || 0 :
          typeof i === 'number' ? i : 0;
      };

      var columnsToTotal = [1, 2, 4, 5, 6, 8, 9];
      
      columnsToTotal.forEach(function(colIdx) {
        var total = api
          .column(colIdx, {page: 'current'})
          .data()
          .reduce(function(a, b) {
            return intVal(a) + intVal(b);
          }, 0);
        
        $(api.column(colIdx).footer()).html('₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      });
      
      var percentColumns = [3, 7, 10];
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

  // Save button click handler
  $('#projectSalesTable').on('click', '.save-btn', function() {
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var salesGenId = $row.data('sales-gen-id');
    
    console.log('Sales Gen ID:', salesGenId);
    
    if (!salesGenId) {
      alert('Error: Missing sales generation ID');
      return;
    }
    
    // Collect all editable fields with data-field attribute
    var updateData = {
      sales_gen_id: salesGenId
    };
    
    $row.find('td[data-field]').each(function() {
      var $td = $(this);
      var field = $td.data('field');
      var value = $td.text().trim().replace(/[₱,%]/g, '');
      updateData[field] = parseFloat(value) || 0;
    });
    
    console.log('Update Data:', updateData);
    
    // Disable button and show loading
    $btn.prop('disabled', true).text('Saving...');
    
    // Send AJAX request
    $.ajax({
      url: 'script/update_sales_generation.php',
      method: 'POST',
      data: updateData,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $btn.removeClass('btn-primary').addClass('btn-success').text('Saved!');
          setTimeout(function() {
            $btn.removeClass('btn-success').addClass('btn-primary').text('Save').prop('disabled', false);
          }, 2000);
          table.draw(false);
        } else {
          alert('Error: ' + response.message);
          $btn.prop('disabled', false).text('Save');
        }
      },
      error: function(xhr, status, error) {
        console.log('AJAX Error:', xhr.responseText);
        alert('Error saving data: ' + error);
        $btn.prop('disabled', false).text('Save');
      }
    });
  });
});
</script>