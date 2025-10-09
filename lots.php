<?php
require "template/header.php";
require "script/role_auth.php";
require "config/db.php"; // <-- your PDO connection
$project_id = $_GET['id'];
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
try {
    // Fetch lots with project name
    $stmt = $pdo->query("
        SELECT 
            l.lot_id, 
            l.lot_name,  
            l.project_id,
            l.contract_no,
            p.project_name,
            p.keystage
        FROM lot l
        LEFT JOIN projects p 
            ON l.project_id = p.project_id
        WHERE l.project_id = $project_id
        ORDER BY l.lot_id ASC
    ");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);


     $stmt = $pdo->query("
            SELECT keystage FROM projects WHERE project_id = $project_id
    ");
    $keystageProj = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <h2 class="mb-3">Lot List</h2>
<div class="d-flex mb-3 justify-content-between">
  <div class="d-flex mb-3">
    <button data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success mb-3">+ Add New Lot</button>
  </div>
</div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Lot Number</th>
                <?php if($keystageProj['keystage']==1){echo "<th>Keystage</th>";}else{echo "<th>Carton</th>";} ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($lots): ?>
            <?php foreach ($lots as $lot): ?>
                <tr>
                  <td id="lotname<?= $lot['lot_id'] ?>s"><?= htmlspecialchars($lot['lot_name']) ?></td>
                  <td id="projectid<?= $lot['lot_id'] ?>s" style="display:none"><?= htmlspecialchars($lot['project_id']) ?></td>
                  <td id="contractno<?= $lot['lot_id'] ?>s" style="display:none"><?= htmlspecialchars($lot['contract_no']) ?></td>

                  <!-- Keystage Column -->
                  <?php if ($keystageProj['keystage'] == 1) {
                      echo "<td>";
                      $stmt = $pdo->query("SELECT * FROM keystage WHERE lot_id = " . (int)$lot['lot_id']);
                      $keystage = $stmt->fetchAll(PDO::FETCH_ASSOC);

                      if (empty($keystage)) {
                          echo "none";
                      } else {
                          foreach ($keystage as $ks) {
                              echo "Keystage " . htmlspecialchars($ks['keystage_num']) . " - " . htmlspecialchars($ks['description']) . "<br>";
                          }
                      }
                      echo "</td><td>";
                      echo "<a href=\"keystage.php?id=$project_id&lot_id={$lot['lot_id']}\" class=\"btn btn-primary d-inline-flex align-items-center\"><i class='bi bi-eye fs-4 me-1'></i>Keystage</a>";
                  } else {
                      $stmt = $pdo->query("SELECT COUNT(package_id) AS carton_count FROM package WHERE lot_id = " . (int)$lot['lot_id']);
                      $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                      echo "<td>";

                      if (empty($packages)) {
                          echo "none";
                      } else {
                          foreach ($packages as $pkg) {
                              echo htmlspecialchars($pkg['carton_count']);
                          }
                      }
                      echo "</td><td>";
                      echo "<a href=\"packages.php?id=$project_id&lot_id={$lot['lot_id']}\" class=\"btn btn-primary btn-sm\">Packages</a>";
                  } ?>

                  <!-- Action buttons -->
                  <button data-bs-toggle="modal" data-bs-target="#editModal" 
                          class="btn btn-warning btn-sm" 
                          onclick="updateEditLot(<?= htmlspecialchars($lot['lot_id']) ?>)"><i class="bi bi-pencil-square fs-4"></i></button>
                  <button  class="btn btn-danger mb-1" data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="document.getElementById('delete_lot').value = <?= htmlspecialchars($lot['lot_id']) ?>;"><i class="bi bi-trash fs-4"></i></button></td>
              </tr>

            <?php endforeach; ?>  
        <?php else: ?>
            <tr><td colspan="5" class="text-center">No lots found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
include "partials/lot_modals.php";
?>

<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>

<script>
function updateEditLot(lotId) {
    const lotName = document.getElementById("lotname" + lotId + "s").innerText;
    const projectId = document.getElementById("projectid" + lotId + "s").innerText;
    const contractNo = document.getElementById("contractno" + lotId + "s").innerText;

    // Populate modal fields
    document.getElementById("editlotid").value = lotId;
    document.getElementById("editlotname").value = lotName;
    document.getElementById("editprojectid").value = projectId;
    document.getElementById("editcontractno").value = contractNo;
}
</script>

