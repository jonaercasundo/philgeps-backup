<?php 
    $is_logistics_page = true;
    require "template/header.php"; 
    require "script/role_auth.php";
    require "config/db.php";

    // roles allowed to access this page
    $allowed_roles = ['Super Admin', 'Admin', 'Logistics Admin'];

    // redirect
    redirectIfNotAuthorized($allowed_roles, 'index.php');

    // Get logistics location ID from URL
    $logistics_location_id = isset($_GET['logistics_location_id']) ? intval($_GET['logistics_location_id']) : 0;

    // Fetch logistics location details
    $logistics_location = null;
    if ($logistics_location_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT ll.logistics_location_id, l.logistic_name, ll.region, w.warehouse_name, w.warehouse_address 
                                  FROM logistics_location ll
                                  JOIN logistics l ON ll.logistics_id = l.logistic_id
                                  JOIN warehouse w ON ll.warehouse_id = w.warehouse_id
                                  WHERE ll.logistics_location_id = ?");
            $stmt->execute([$logistics_location_id]);
            $logistics_location = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }

    if (!$logistics_location) {
        header("Location: logistics_reports.php");
        exit();
    }
?>
<h4>Accepted Deliveries - <?php echo htmlspecialchars($logistics_location['logistic_name']); ?></h4>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Logistics Provider:</strong> <?php echo htmlspecialchars($logistics_location['logistic_name']); ?>
            </div>
            <div class="col-md-3">
                <strong>Region:</strong> <?php echo htmlspecialchars($logistics_location['region']); ?>
            </div>
            <div class="col-md-3">
                <strong>Warehouse:</strong> <?php echo htmlspecialchars($logistics_location['warehouse_name']); ?>
            </div>
            <div class="col-md-3">
                <strong>Warehouse Address:</strong> <?php echo htmlspecialchars($logistics_location['warehouse_address']); ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <label>Project</label>
        <select class="form-select" id="filterProject">
            <option value="">All Projects</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>School</label>
        <select class="form-select" id="filterSchool">
            <option value="">All Schools</option>
        </select>
    </div>
    <div class="col-md-2">
        <label>Start Date</label>
        <input type="date" class="form-control" id="filterStartDate">
    </div>
    <div class="col-md-2">
        <label>End Date</label>
        <input type="date" class="form-control" id="filterEndDate">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100" onclick="applyFilters()">Apply Filters</button>
    </div>
</div>

<table id="acceptedDeliveriesTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Delivery ID</th>
            <th>Project Name</th>
            <th>School Name</th>
            <th>DR Number</th>
            <th>Delivery Date</th>
            <th>Status</th>
            <th>Package Type</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<div class="mt-3">
    <button class="btn btn-danger" onclick="exportPDF()">Export PDF</button>
    <button class="btn btn-success" onclick="exportExcel()">Export Excel</button>
    <a href="logistics_reports.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Logistics Reports
    </a>
</div>

<?php require "template/footer.php"; ?>

<script>
    let table;
    const logisticsLocationId = <?php echo $logistics_location_id; ?>;

    $(document).ready(function() {
        table = $('#acceptedDeliveriesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "script/get_logistics_accepted_deliveries.php",
                type: "GET",
                data: function(d) {
                    d.logistics_location_id = logisticsLocationId;
                    d.project = $('#filterProject').val();
                    d.school = $('#filterSchool').val();
                    d.startDate = $('#filterStartDate').val();
                    d.endDate = $('#filterEndDate').val();
                }
            },
            columns: [
                { data: "delivery_id", className: "text-center" },
                { data: "project_name", className: "text-center" },
                { data: "school_name", className: "text-center" },
                { data: "dr_no", className: "text-center" },
                { 
                    data: "delivery_date", 
                    className: "text-center",
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString() : '-';
                    }
                },
                { 
                    data: "status", 
                    className: "text-center",
                    render: function(data) {
                        const statusClass = {
                            'pending': 'warning',
                            'delivered': 'success',
                            'accepted': 'primary',
                            'warehouse': 'info',
                            'cancelled': 'danger'
                        };
                        return `<span class="badge bg-${statusClass[data] || 'secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                    }
                },
                { data: "package_type", className: "text-center" }
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
        // Load all projects
        $.getJSON("script/get_project_list.php", function(data) {
            let options = '<option value="">All Projects</option>';
            if (Array.isArray(data)) {
                data.forEach(function(project) {
                    options += `<option value="${project.project_id}">${project.project_name}</option>`;
                });
            }
            $('#filterProject').html(options);
        });

        // Load all schools
        $.getJSON("script/get_school_list.php", function(data) {
            let options = '<option value="">All Schools</option>';
            if (Array.isArray(data)) {
                data.forEach(function(school) {
                    options += `<option value="${school.school_id}">${school.school_name}</option>`;
                });
            }
            $('#filterSchool').html(options);
        });
    }

    function exportPDF() {
        const params = new URLSearchParams({
            logistics_location_id: logisticsLocationId,
            project: $('#filterProject').val(),
            school: $('#filterSchool').val(),
            startDate: $('#filterStartDate').val(),
            endDate: $('#filterEndDate').val()
        });
        window.open('script/export_logistics_deliveries_pdf.php?' + params.toString(), '_blank');
    }

    function exportExcel() {
        const params = new URLSearchParams({
            logistics_location_id: logisticsLocationId,
            project: $('#filterProject').val(),
            school: $('#filterSchool').val(),
            startDate: $('#filterStartDate').val(),
            endDate: $('#filterEndDate').val()
        });
        window.location.href = 'script/export_logistics_deliveries_csv.php?' + params.toString();
    }
</script>