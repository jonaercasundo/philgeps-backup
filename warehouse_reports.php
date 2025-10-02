<?php 
    $is_warehouse_page = true;
    require "template/header.php"; 
    require "script/role_auth.php";
    require "config/db.php";

    // roles allowed to access this page
    $allowed_roles = ['Super Admin', 'Admin', 'Warehouse Admin'];

    // redirect
    redirectIfNotAuthorized($allowed_roles, 'index.php');
?>
<h4>Warehouse Reports</h4>

<div class="row mb-3 align-items-end">
  <div class="col-md-4">
    <label>Warehouse Name</label>
    <select class="form-select" id="filterWarehouse">
      <option value="">All Warehouses</option>
      <!-- Dynamically populate -->
    </select>
  </div>
  <div class="col-md-4 ms-auto d-flex justify-content-end">
    <button class="btn btn-primary w-75" onclick="applyFilters()">Generate Report</button>
  </div>
</div>

<table id="warehouseReportTable" class="table table-bordered shadow-sm">
  <thead class="table-dark">
    <tr>
      <th>Warehouse Name</th>
      <th>Location/Region</th>
      <th>Contact Info</th>
      <th>Total Items</th>
      <th>Active Deliveries</th>
      <th>Projects Served</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  </tbody>
</table>

<div class="mt-3">
  <button class="btn btn-danger" onclick="exportPDF()">Export PDF</button>
  <button class="btn btn-success" onclick="exportExcel()">Export Excel</button>
</div>

<?php require "template/footer.php"; ?>

<script>
    let table;
    
    $(document).ready(function() {
        table = $('#warehouseReportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "script/get_warehouse_report.php",
                type: "GET",
                data: function(d) {
                    d.warehouse = $('#filterWarehouse').val();
                }
            },
            columns: [
                { data: "warehouse_name", className: "text-center" },
                { data: "location_region", className: "text-center" },
                { data: "contact_info", className: "text-center" },
                { data: "total_items", className: "text-center" },
                { data: "active_deliveries", className: "text-center" },
                { data: "projects_served", className: "text-center" },
                { 
                    data: null,
                    orderable: false,
                    className: "text-center",
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-info" onclick="viewWarehouseDetails(${row.warehouse_id})">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        `;
                    }
                }
            ],
            scrollY: "53vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
        });

        // Load filter options
        loadFilterOptions();
    });

    function applyFilters() {
        table.ajax.reload();
    }

    function loadFilterOptions() {
        // Load warehouses
        $.getJSON("script/get_warehouse_list.php", function(data) {
            let options = '<option value="">All Warehouses</option>';
            data.forEach(function(warehouse) {
                options += `<option value="${warehouse.warehouse_id}">${warehouse.warehouse_name}</option>`;
            });
            $('#filterWarehouse').html(options);
        });
    }

    function viewWarehouseDetails(warehouseId) {
        window.location.href = 'warehouse_active_deliveries.php?warehouse_id=' + warehouseId;
    }

    function exportPDF() {
        const params = new URLSearchParams({
            warehouse: $('#filterWarehouse').val()
        });
        window.open('script/export_warehouse_pdf.php?' + params.toString(), '_blank');
    }

    function exportExcel() {
        const params = new URLSearchParams({
            warehouse: $('#filterWarehouse').val()
        });
        window.location.href = 'script/export_warehouse_excel.php?' + params.toString();
    }
</script>