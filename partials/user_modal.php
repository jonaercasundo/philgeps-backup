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
              <option>Logistics</option>
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

<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="script/editUser.php" id="editDeliveryForm">
        <div class="modal-body">
          <input type="hidden" name="user_id" id="editId">

          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" id="editName" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="editUsername"  required>
          </div>

          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" id="editRole" onchange="toggleWarehouse()" required>
                <option value="Super Admin">Super Admin</option>
                <option value="Office Admin">Office Admin</option>
                <option value="Warehouse Admin">Warehouse Admin</option>
                <option value="Admin">Admin</option>
                <option value="Office Coordinator">Office Coordinator</option>
                <option value="Warehouse Coordinator">Warehouse Coordinator</option>
                <option value="Logistics">Logistics</option>            </select>
          </div>

          <div id="warehouseContainer"class="mb-3 visually-hidden">
            <label>Warehouse</label>
            <select class="form-select" name="warehouse_id" id="editWarehouse">
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
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  const editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    let button = event.relatedTarget; 
    document.getElementById('editId').value = button.getAttribute('data-id');
    document.getElementById('editName').value = button.getAttribute('data-name');
    document.getElementById('editUsername').value = button.getAttribute('data-username');
    document.getElementById('editRole').value = button.getAttribute('data-role');
    document.getElementById('editWarehouse').value = button.getAttribute('data-warehouse');
    toggleWarehouse();
  });

  function toggleWarehouse(){
    if(document.getElementById('editRole').value =="Warehouse Admin" || document.getElementById('editRole').value =="Warehouse Coordinator")
        {document.getElementById('warehouseContainer').classList.remove("visually-hidden")}else{
          document.getElementById('warehouseContainer').classList.add("visually-hidden")
          document.getElementById('editWarehouse').value = "";

        }
  }
</script>


<div class="modal fade" id="passModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="script/newPass.php" id="editPass">
        <div class="modal-body">
          <input type="hidden" name="user_id" id="editIdPass">

          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" id="editNamePassword" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="d-flex">
                <input type="password" class="form-control m-lg-1" name="password" id="editPassword" required>
                <button id="showPass" type="button" class="btn btn-success" onclick="togglePass(this)"><i class="bi bi-eye-slash-fill"></i></button>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  const passwordInput = document.getElementById('editPassword');
  const changePass = document.getElementById('passModal');

  changePass.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('editIdPass').value = button.getAttribute('data-id');
    document.getElementById('editNamePassword').value = button.getAttribute('data-name');
    passwordInput.value = button.getAttribute('data-password');
  });

  function togglePass(button) {
    const isHidden = passwordInput.type === 'password';
    
    passwordInput.type = isHidden ? 'text' : 'password';

    // Update button style and icon
    button.classList.toggle('btn-success', !isHidden);
    button.classList.toggle('btn-danger', isHidden);
    button.innerHTML = isHidden 
      ? "<i class='bi bi-eye-fill'></i>"     // Show icon
      : "<i class='bi bi-eye-slash-fill'></i>"; // Hide icon
  }
</script>