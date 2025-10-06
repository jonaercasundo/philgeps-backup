<?php require "template/header.php"; 
      require "config/db.php";

       try {
          $stmt = $pdo->prepare("SELECT * FROM users");
          $stmt->execute();
          $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

      } catch (PDOException $e) {
          die("DB Error: " . $e->getMessage());
      };

?>

<div class="d-flex justify-content-between mb-3">
  <h4>Users</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
</div>

<table class="table table-bordered shadow-sm">
  <thead class="table-dark">
    <tr>
      <th>Name</th><th>User Name</th><th>Role</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php
 foreach ($users as $user) {
  $user_name = $user['username'];
  $name = $user['name'];
  $user_role = $user['role'];
  echo "
    <tr>
      <td>$name</td><td>$user_name</td><td>$user_role</td>
      <td><button class='btn btn-sm btn-info'>Edit</button></td>
    </tr>
    ";
 }?>
  </tbody>
</table>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add User</h5></div>
      <div class="modal-body">
        <form action="script/add_user.php" method="post" id="addUserForm">
          <div class="mb-3"><label>Name</label><input name="fname" type="text" class="form-control"></div>
          <div class="mb-3"><label>Email</label><input name="uname" type="email" class="form-control"></div>
          <div class="mb-3">
            <label>Role</label>
            <select onchange="checkRole(this.value)" name="role" class="form-select">
              <option>Super Admin</option>
              <option>Admin</option>
              <option>Warehouse Admin</option>
              <option>Warehouse Coordinator</option>
              <option>Office Admin</option>
              <option>Office Coordinator</option>
              <option>Viewer</option>
            </select>
          </div>
          <div id="warehouseDiv" class="mb-3 visually-hidden">
            <label>Warehouse</label>
            <select class="form-select" name="warehouse_id">
              <option value="">Choose Warehouse</option>
               <?php 
                try {
                    $stmt = $pdo->prepare("SELECT * FROM warehouse");
                    $stmt->execute();
                    $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    die("DB Error: " . $e->getMessage());
                }

                foreach ($warehouses as $warehouse) {
                    echo "<option value='{$warehouse['warehouse_id']}'>"
                      . htmlspecialchars($warehouse['warehouse_name']) . " - "
                      . htmlspecialchars($warehouse['warehouse_address']) 
                      . "</option>";
                }
              ?>
            </select>
          </div>
          <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button onclick="document.getElementById('addUserForm').submit();" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>
<script>
function checkRole(role) {
  const warehouseDiv = document.getElementById('warehouseDiv');
  if (role === "Warehouse Admin" || role === "Warehouse Coordinator") {
    warehouseDiv.classList.remove("visually-hidden");
  } else {
    warehouseDiv.classList.add("visually-hidden");
  }
}
</script>
<?php require "template/footer.php"; ?>
