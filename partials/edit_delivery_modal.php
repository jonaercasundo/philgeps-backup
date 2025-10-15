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
              <input type="text" class="form-control" name="dr_no" id="editDrNo">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Delivery Date</label>
              <input type="date" class="form-control" name="delivery_date" id="editDate">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="editStatus" required>
              <option value="pending">Pending</option>
              <option value="delivered">Delivered</option>
              <option value="accepted">Accepted</option>
              <option value="cancelled">Cancelled</option>
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

<div class="modal fade" id="generateQRModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Generate QR Codes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="generateQRForm">

          <!-- Project -->
          <div class="mb-3">
            <label class="form-label">Project Name</label>
            <select class="form-select" id="projectSelect" name="project_id" required>
              <option value="">Select Project</option>
              <?php
                $stmt = $pdo->query("SELECT project_id, project_name FROM projects ORDER BY project_name");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='{$row['project_id']}'>" . htmlspecialchars($row['project_name']) . "</option>";
                }
              ?>
            </select>
          </div>

          <!-- DR Range -->
          <div class="row mb-3">
            <div class="col">
              <label class="form-label">DR No. (From)</label>
              <input type="number" class="form-control" id="drFrom" required>
            </div>
            <div class="col">
              <label class="form-label">DR No. (To)</label>
              <input type="number" class="form-control" id="drTo" required>
            </div>
          </div>

          <!-- Status -->
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" id="statusSelect" required>
              <option value="">Select Status</option>
              <!-- JS will populate this based on project_id -->
            </select>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="submitQR">Generate</button>
      </div>
    </div>
  </div>
</div>

<!-- Hidden Form for Generation of QR -->
 <form id="qrForm" method="POST" action="generate_qr.php" target="_blank">
  <input type="hidden" name="ids" id="idsInput">
</form>

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

// --- Load statuses dynamically based on selected project ---
document.getElementById('projectSelect').addEventListener('change', function() {
  const projectId = this.value;
  const statusSelect = document.getElementById('statusSelect');
  statusSelect.innerHTML = '<option value="">Loading...</option>';

  if (!projectId) {
    statusSelect.innerHTML = '<option value="">Select Status</option>';
    return;
  }

  fetch('script/get_statuses.php?project_id=' + projectId)
    .then(res => res.json())
    .then(data => {
      statusSelect.innerHTML = '<option value="">Select Status</option>';
      data.forEach(status => {
        statusSelect.innerHTML += `<option value="${status}">${status}</option>`;
      });
    });
});

// --- On submit ---
document.getElementById('submitQR').addEventListener('click', function() {
  const projectId = document.getElementById('projectSelect').value;
  const drFrom = parseInt(document.getElementById('drFrom').value);
  const drTo = parseInt(document.getElementById('drTo').value);
  const status = document.getElementById('statusSelect').value;

  if (!projectId || !drFrom || !drTo || !status) {
    alert('Please fill all fields.');
    return;
  }

  if (drTo < drFrom) {
    alert('The "To" DR number must be greater than or equal to "From".');
    return;
  }

  const range = drTo - drFrom + 1;
  if (range > 101) {
    alert('You can only generate a maximum of 100 DR numbers at a time.');
    return;
  }

  fetch(`script/get_dr_range.php?project_id=${projectId}&status=${status}&from=${drFrom}&to=${drTo}`)
    .then(res => res.json())
    .then(data => {
      if (!data.length) {
        alert('No DR numbers found for this range.');
        return;
      }

      const form = document.getElementById('qrForm');
      document.getElementById('idsInput').value = data.join(',');
      form.submit(); // open in new tab
    })
    .catch(err => {
      console.error(err);
      alert('Error fetching DR range.');
    });
});
</script>
