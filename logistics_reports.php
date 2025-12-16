<?php 
    $is_logistics_page = true;
    require "template/header.php"; 
    require "script/role_auth.php";
    require "config/db.php";

    // roles allowed to access this page
    $allowed_roles = ['Super Admin', 'Admin', 'Logistics Admin', 'Logistics'];

    // redirect
    redirectIfNotAuthorized($allowed_roles, 'index.php');
?>
<h4>Logistics Reports</h4>

<div class="row mb-3 align-items-end">
  <div class="col-md-4">
    <label>Logistics Provider</label>
    <select class="form-select" id="filterLogistics">
      <option value="">All Logistics Providers</option>
      <!-- Dynamically populate -->
    </select>
  </div>
  <div class="col-md-4 ms-auto d-flex justify-content-end">
    <button class="btn btn-primary w-75" onclick="applyFilters()">Apply Filter</button>
  </div>
</div>

<table id="logisticsReportTable" class="table table-bordered shadow-sm">
  <thead class="table-dark">
    <tr>
      <th>Logistics Provider</th>
      <th>Region</th>
      <th>Warehouse</th>
      <th>Accepted Deliveries</th>
      <th>Projects Served</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  </tbody>
</table>

<div class="mt-3">
  <!-- <button class="btn btn-danger" onclick="exportPDF()">Export PDF</button> -->
  <button class="btn btn-success" onclick="exportExcel()">Export CSV</button>
</div>

<?php require "template/footer.php"; ?>

<script>
    let table;
    
    $(document).ready(function() {
        table = $('#logisticsReportTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "script/get_logistics_report.php",
                type: "GET",
                data: function(d) {
                    d.logistics = $('#filterLogistics').val();
                }
            },
            columns: [
                { data: "logistic_name", className: "text-center" },
                { data: "region", className: "text-center" },
                { data: "warehouse_name", className: "text-center" },
                { data: "accepted_deliveries", className: "text-center" },
                { data: "projects_served", className: "text-center" },
                { 
                    data: null,
                    orderable: false,
                    className: "text-center",
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-info" onclick="viewLogisticsDetails(${row.logistics_location_id})">
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
        // Load logistics providers
        $.getJSON("script/get_logistics_list.php", function(data) {
            let options = '<option value="">All Logistics Providers</option>';
            data.forEach(function(logistics) {
                options += `<option value="${logistics.logistic_id}">${logistics.logistic_name}</option>`;
            });
            $('#filterLogistics').html(options);
        });
    }

    function viewLogisticsDetails(logisticsLocationId) {
        window.location.href = 'logistics_accepted_deliveries.php?logistics_location_id=' + logisticsLocationId;
    }

    function exportPDF() {
        const params = new URLSearchParams({
            logistics: $('#filterLogistics').val()
        });
        window.open('script/export_logistics_pdf.php?' + params.toString(), '_blank');
    }

    function exportExcel() {
        const params = new URLSearchParams({
            logistics: $('#filterLogistics').val()
        });
        window.location.href = 'script/export_logistics_csv.php?' + params.toString();
    }
</script>