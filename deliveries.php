<?php 
$is_deliveries_page = true;
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Office Admin', 'Office Coordinator', 'Warehouse Coordinator', 'Warehouse Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
try {
    $limit = 10;
    $page = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    // Count total deliveries
    $stmt = $pdo->query("SELECT COUNT(*) FROM deliveries");
    $total_rows = $stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Fetch deliveries with project name
    $stmt = $pdo->prepare("
SELECT
    d.delivery_id,
    d.project_id,
    p.project_name,
    s.school_id,
    s.school_name,
    s.address,
    d.package_type,
    d.dr_no,
    d.delivery_date,
    d.status,
    d.accepted_date,
    d.delivered_date,
    k.keystage_num,
    k.description,
    l.lot_name,
    w.warehouse_id,
    w.warehouse_name,
    COALESCE(pkg_items.items_contents, '') AS items_contents
        FROM deliveries d
        LEFT JOIN keystage k ON k.keystage_id = d.keystage_id
        JOIN lot l ON l.lot_id = d.lot_id
        JOIN projects p ON d.project_id = p.project_id
        JOIN school s   ON d.school_id = s.school_id
        LEFT JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
        LEFT JOIN warehouse w ON ll.warehouse_id = w.warehouse_id

LEFT JOIN (
    SELECT
        x.delivery_id,
        GROUP_CONCAT(
            CONCAT(
                'Package ', x.rn, ' out of ', x.total_packages,
                ' — ', x.colored_pkg_status, '<br>',
                x.items
            )
            SEPARATOR '<br><br>'
        ) AS items_contents
    FROM (
        SELECT
            d.delivery_id,
            p.package_id,

            ROW_NUMBER() OVER (
                PARTITION BY d.delivery_id
                ORDER BY p.package_id
            ) AS rn,

            COUNT(*) OVER (
                PARTITION BY d.delivery_id
            ) AS total_packages,

            GROUP_CONCAT(
                CONCAT(
                    i.item_name,
                    ' (',
                    pc.qty * d.package_qty,
                    ')'
                )
                SEPARATOR '<br>'
            ) AS items,

            CASE
                WHEN COALESCE(MAX(dp.status), 'PENDING') = 'DELIVERED' THEN
                    '<span class=\"text-success font-weight-bold\">DELIVERED</span>'
                WHEN COALESCE(MAX(dp.status), 'PENDING') = 'ACCEPTED' THEN
                    '<span class=\"text-primary font-weight-bold\">ACCEPTED</span>'
                WHEN COALESCE(MAX(dp.status), 'PENDING') = 'WAREHOUSE' THEN
                    '<span class=\"text-info font-weight-bold\">WAREHOUSE</span>'
                ELSE
                    '<span class=\"text-warning font-weight-bold\">PENDING</span>'
            END AS colored_pkg_status

        FROM deliveries d

        LEFT JOIN package p
            ON (
                (d.keystage_id IS NOT NULL AND d.keystage_id = p.keystage_id)
                OR
                (d.keystage_id IS NULL AND d.lot_id = p.lot_id)
            )

        JOIN package_content pc ON pc.package_id = p.package_id
        JOIN item i ON pc.item_id = i.item_id

        LEFT JOIN package_status dp
            ON dp.delivery_id = d.delivery_id
           AND dp.package_id = p.package_id

        GROUP BY
            d.delivery_id,
            p.package_id,
            d.package_qty
    ) x
    GROUP BY x.delivery_id
) pkg_items ON pkg_items.delivery_id = d.delivery_id


ORDER BY d.status, d.delivery_date
LIMIT :limit OFFSET :offset;

    ");
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped_deliveries = [];
    foreach ($deliveries as $row) {
        $dr = $row['dr_no'];
        if (!isset($grouped_deliveries[$dr])) {
            $grouped_deliveries[$dr] = [
                'dr_no'          => $dr,
                'project_id'     => $row['project_id'],
                'keystage_name'  => $row['keystage_num'],
                'description'    => $row['description'],
                'lot_name'       => $row['lot_name'],
                'project_name'   => $row['project_name'],
                'school_id'      => $row['school_id'],
                'school_name'    => $row['school_name'],
                'address'        => $row['address'],
                'delivery_date'  => $row['delivery_date'],
                'status'         => $row['status'],
                'deliveries'     => []
            ];
        }
        $grouped_deliveries[$dr]['deliveries'][] = $row;
    }

    // Fetch projects
    $stmt = $pdo->prepare("SELECT * FROM projects;");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between mb-3">
    <h4>Deliveries</h4>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeliveryModal">Add Delivery</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#batchDeliveryModal">Batch Delivery</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importDeliveryModal">Import From File Delivery</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateQRModal">Batch Generate AR</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateLabelsModal">Batch Generate Label</button>
    </div>
</div>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-4"><label>Year</label><select class="form-select filter" id="year"></select></div>
    <div class="col-md-4"><label>Project</label><select class="form-select filter" id="filterProjects" disabled></select></div>
    <div class="col-md-4"><label>Status</label><select class="form-select filter" id="filterStatus" disabled></select></div>
</div>

<!-- DepEd specific filters -->
<div id="depedDeliveries" class="visually-hidden row mb-3">
    <div class="col-md-6"><label>Lot</label><select class="form-select filter" id="importlot"></select></div>
    <div class="col-md-6"><label>Keystage</label><select class="form-select filter" id="importkeystage" disabled></select></div>
</div>

<!-- Location filters -->
<div id="locationFilters" class="visually-hidden row mb-3">
    <div class="col-md-4"><label>Region</label><select class="form-select filter" id="filterRegion"></select></div>
    <div class="col-md-4"><label>Division</label><select class="form-select filter" id="filterDivision" disabled></select></div>
    <div class="col-md-4"><label>Municipality</label><select class="form-select filter" id="filterMunicipality" disabled></select></div>
</div>

<!-- Date Range Filter (shown only when status is 'Accepted' or 'Delivered') -->
<div id="dateRangeFilter" class="visually-hidden row mb-3">
    <div class="col-md-4">
        <label>Start Date</label>
        <input type="date" class="form-control filter" id="startDate">
    </div>
    <div class="col-md-4">
        <label>End Date</label>
        <input type="date" class="form-control filter" id="endDate">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-outline-secondary w-100" id="applyDateFilter">Apply Filter</button>
    </div>
</div>

<!-- Search -->
<div class="d-flex mb-3">
    <input id="searchInput" class="form-control me-2" placeholder="Search items...">
    <button class="btn btn-outline-primary" id="searchButton">Search</button>
</div>

<!-- Table -->
<table class="table table-bordered shadow-sm" id="resultTable">
    <thead class="table-dark">
        <tr>
            <th></th>
            <th>Delivery Details</th>
            <th>Items</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($grouped_deliveries as $dr_group): ?>
        <tr class="table-secondary fw-bold">
            <td class="text-center align-middle" colspan="1">
                <input type="checkbox"
                    class="form-check-input dr-checkbox"
                    value="<?= htmlspecialchars($dr_group['dr_no']) ?>"
                    data-school-id="<?= htmlspecialchars($dr_group['school_id']) ?>"
                    data-project-id="<?= htmlspecialchars($dr_group['project_id']) ?>">
            </td>
            <td class="align-middle" colspan="2">
                DR No: <?= htmlspecialchars($dr_group['dr_no']) ?> —
                Project: <?= htmlspecialchars($dr_group['project_name']) ?> —
                School: <?= htmlspecialchars($dr_group['school_name']) ?>
            </td>
            <td colspan="1">
                <button class="btn btn-secondary mb-1" onclick="generateARs()"><i class="bi bi-qr-code fs-4"></i></button>
                <button class="btn btn-secondary mb-1" onclick="generateLabels()"><i class="bi bi-tags fs-4"></i></button>
            </td>
        </tr>

        <?php foreach ($dr_group['deliveries'] as $d): ?>
            <?php
                $stmt_check = $pdo->prepare("
                    SELECT COUNT(dp.delivery_photo_id)
                    FROM deliveries d
                    JOIN package_status ps ON d.delivery_id = ps.delivery_id
                    JOIN delivery_photo dp ON ps.package_status_id = dp.package_status_id
                    WHERE d.delivery_id = :delivery_id AND dp.status IN ('accepted', 'delivered')
                ");
                $stmt_check->execute([':delivery_id' => $d['delivery_id']]);
                $has_photos = ($stmt_check->fetchColumn() > 0);
            ?>
            <tr>
                <td></td>
                <td>LOT <?= htmlspecialchars($d['lot_name']) ?> <?= !empty($d['keystage_num']) ? "Keystage " . $d['keystage_num'] . " " . $d['description'] : '' ?></td>
                <td><?= !empty($d['items_contents']) ? $d['items_contents'] : '<em>No items</em>' ?></td>
                <td>
                    <?php if ($_SESSION['role'] == "Super Admin" || $_SESSION['role'] == "Office Admin" || $_SESSION['role'] == "Office Coordinator" || $_SESSION['role'] == "Warehouse Admin"): ?>
                    <button class="btn btn-warning mb-1" data-bs-toggle="modal" data-bs-target="#editDeliveryModal"
                            data-id="<?= $d['delivery_id'] ?>"
                            data-project="<?= htmlspecialchars($d['project_name']) ?>"
                            data-school="<?= htmlspecialchars($d['school_id']) . ' ' . htmlspecialchars($d['school_name']) ?>"
                            data-address="<?= htmlspecialchars($d['address']) ?>"
                            data-remarks="<?= htmlspecialchars($d['items_contents']) ?>"
                            data-drno="<?= htmlspecialchars($d['dr_no']) ?>"
                            data-status="<?= htmlspecialchars($d['status']) ?>"
                            data-warehouse-id="<?= htmlspecialchars($d['warehouse_id'] ?? '') ?>"
                            data-warehouse-name="<?= htmlspecialchars($d['warehouse_name'] ?? '') ?>">
                        <i class="bi bi-pencil-square fs-4"></i>
                    </button>
                    <?php endif; ?>
                    <?php if ($has_photos): ?>
                        <a class="btn btn-info mb-1" href="deliveries_details.php?id=<?= $d['dr_no'] ?>" target="_blank"><i class="bi bi-eye fs-4"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Pagination -->
<nav>
    <ul class="pagination justify-content-center" id="pagination">
        <!-- Previous -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&limit=<?= $limit ?>">Previous</a>
        </li>

        <?php
        $window = 9;
        $start = max(1, $page - floor($window / 2));
        $end   = min($total_pages, $start + $window - 1);

        if ($end - $start + 1 < $window) {
            $start = max(1, $end - $window + 1);
        }

        for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <!-- Next -->
        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?>&limit=<?= $limit ?>">Next</a>
        </li>
    </ul>
</nav>

<!-- Modals -->
<?php include "partials/add_delivery_modal.php"; ?>
<?php include "partials/edit_delivery_modal.php"; ?>
<?php include "partials/import_delivery_modal.php"; ?>
<?php include "partials/batchAdd_delivery_modal.php"; ?>
<script src="assets/js/deliveriesModalSelect.js"></script>
<script src="assets/js/deliveriesFilter.js"></script>
<?php require "template/footer.php"; ?>

<script>
function generateARs() {
    const checkboxes = document.querySelectorAll('.dr-checkbox:checked');

    if (checkboxes.length === 0) {
        alert('Please select at least one DR.');
        return;
    }

    const projectIds = Array.from(checkboxes).map(cb => cb.dataset.projectId);

    if (projectIds.some(id => !id)) {
        alert("Project ID is missing. Please refresh or reselect DR.");
        return;
    }

    const projectId = projectIds[0];
    const selectedDrs = Array.from(checkboxes).map(cb => cb.value);

    const params = new URLSearchParams();
    params.append('ids', selectedDrs.join(','));
    params.append('project_id', projectId);

    window.open('generate_qr.php?' + params.toString(), '_blank');
}

function generateLabels() {
    const checkboxes = document.querySelectorAll('.dr-checkbox:checked');

    if (checkboxes.length === 0) {
        alert('Please select at least one DR.');
        return;
    }

    const projectIds = Array.from(checkboxes).map(cb => cb.dataset.projectId);

    if (projectIds.some(id => !id)) {
        alert("Project ID is missing. Please refresh or reselect DR.");
        return;
    }

    const uniqueProjects = [...new Set(projectIds)];

    if (uniqueProjects.length > 1) {
        alert("Please select DRs from the same project only.");
        return;
    }

    const projectId = uniqueProjects[0];
    const selectedDrs = Array.from(checkboxes).map(cb => cb.value);

    const params = new URLSearchParams();
    params.append('drs', selectedDrs.join(','));
    params.append('project_id', projectId);

    window.open('generate_labels.php?' + params.toString(), '_blank');
}
</script>