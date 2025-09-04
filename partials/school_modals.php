<!-- school_modals.php -->

<!-- Add School Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add School</h5></div>
      <div class="modal-body">
        <form method="POST" id="addForm">
          <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">
          <div class="mb-3"><label>School ID</label><input type="text" name="school_id" class="form-control" required></div>
          <div class="mb-3"><label>School Name</label><input type="text" name="school_name" class="form-control" required></div>
          <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control" required></div>
          <div class="mb-3"><label>Contact Person</label><input type="text" name="contact_person" class="form-control" required></div>
          <div class="mb-3"><label>Contact</label><input type="text" name="contact" class="form-control" required></div>
          <div class="mb-3"><label>Municipality</label><input type="text" name="municipality" class="form-control" required></div>
          <div class="mb-3"><label>Division</label><input type="text" name="division" class="form-control" required></div>
          <div class="mb-3"><label>Region</label><input type="text" name="region" class="form-control" required></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="addForm('schools','add_school.php')">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Import School Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Import Schools</h5></div>
      <div class="modal-body">
        <form method="POST" action="import_schools.php" enctype="multipart/form-data">
          <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">
          <div class="mb-3">
            <label>CSV File</label>
            <input type="file" name="file" class="form-control" accept=".csv" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" form="importForm">Import</button>
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
          <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">
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
