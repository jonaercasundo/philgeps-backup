<div class="modal fade" id="importDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Import Deliveries (Batch Upload)</h5></div>
      <div class="modal-body">
        <form method="POST" id="importDelivery" action="script/import_deliveries.php" enctype="multipart/form-data">
          <div class="mb-3">
          
            <div class="mb-3">
            <label for="project" class="form-label">Select Project</label><br>
            <select type="text" name="project" id="importproject" class="form-control" accept=".xlsx,.xls,.csv" required>
            <?php 
            echo "<option value='0'>Select Project</option>"
            ?></select>
            </div>

            <div id ="keystageimport" class="mb-3">
            <label for="file" class="form-label">Upload File (Excel/CSV)</label><br>
            <input type="file" name="csv_file" id="file_upload_import" class="form-control" accept=".xlsx,.xls,.csv" required disabled><br>
            </div>
            <a href="assets/uploads/import_deliveries.template.csv" download="delivery_template">Download Delivery Template</a>
          </div>
          <small class="text-muted">
            Format: School ID, Delivery, DR_Number, Delivery_Date
          </small>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" onclick="document.getElementById('importDelivery').submit();">Import</button>
      </div>
    </div>
  </div>
</div>
