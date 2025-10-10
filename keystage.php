<?php
require "template/header.php";
require "script/role_auth.php";
require "config/db.php"; // your PDO connection
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Office Admin', 'Office Coordinator'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
// Get params
$lot_id = isset($_GET['lot_id']) ? (int)$_GET['lot_id'] : null;
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    $stmt = $pdo->prepare("SELECT DISTINCT lot_name, lot_id from lot where project_id = $project_id");
    $stmt->execute();
    $lotsFilter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($lot_id) {
        // Get keystages for a specific lot
        $stmt = $pdo->prepare("SELECT k.keystage_id, 
                                    k.keystage_num, 
                                    l.lot_name, 
                                    k.description,
                                    l.lot_id,
                                    COUNT(p.package_id) AS carton_count
                                FROM keystage k
                                LEFT JOIN package p 
                                    ON k.keystage_id = p.keystage_id
                                LEFT JOIN lot l 
                                    ON k.lot_id = l.lot_id
                                WHERE k.lot_id = ?
                                GROUP BY k.keystage_id, k.keystage_num, k.lot_id, k.description
                                ORDER BY k.keystage_num ASC;
                                ");
        $stmt->execute([$lot_id]);
    } elseif ($project_id) {
        // Get keystages for all lots belonging to a project
        $stmt = $pdo->prepare("SELECT k.keystage_id, 
                                    k.keystage_num, 
                                    l.lot_name, l.lot_id,
                                    k.description, 
                                    COUNT(p.package_id) AS carton_count
                                FROM keystage k
                                INNER JOIN lot l 
                                        ON k.lot_id = l.lot_id
                                LEFT JOIN package p 
                                    ON k.keystage_id = p.keystage_id
                                WHERE l.project_id = ?
                                GROUP BY k.keystage_id, k.keystage_num, k.lot_id, k.description
                                ORDER BY lot_name ASC;");
        $stmt->execute([$project_id]);
    } else {
        die("Missing lot_id or project_id");
    }

    $keystages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

   <div class="container mt-4">
    <h2 class="mb-3">Keystage List</h2>
<div class="d-flex mb-3 justify-content-between">
  <div class="d-flex mb-3">
    <a href="#" data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success mb-3">+ Add New Keystage</a>
  </div>
</div>

<?php include "partials/keystage_modals.php"?>

<?php if (empty($keystages)): ?>
        <p>No keystages found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Keystage</th>
                    <th>Cartons</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keystages as $ks): ?>
                    <tr>
                        <td>
                          Lot
                          <span id="lot<?= $ks['lot_id'] ?>s"><?= htmlspecialchars($ks['lot_name']) ?></span>
                          Keystage
                          <span id="id<?= $ks['keystage_id'] ?>s"><?= htmlspecialchars($ks['keystage_num']) ?></span>
                          <span id="desc<?= $ks['keystage_id'] ?>s"><?= htmlspecialchars($ks['description']) ?></span></td>
                        <td><?= htmlspecialchars($ks['carton_count']) ?></td>
                        <td>
                        <a href="packages.php?id=<?=$project_id?>&lot_id=<?=$ks['lot_id']?>&keystage_id=<?= $ks['keystage_id'] ?>" class="btn btn-primary d-inline-flex align-items-center"><i class='bi bi-eye fs-4 me-1'></i>Packages</a>
                        <button data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(<?= $ks['keystage_id'] ?>,<?= $ks['lot_id'] ?>)" class="btn btn-warning"><i class="bi bi-pencil-square fs-4"></i></button>
                        <button data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="document.getElementById('delete_keystage').value = <?= htmlspecialchars($ks['keystage_id']) ?>;" class="btn btn-danger"><i class="bi bi-trash fs-4"></i></button>
                    </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
        function updateEdit(keystageId, lotId){
        const id = document.getElementById("id"+keystageId+"s").innerHTML;
        const desc = document.getElementById("desc"+keystageId+"s").innerHTML;

        document.getElementById("edit_keystage_id").value = keystageId;
        document.getElementById("opt"+lotId).selected = true;
        document.getElementById("editid").value = id;
        document.getElementById("editdesc").value = desc;
}
    </script>
<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>