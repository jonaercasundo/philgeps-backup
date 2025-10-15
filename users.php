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
      <td class='text-center'>
                <button class='btn btn-warning mb-1' data-bs-toggle='modal' data-bs-target='#editModal'
                        data-id=".$user['user_id']."
                        data-name='$name'
                        data-username='$user_name'
                        data-role='$user_role'
                        data-warehouse=".htmlspecialchars($user['warehouse_id'])."
                ><i class='bi bi-pencil-square fs-4'></i></button>
                <button class='btn btn-danger mb-1'data-bs-toggle='modal' data-bs-target='#passModal'
                        data-id=".$user['user_id']."
                        data-name='$name'
                ><i class='bi bi-file-lock-fill fs-4'></i></button>
      </td>
    </tr>
    ";
 }?>
  </tbody>
</table>

<?php require "partials/user_modal.php";?>
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
