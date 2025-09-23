<?php
$project_id = $_GET['id'];
?>
<!-- school_modals.php -->

<!-- Import School Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Import Schools</h5></div>
      <div class="modal-body">
        <!-- add id="importForm" -->
        <form id="importForm" method="POST" action="script/import_schools.php">
          <input type="hidden" name="project_id" value="<?= htmlspecialchars($id) ?>">
          <div class="mb-3">
            <label>Paste School IDs</label>
            <textarea name="school_ids" class="form-control" rows="6" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- no need for form="" attribute -->
        <button type="button" class="btn btn-primary" id="importBtn">Import</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete School Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Delete School</h5></div>
      <div class="modal-body">
        <!-- add id="deleteForm" -->
        <form id="deleteForm" method="POST" action="script/delete.php">
          <input type="hidden" name="source_page" value="schools.php?id=<?= htmlspecialchars($project_id) ?>">
          <input type="hidden" id="delete_school" name="id">
          <input type="hidden" name="table" value="schools_project">
          <input type="hidden" name="condition" value="school_id">
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
      <div class="modal-header"><h5 class="modal-title">Edit School</h5></div>
      <div class="modal-body">
        <form method="POST" action="script/edit_school.php" id="editForm">
          <input type="hidden" name="project_id" value="<?= $id ?>">
          <div class="mb-3"><label>School ID</label><input required id="editid" name="id" type="text" class="form-control"></div>
          <div class="mb-3"><label>School Name</label><input required id="editname" name="school" type="text" class="form-control"></div>
          <div class="mb-3"><label>Address</label><input required id="editaddress" name="address" type="text" class="form-control"></div>
          <div class="mb-3"><label>Contact Person</label><input required id="editperson" name="person" type="text" class="form-control"></div>
          <div class="mb-3"><label>Contact</label><input required id="editcontact" name="contact" type="text" class="form-control"></div>
          <div class="mb-3"><label>Municipality</label><input required id="editmunicipality" name="municipality" type="text" class="form-control"></div>
          <div class="mb-3"><label>Division</label><input required id="editdivision" name="division" type="text" class="form-control"></div>
          <div class="mb-3"><label>Region</label><input required id="editregion" name="region" type="text" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" form="editForm">Save</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const importBtn = document.getElementById("importBtn");
  const importForm = document.getElementById("importForm");

  importBtn.addEventListener("click", () => {
    // Optional: confirm before submitting
    if (confirm("Are you sure you want to import these schools?")) {
      importForm.submit();
    }
  });
});
</script>
