<!-- Delete Packages Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Delete School</h5></div>
      <div class="modal-body">
        <!-- add id="deleteForm" -->
        <form id="deleteForm" method="post" id="addForm" action="script/delete.php">
          <input type="hidden" name="source_page" value="packages.php?id=<?= htmlspecialchars($project_id) ?>">
          <input type="hidden" id="delete_packages" name="id">
          <input type="hidden" name="table" value="package">
          <input type="hidden" name="condition" value="package_id">
          <div class="mb-3">
            <label>Input password to Continue</label>
          <input type="password" name="deletePassword">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- no need for form="" attribute -->
        <button type="button" class="btn btn-primary" onclick="document.getElementById('deleteForm').submit();">Delete</button>
      </div>
    </div>
  </div>
</div>
<!-- Add Item Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Add Items</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

      <form id="addItemForm" method="POST" action="script/add_items.php">
        <div class="modal-body">
        <?php if (isset($_GET['keystage_id'])): ?>
    <input type="hidden" name="keystage_id" value="<?= htmlspecialchars($_GET['keystage_id']) ?>">
    <input type="hidden" name="lot_id" value="<?= htmlspecialchars($_GET['lot_id']) ?>">
      <?php else: ?>
          <!-- Select Lot + Keystage -->
          <div class="mb-3 d-flex">
              <div class="w-50 me-2">
                  <label for="lot_id" class="form-label">Lot</label>
                  <select class="form-select" id="lot_id" name="lot_id" onchange="populateKeystage()" required>
                      <option value="">-- Select Lot --</option>
                      <?php
                      $stmt = $pdo->prepare("SELECT DISTINCT lot_id, lot_name FROM lot WHERE project_id = :pid");
                      $stmt->execute([':pid' => $project_id]);
                      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $lot) {
                          echo "<option value='" . htmlspecialchars($lot['lot_id']) . "'>"
                              . htmlspecialchars($lot['lot_name'])
                              . "</option>";
                      }
                      ?>
                  </select>
              </div>
              <div class="w-100 me-2">
                  <label for="keystage_id" class="form-label">Keystage</label>
                  <select class="form-select" id="keystage_id" name="keystage_id" required disabled>
                      <option value="">-- Select Keystage --</option>
                  </select>
              </div>
          </div>
      <?php endif; ?>
          </div>
          <table class="table table-bordered table-hover table-striped align-middle" id="myTable">
            <thead class="table-dark">
              <tr>
                <th>Paste the table below</th>
              </tr>
            </thead>
            <tbody>
              <tr id="pasteHere">
                <td contenteditable="true">Here!</td>
              </tr>
            </tbody>
          </table><br><br>
          <!-- Dynamic Items -->
          <div id="itemsContainer">
            <div class="d-flex mb-2">
                <select class="form-select" name="items[]">
                  <option value="">-- Select Item --</option>
                  <?php
                  $stmt = $pdo->query("SELECT item_id, item_name FROM item ORDER BY item_name ASC");
                  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
                      echo "<option value='" . htmlspecialchars($item['item_id']) . "'>"
                          . htmlspecialchars($item['item_name'])
                          . "</option>";
                  }
                  ?>
                </select>
                <input type="number" class="form-control" name="quantities[]" min="1" required>
                <button type="button" class="btn btn-danger btn-sm removeItemBtn">x</button>
            </div>
          </div>

          <!-- Add More Items Button -->
          <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addMoreItem">
            + Add Another Item
          </button>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Items</button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- Edit Package Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5>Edit Package</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" name="package_id" id="edit_package_id">

          <div class="mb-3 d-flex">
            <div class="w-50 me-2">
                <label>Package Num</label>
            <input type="text" class="form-control" name="package_num"readonly id="edit_package_num">
            </div>
            <div class="w-50 me-2">
                <label>Lot Number</label>
            <input type="text" class="form-control" readonly id="edit_lot_num">
            </div>
            <div class="w-100 me-2">
                <label>Keystage</label>
            <input type="text" class="form-control" readonly id="edit_key_num">
            </div>
          </div>
                  
          <div class="mb-3 d-flex">
            <div class="w-100 me-2">
              <label>Width</label>
              <input type="decimal" class="form-control" name="width" id="edit_width">
            </div>
            <div class="w-100 me-2">
              <label>Height</label>
              <input type="decimal" class="form-control" name="height" id="edit_height">
            </div>
            <div class="w-100 me-2">
              <label>Length</label>
              <input type="decimal" class="form-control" name="length" id="edit_length">
            </div>
          </div>
          <div class="mb-3">
            <label>Items in Package</label>
            <div id="edit_items"></div>
            <button type="button" class="btn btn-sm btn-success mt-2" id="addItemBtn">+ Add Item</button>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="saveEditBtn">Save changes</button>
      </div>
    </div>
  </div>
</div>
<script>
  function submitAddForm(){
  fetch('add_items.php', {
  method: 'POST',
  body: new FormData(document.getElementById('addForm'))
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    alert(data.message);
  } else {
    alert('Error: ' + data.message);
  }
});
  }
</script>