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

<!-- Delete Lot Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Delete School</h5></div>
      <div class="modal-body">
        <!-- add id="deleteForm" -->
        <form id="deleteForm" method="POST" action="script/delete.php">
          <input type="hidden" name="source_page" value="lots.php?id=<?= htmlspecialchars($project_id) ?>">
          <input type="hidden" id="delete_lot" name="id">
          <input type="hidden" name="table" value="lot">
          <input type="hidden" name="condition" value="lot_id">
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

<!-- Edit School Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Edit Lot</h5></div>
      <div class="modal-body">
        <form method="POST" action="script/edit_lot.php" id="editLotForm">
          <!-- hidden primary key -->
          <input type="hidden" id="editlotid" name="lot_id">

          <!-- lot_name -->
          <div class="mb-3">
            <label>Lot Name</label>
            <input type="text" id="editlotname" name="lot_name" class="form-control">
          </div>

          <!-- project_id -->
          <div class="mb-3">
            <label>Project ID</label>
            <input type="text" id="editprojectid" name="project_id" class="form-control">
          </div>

          <!-- contract_no -->
          <div class="mb-3">
            <label>Contract No</label>
            <input type="text" id="editcontractno" name="contract_no" class="form-control">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" form="editLotForm" type="submit">Save</button>
      </div>
    </div>
  </div>
</div>