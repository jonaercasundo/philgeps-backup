<?php
require "template/header.php";
require "config/db.php"; // your PDO connection

// Get params
$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : null;
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Pagination setup
$limit = 10; // number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

try {
    if ($package_id) {
        // Count total rows
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM item i
            INNER JOIN package_content pc ON i.item_id = pc.item_id
            WHERE pc.package_id = ?
        ");
        $countStmt->execute([$package_id]);
        $totalRows = $countStmt->fetchColumn();

        // Get data with limit
        $stmt = $pdo->prepare("
            SELECT i.item_id, i.item_name, i.unit
            FROM item i
            INNER JOIN package_content pc ON i.item_id = pc.item_id
            WHERE pc.package_id = ?
            ORDER BY i.item_name ASC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$package_id]);

    } elseif ($project_id) {
        // Count total rows
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM item i
            INNER JOIN package_content pc ON i.item_id = pc.item_id
            INNER JOIN package p ON pc.package_id = p.package_id
            INNER JOIN keystage k ON p.keystage_id = k.keystage_id
            INNER JOIN lot l ON k.lot_id = l.lot_id
            WHERE l.project_id = ?
        ");
        $countStmt->execute([$project_id]);
        $totalRows = $countStmt->fetchColumn();

        // Get data with limit
        $stmt = $pdo->prepare("
            SELECT 
                i.item_id, 
                i.item_name, 
                i.unit,
                p.package_num,
                k.keystage_num,
                l.lot_name
            FROM item i
            INNER JOIN package_content pc ON i.item_id = pc.item_id
            INNER JOIN package p ON pc.package_id = p.package_id
            INNER JOIN keystage k ON p.keystage_id = k.keystage_id
            INNER JOIN lot l ON k.lot_id = l.lot_id
            WHERE l.project_id = ?
            ORDER BY l.lot_name, k.keystage_num, p.package_num, i.item_name
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$project_id]);
    } else {
        die("Missing project_id or package_id");
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPages = ceil($totalRows / $limit);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <h2 class="mb-3">Lot List</h2>

    <div class="d-flex mb-3 justify-content-between">
        <div class="d-flex mb-3">
            <input class="form-control me-2" id="search" type="search" name="q" placeholder="Search items..." aria-label="Search">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <p>No Items Found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Item Name</th>
                    <th>Unit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['unit']) ?></td>
                        <td>
                            <a href="edit_item.php?id=<?= $item['item_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_item.php?id=<?= $item['item_id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?= $project_id ?>&package_id=<?= $package_id ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?id=<?= $project_id ?>&package_id=<?= $package_id ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?= $project_id ?>&package_id=<?= $package_id ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>
