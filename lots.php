<?php
require "template/header.php";
require "config/db.php"; // <-- your PDO connection
$project_id = $_GET['id'];
try {
    // Fetch lots with project name
    $stmt = $pdo->query("
            SELECT 
        l.lot_id, 
        l.lot_name,  
        p.project_name,
        p.keystage
    FROM lot l
    LEFT JOIN projects p 
        ON l.project_id = p.project_id
    WHERE l.project_id = $project_id
    ORDER BY l.lot_id ASC;
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
  <div class="d-flex mb-3">
    <input class="form-control me-2" type="search" name="q" placeholder="Search items..." aria-label="Search">
    <button class="btn btn-outline-primary" type="submit">Search</button>
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
                    <td><?= htmlspecialchars($lot['lot_name']) ?></td>

                      <!-- Keystage Column -->
                      <?php if($keystageProj['keystage']==1){
                      echo "<td>";
                              $stmt = $pdo->query("SELECT * FROM keystage WHERE lot_id = " . (int)$lot['lot_id']);
                              $keystage = $stmt->fetchAll(PDO::FETCH_ASSOC);

                              if (empty($keystage)) {
                                  echo "none";
                              } else {
                                  foreach ($keystage as $ks) {
                                      echo "Keystage ". htmlspecialchars($ks['keystage_num']) . " - " .htmlspecialchars($ks['description'])."<br>";
                                  }
                              }
                            echo "</td><td>";
                            echo "<a href=\"keystage.php?id=$project_id&lot_id={$lot['lot_id']}\" class=\"btn btn-primary btn-sm\">Keystage</a>";}
                            //End of Keystage
                            else{
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

                        <a href="edit_lot.php?id=<?= $lot['lot_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete_lot.php?id=<?= $lot['lot_id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this lot?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center">No lots found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Lot Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add Lot</h5></div>
      <div class="modal-body">
        <form method="POST" id="addForm">
          <input type="hidden" value="<?=$_GET['id']?>" name="project_id" class="form-control">
          <div class="mb-3"><label>Lot Number</label><input type="text" name="lot_no" class="form-control"></div>
          <div class="mb-3"><label>Contract Number</label><input type="text" name="contract_no" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="addForm('lots','add_lots.php')">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit School Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Edit School</h5></div>
      <div class="modal-body">
        <form>
          <input type="hidden" name="project_id" value="<?=$_GET['id']?>" class="form-control">
          <div class="mb-3"><label>School ID</label><input require id="editid" name="id" type="text" class="form-control"></div>
          <div class="mb-3"><label>School Name</label><input require id="editname" name="school" type="text" class="form-control"></div>
          <div class="mb-3"><label>Address</label><input require id="editaddress" name="address" type="text" class="form-control"></div>
          <div class="mb-3"><label>Contact Person</label><input require id="editperson" name="person" type="text" class="form-control"></div>
          <div class="mb-3"><label>Municipality</label><input require id="editmunicipality" name="municipality" type="text" class="form-control"></div>
          <div class="mb-3"><label>Division</label><input require id="editdivision" name="division" type="text" class="form-control"></div>
          <div class="mb-3"><label>Region</label><input require id="editregion" name="region" type="text" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>
<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>
