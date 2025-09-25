<?php
require "template/header.php";
require "config/db.php"; // your PDO connection
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

<!-- Add Keystage Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add Keystage</h5></div>
      <div class="modal-body">
        <form method="POST" id="addForm">
          <div class="mb-3"><label>Lot Number</label><select class="form-control" name="lot"><?php
           foreach ($lotsFilter as $lotFilter){
             echo "<option value='" . htmlspecialchars($lotFilter['lot_id'], ENT_QUOTES) . "'>" 
        . htmlspecialchars($lotFilter['lot_name'], ENT_QUOTES) 
        . "</option>";}?></select></div>
          <div class="mb-3"><label>Keystage Number</label><input type="text" class="form-control" name="keystage_no"></div>
          <div class="mb-3"><label>Description</label><input type="text" class="form-control" name="description"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" onclick="addForm('keystage','add_keystage.php')">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Keystage Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add Keystage</h5></div>
      <div class="modal-body">
        <form>
          <input type="hidden" value="<?=$_GET['id']?>" class="form-control">
          <div class="mb-3"><label>Lot Number</label><select class="form-control"><?php
           foreach ($lotsFilter as $lotFilter){
             echo "<option id='opt" . htmlspecialchars($lotFilter['lot_id'], ENT_QUOTES) . "' value='" . htmlspecialchars($lotFilter['lot_id'], ENT_QUOTES) . "'>" 
        . htmlspecialchars($lotFilter['lot_name'], ENT_QUOTES) 
        . "</option>";}?></select></div>
          <div class="mb-3"><label>Keystage Number</label><input id="editid" type="text" class="form-control"></div>
          <div class="mb-3"><label>Description</label><input id="editdesc" type="text" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>
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
                        <a href="packages.php?id=<?=$project_id?>&keystage_id=<?= $ks['keystage_id'] ?>" class="btn btn-primary d-inline-flex align-items-center"><i class='bi bi-eye fs-4 me-1'></i>Packages</a>
                        <button data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(<?= $ks['keystage_id'] ?>,<?= $ks['lot_id'] ?>)" class="btn btn-warning"><i class="bi bi-pencil-square fs-4"></i></button>
                        <button data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="document.getElementById('delete_keystage').value = <?= htmlspecialchars($ks['keystage_id']) ?>;" class="btn btn-danger"><i class="bi bi-trash fs-4"></i></button>
                    </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <script>
        function updateEdit(schoolId, lotId){
            const id = document.getElementById("id"+schoolId+"s").innerHTML;
            const desc = document.getElementById("desc"+schoolId+"s").innerHTML;

            document.getElementById("opt"+lotId).selected = true;
            document.getElementById("editid").value = id;
            document.getElementById("editdesc").value = desc;
}
    </script>
<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>