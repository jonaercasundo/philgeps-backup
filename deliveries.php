<?php 
require "template/header.php"; 
require "config/db.php";

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
        SELECT d.*, p.project_name
        FROM deliveries d
        JOIN projects p ON p.project_id = d.project_id
        ORDER BY d.delivery_id ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch deliveries with project name
    $stmt = $pdo->prepare("
        SELECT *
        FROM projects;
    ");
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
    </div>
</div>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-3"><label>Year</label><select class="form-select filter" id="year"></select></div>
    <div class="col-md-3"><label>Project</label><select class="form-select filter" id="filterProjects" disabled></select></div>
    <div class="col-md-3"><label>Status</label><select class="form-select filter" id="filterStatus" disabled></select></div>
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100 me-2" id="filterButt">Filter</button>
        <button class="btn btn-primary w-100" id="rmvFilter">Remove Filter</button>
    </div>
</div>

<!-- Search -->
<div class="d-flex mb-3">
    <input id="searchInput" class="form-control me-2" placeholder="Search items...">
    <button class="btn btn-outline-primary" id="searchButton">Search</button>
</div>

<!-- Table -->
<table class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Project</th><th>School</th><th>Address</th><th>Content</th><th>DR No</th>
            <th>Date</th><th>Status</th><th>Actions</th>
        </tr>
    </thead>
    <tbody id="resultTable">
        <?php foreach($deliveries as $d): ?>
        <tr>
            <td><?= htmlspecialchars(mb_strimwidth($d['project_name'], 0, 50, '...')) ?></td>
            <td><?= htmlspecialchars($d['school']) ?></td>
            <td><?= htmlspecialchars($d['address']) ?></td>
            <td><?= htmlspecialchars($d['remarks']) ?></td>
            <td><?= htmlspecialchars($d['dr_no']) ?></td>
            <td><?= htmlspecialchars($d['delivery_date']) ?></td>
            <td><?= htmlspecialchars($d['status']) ?></td>
            <td>
                <button class="btn btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#editDeliveryModal"
                        data-id="<?= $d['delivery_id'] ?>"
                        data-project="<?= htmlspecialchars($d['project_name']) ?>"
                        data-school="<?= htmlspecialchars($d['school']) ?>"
                        data-address="<?= htmlspecialchars($d['address']) ?>"
                        data-remarks="<?= htmlspecialchars($d['remarks']) ?>"
                        data-drno="<?= htmlspecialchars($d['dr_no']) ?>"
                        data-date="<?= htmlspecialchars($d['delivery_date']) ?>"
                        data-status="<?= htmlspecialchars($d['status']) ?>"
                >Edit</button>
                <a class="btn btn-sm btn-success" href="generate_qr.php?id=<?= $d['delivery_id'] ?>" target="_blank">QR</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Pagination -->
<nav>
    <ul class="pagination justify-content-center" id="pagination"></ul>
</nav>

<!-- Modals -->
<?php include "partials/add_delivery_modal.php"; ?>
<?php include "partials/edit_delivery_modal.php"; ?>
<?php include "partials/batchAdd_delivery_modal.php"; ?>
<script src="assets/js/deliveriesModalSelect.js"></script>
<script src="assets/js/deliveriesFilter.js"></script>
<?php require "template/footer.php"; ?>
