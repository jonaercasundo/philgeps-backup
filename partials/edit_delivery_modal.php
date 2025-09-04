<div class="modal fade" id="editDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Delivery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="script/update_delivery.php" id="editDeliveryForm">
        <div class="modal-body">
          <input type="hidden" name="delivery_id" id="editDeliveryId">

          <div class="mb-3">
            <label class="form-label">Project</label>
            <input type="text" class="form-control" id="editProject" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">School</label>
            <input type="text" class="form-control" name="school" id="editSchool" disabled required>
          </div>

          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="address" id="editAddress" disabled required>
          </div>

          <div class="mb-3">
            <label class="form-label">Content / Remarks</label>
            <input class="form-control" name="remarks" id="editRemarks" disabled></input>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">DR No.</label>
              <input type="text" class="form-control" name="dr_no" id="editDrNo" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Delivery Date</label>
              <input type="date" class="form-control" name="delivery_date" id="editDate" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="editStatus" required>
              <option value="Pending">Pending</option>
              <option value="Delivered">Delivered</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  const editModal = document.getElementById('editDeliveryModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    let button = event.relatedTarget;

    document.getElementById('editDeliveryId').value = button.getAttribute('data-id');
    document.getElementById('editProject').value = button.getAttribute('data-project');
    document.getElementById('editSchool').value = button.getAttribute('data-school');
    document.getElementById('editAddress').value = button.getAttribute('data-address');
    document.getElementById('editRemarks').value = button.getAttribute('data-remarks');
    document.getElementById('editDrNo').value = button.getAttribute('data-drno');
    document.getElementById('editDate').value = button.getAttribute('data-date');
    document.getElementById('editStatus').value = button.getAttribute('data-status');
  });
</script>
