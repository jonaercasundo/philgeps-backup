<!-- Delete Keystage Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Delete School</h5></div>
      <div class="modal-body">
        <!-- add id="deleteForm" -->
        <form id="deleteForm" method="POST" action="script/delete.php">
          <input type="hidden" name="source_page" value="keystage.php?id=<?= htmlspecialchars($project_id) ?>">
          <input type="hidden" id="delete_keystage" name="id">
          <input type="hidden" name="table" value="keystage">
          <input type="hidden" name="condition" value="keystage_id">
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

<!-- Edit Keystage Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Edit Keystage</h5></div>
      <div class="modal-body">
        <form method="POST" action="script/edit_keystage.php" id="editKeystageForm">
          <input type="hidden" value="<?=$_GET['id']?>" name="project_id" class="form-control">
          <input type="hidden" id="edit_keystage_id" name="keystage_id" class="form-control">
          <div class="mb-3"><label>Lot Number</label><select name="lotID" class="form-control"><?php
           foreach ($lotsFilter as $lotFilter){
             echo "<option id='opt" . htmlspecialchars($lotFilter['lot_id'], ENT_QUOTES) . "' value='" . htmlspecialchars($lotFilter['lot_id'], ENT_QUOTES) . "'>" 
        . htmlspecialchars($lotFilter['lot_name'], ENT_QUOTES) 
        . "</option>";}?></select></div>
          <div class="mb-3"><label>Keystage Number</label><input id="editid" name="keystageNum" type="number" class="form-control"></div>
          <div class="mb-3"><label>Description</label><input id="editdesc" name="description" type="text" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" form="editKeystageForm" type="submit">Save</button>
      </div>
    </div>
  </div>
</div>

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
          <div class="mb-3"><label>Keystage Number</label><input type="number" class="form-control" name="keystage_no"></div>
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