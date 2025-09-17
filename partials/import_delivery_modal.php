<div class="modal fade" id="importDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Import Deliveries (Batch Upload)</h5></div>
      <div class="modal-body">
        <form method="POST" id="importDelivery" action="script/import_deliveries.php" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="file" class="form-label">Upload File (Excel/CSV)</label><br>
            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls,.csv" required><br>
            <a href="assets/uploads/import_deliveries.template.csv" download="delivery_template">Download Delivery Template</a>
          </div>
          <small class="text-muted">
            Format: Project, School, Address, Lot, Keystage, Package_Type, DR_Number, Delivery_Date
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
