<?php
require "template/header.php";
require "config/db.php"; // your PDO connection
require "script/role_auth.php";

// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Office Admin', 'Office Coordinator'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
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
            FROM item 
            WHERE project_id = ?
        ");
        $countStmt->execute([$project_id]);
        $totalRows = $countStmt->fetchColumn();

        // Get data with limit
        $stmt = $pdo->prepare("
            SELECT 
                item_id, 
                item_name, 
                unit
            FROM item 
            WHERE project_id = ?
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
    <h2 class="mb-3">Item List</h2>

<div class="d-flex mb-3 justify-content-between">
    <div class="d-flex mb-3">
        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addModal">Add Item</button><br><br>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">Import Item</button><br><br>
    </div>
    <div class="d-flex mb-3">
        <input class="form-control me-2" id="searchInput" name="q" placeholder="Search items..." aria-label="Search">
        <button class="btn btn-outline-primary" id ="searchButton"type="button">Search</button>
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
                        <td id="name<?= $item['item_id'] ?>"><?= htmlspecialchars($item['item_name']) ?></td>
                        <td id="unit<?= $item['item_id'] ?>"><?= htmlspecialchars($item['unit']) ?></td>
                        <td>
                            <button data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(<?= $item['item_id'] ?>)" class="btn btn-warning"><i class="bi bi-pencil-square fs-4"></i></button>
                        <button data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="document.getElementById('delete_item').value = <?= htmlspecialchars($item['item_id']) ?>;" class="btn btn-danger"><i class="bi bi-trash fs-4"></i></button>
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

<?php include "partials/item_modals.php"?>


<script src="assets/js/project_details.js"></script>
<script>
function updateEdit(itemId){
    const id = itemId;
    const itemName = document.getElementById("name"+itemId).innerHTML;
    const unit = document.getElementById("unit"+itemId).innerHTML;

    document.getElementById("edititem_id").value = id;
    document.getElementById("editname").value = itemName;
    document.getElementById("editunit").value = unit;
}</script>
<?php require "template/footer.php"; ?>
