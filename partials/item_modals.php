<!-- Edit Item Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Edit Item</h5></div>
      <div class="modal-body">
        <form method="POST" action="script/edit_item.php" id="editItemForm">
          <input type="hidden" value="<?$item['item_id']?>" name="item_id" id="edititem_id" class="form-control">
          <div class="mb-3"><label>Item name</label><input id="editname" name="itemName" type="text" class="form-control"></div>
          <div class="mb-3"><label>Unit</label><input id="editunit" name="unit" type="text" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" form="editItemForm" type="submit">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Import Items Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Import Items</h5></div>
      <div class="modal-body">
        <form id="importForm" method="POST" action="script/import_items.php" enctype="multipart/form-data">
          <input type="hidden" name="project_id" value="<?= htmlspecialchars($id) ?>">
          <div class="mb-3">
            <label>Upload Items</label>
            <input type="file" name="file" id="file" class="form-control" accept=".csv,.xlsx,.xls" required><br>
            <a href="assets/uploads/itemtemplate.csv" download="itemtemplate.csv">Download Items Template</a>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="importBtn" onclick="document.getElementById('importForm').submit();">Import</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Keystage Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Delete Item</h5></div>
      <div class="modal-body">
        <!-- add id="deleteForm" -->
        <form id="deleteForm" method="POST" action="script/delete.php">
          <input type="hidden" name="source_page" value="items.php?id=<?= htmlspecialchars($project_id) ?>">
          <input type="hidden" id="delete_item" name="id">
          <input type="hidden" name="table" value="item">
          <input type="hidden" name="condition" value="item_id">
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