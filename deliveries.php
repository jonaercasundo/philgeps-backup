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
   SELECT 
    d.delivery_id,
    p.project_name,
    s.school_id,
    s.school_name,
    s.address,
    d.package_type,
    d.dr_no,
    d.delivery_date,
    d.status,
    COALESCE(pkg_items.items_contents, '') AS items_contents
    FROM deliveries d
    JOIN projects p ON d.project_id = p.project_id
    JOIN school s   ON d.school_id = s.school_id

    LEFT JOIN (
        SELECT 
            x.delivery_id,
            GROUP_CONCAT(
                CONCAT(
                    'Package ', x.rn, ' out of ', x.total_packages, '<br>',
                    x.items
                )
                SEPARATOR '<br><br>'
            ) AS items_contents
        FROM (
            SELECT 
                d.delivery_id,
                p.package_id,
                ROW_NUMBER() OVER (PARTITION BY d.delivery_id ORDER BY p.package_id) AS rn,
                COUNT(*) OVER (PARTITION BY d.delivery_id) AS total_packages,
                GROUP_CONCAT(CONCAT(i.item_name, ' (', pc.qty, ')') SEPARATOR '<br>') AS items
            FROM deliveries d
            -- 🔹 Join packages depending on whether delivery uses keystage_id or lot_id
            LEFT JOIN package p 
                ON ( (d.keystage_id IS NOT NULL AND d.keystage_id = p.keystage_id)
                    OR (d.lot_id IS NOT NULL AND d.lot_id = p.lot_id) )
            JOIN package_content pc ON pc.package_id = p.package_id
            JOIN item i ON pc.item_id = i.item_id
            GROUP BY d.delivery_id, p.package_id
        ) x
        GROUP BY x.delivery_id
    ) pkg_items ON pkg_items.delivery_id = d.delivery_id

    ORDER BY d.status, d.delivery_date
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
           <th>Project</th>
            <th>School</th>
            <th>Address</th>
            <th>Items</th>
            <th>DR No</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
   <tbody id="resultTable">
        <?php foreach($deliveries as $d): ?>
        <tr>
            <td><?= htmlspecialchars(mb_strimwidth($d['project_name'], 0, 50, '...')) ?></td>
            <td><?= htmlspecialchars($d['school_id']). ' ' . htmlspecialchars($d['school_name']) ?></td>
            <td><?= htmlspecialchars($d['address']) ?></td>
            <td>
                <?php if (!empty($d['items_contents'])): ?>
                    <?= nl2br($d['items_contents']) ?>
                <?php else: ?>
                    <em>No items</em>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($d['dr_no']) ?></td>
            <td><?= htmlspecialchars($d['delivery_date']) ?></td>
            <td><?= htmlspecialchars($d['status']) ?></td>
            <td>
                <button class="btn btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#editDeliveryModal"
                        data-id="<?= $d['delivery_id'] ?>"
                        data-project="<?= htmlspecialchars($d['project_name']) ?>"
                        data-school="<?= htmlspecialchars($d['school_id']). ' '. htmlspecialchars($d['school_name']) ?>"
                        data-address="<?= htmlspecialchars($d['address']) ?>"
                        data-remarks="<?= htmlspecialchars($d['items_contents']) ?>"
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
    <ul class="pagination justify-content-center" id="pagination">
          <!-- Previous button -->
     <!-- Previous -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
        <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&limit=<?= $limit ?>">Previous</a>
        </li>

        <?php
        $window = 9; // number of page links to display
        $start = max(1, $page - floor($window / 2));
        $end   = min($total_pages, $start + $window - 1);

        // adjust start if we don't have enough pages at the end
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
<?php include "partials/batchAdd_delivery_modal.php"; ?>
<script src="assets/js/deliveriesModalSelect.js"></script>
<script src="assets/js/deliveriesFilter.js"></script>
<?php require "template/footer.php"; ?>
