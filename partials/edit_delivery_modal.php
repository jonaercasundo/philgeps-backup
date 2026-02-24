<!-- edit_delivery_modal -->
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
          <input type="hidden" name="warehouse_id" id="editWarehouseId">

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
          
          <!-- Show when Accepted is selected -->
          <div id="qtySection" style="display: none;">

            <!-- Add Warehouse Selection -->
            <div class="mb-3">
              <label class="form-label">Warehouse</label>
              <select class="form-select" name="warehouse" id="editWarehouse" required>
                <option value="">Select Warehouse</option>
                <?php
                // Fetch warehouses from database
                $warehouse_stmt = $pdo->query("SELECT warehouse_id, warehouse_name FROM warehouse");
                $warehouses = $warehouse_stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($warehouses as $warehouse) {
                  echo "<option value='{$warehouse['warehouse_id']}'>{$warehouse['warehouse_name']}</option>";
                }
                ?>
              </select>
            </div>
            <hr>
            <h6>Package Quantities to Accept</h6>
            <p class="text-muted small">Enter how many packages to accept. Inventory will be multiplied accordingly.</p>
            <div id="packageList"></div>
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
                  echo "<option value='{$row['project_id']}' title='" . htmlspecialchars($row['project_name']) . "'>" . htmlspecialchars(strlen($row['project_name']) > 20 ? substr($row['project_name'], 0, 100) . '...' : $row['project_name']) . "</option>";
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

<div class="modal fade" id="generateLabelsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Generate Labels</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="generateLabelsForm">

          <!-- Project -->
          <div class="mb-3">
            <label class="form-label">Project Name</label>
            <select class="form-select" id="labelProjectSelect" name="project_id" required>
              <option value="">Select Project</option>
              <?php
                $stmt = $pdo->query("SELECT project_id, project_name FROM projects ORDER BY project_name");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='{$row['project_id']}' title='" . htmlspecialchars($row['project_name']) . "'>" . htmlspecialchars(strlen($row['project_name']) > 20 ? substr($row['project_name'], 0, 100) . '...' : $row['project_name']) . "</option>";
                }
              ?>
            </select>
          </div>

          <!-- Batch Range -->
          <div class="row mb-3">
            <div class="col">
              <label class="form-label">
                Batch From 
                <small class="text-muted">(Starting batch number)</small>
              </label>
              <input type="number" class="form-control" id="pageFrom" min="1" value="1" required>
            </div>
            <div class="col">
              <label class="form-label">
                Batch To 
                <small class="text-muted">(Ending batch number)</small>
              </label>
              <input type="number" class="form-control" id="pageTo" min="1" value="100" required>
            </div>
          </div>

          <div class="alert alert-info small p-2">
            <strong>Tip:</strong> Use batch 1–100 for first 100 batches, 101–200 for next 100 batches, etc.
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="submitLabels">Generate</button>
      </div>
    </div>
  </div>
</div>

<!-- Hidden Form for Generation of QR -->
<form id="qrForm" method="POST" action="generate_qr.php" target="_blank">
  <input type="hidden" name="ids" id="idsInput">
</form>

<form id="labelForm" action="generate_labels.php" method="POST" target="_blank">
  <input type="hidden" id="labelIdsInput" name="ids">
</form>

<script>
  const editModal = document.getElementById('editDeliveryModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    let button = event.relatedTarget;
    const deliveryId = button.getAttribute('data-id');

    document.getElementById('editDeliveryId').value = deliveryId;
    document.getElementById('editProject').value = button.getAttribute('data-project');
    document.getElementById('editSchool').value = button.getAttribute('data-school');
    document.getElementById('editAddress').value = button.getAttribute('data-address');
    document.getElementById('editDrNo').value = button.getAttribute('data-drno');
    document.getElementById('editDate').value = button.getAttribute('data-date');
    document.getElementById('editStatus').value = button.getAttribute('data-status');

    // Set warehouse data if available
    const warehouseId = button.getAttribute('data-warehouse-id');
    const warehouseName = button.getAttribute('data-warehouse-name');
    
    if (warehouseId) {
      document.getElementById('editWarehouseId').value = warehouseId;
      document.getElementById('editWarehouse').value = warehouseId;
    }

    // Load packages for this delivery
fetch(`script/get_delivery_packages.php?delivery_id=${deliveryId}`)
  .then(res => res.json())
  .then(packages => {
    const packageList = document.getElementById('packageList');
    packageList.innerHTML = '';
    packages.forEach(pkg => {
      const isPending = pkg.status === 'pending';
      const isAccepted = pkg.status === 'accepted';
      const isDelivered = pkg.status === 'delivered';

      // Compute multiplier from package_type (strip letters)
      let multiplier = 1;
      if (pkg.package_type) {
        const numeric = pkg.package_type.replace(/[^0-9]/g, '');
        multiplier = numeric ? parseInt(numeric) : 1;
      }

      const itemsList = pkg.items_detail.map(item =>
        `${item.item_name} (${item.qty * multiplier})`
      ).join(', ');

      let statusBadge = '';
      let statusChangeOptions = '';

      if (isAccepted) {
        statusBadge = '<span class="badge bg-success ms-2">Accepted</span>';
        statusChangeOptions = `
          <select class="form-select form-select-sm" name="package_status_change[${pkg.package_status_id}]">
            <option value="">Keep as Accepted</option>
            <option value="delivered">Change to Delivered</option>
            <option value="pending">Revert to Pending (returns inventory)</option>
          </select>
        `;
      } else if (isDelivered) {
        statusBadge = '<span class="badge bg-info ms-2">Delivered</span>';
        statusChangeOptions = `
          <select class="form-select form-select-sm" name="package_status_change[${pkg.package_status_id}]">
            <option value="">Keep as Delivered</option>
            <option value="accepted">Change to Accepted</option>
            <option value="pending">Revert to Pending (returns inventory)</option>
          </select>
        `;
      }

      // If pending, show input with package_type as default value
      const pendingInput = `
        <label class="form-label mb-1">Number of Packages</label>
        <input type="number" class="form-control" 
               name="package_qty[${pkg.package_status_id}]"
               min="0" value="${multiplier}">
        <small class="text-muted">Inventory will be multiplied accordingly</small>
      `;

      packageList.innerHTML += `
        <div class="card mb-3">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-6">
                <strong>Package #${pkg.package_num}</strong>
                ${statusBadge}
                <br>
                <small class="text-muted">${itemsList}</small>
              </div>
              <div class="col-md-6">
                ${isPending ? pendingInput : statusChangeOptions}
              </div>
            </div>
          </div>
        </div>
      `;
    });
  });
  });

  // Show/hide package section when status changes
  document.getElementById('editStatus').addEventListener('change', function() {
    const qtySection = document.getElementById('qtySection');
    const selectedStatus = this.value;
    qtySection.style.display = (selectedStatus === 'accepted' || selectedStatus === 'delivered') ? 'block' : 'none';
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
  //IVAN PANG DR TO HINDI SA LABEL
  const range = drTo - drFrom + 1;
  if (range > 100) {
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

// --- On submit ---
document.getElementById('submitLabels').addEventListener('click', async function() {
  const projectId = document.getElementById('labelProjectSelect').value.trim();
  const pageFrom = parseInt(document.getElementById('pageFrom').value);
  const pageTo = parseInt(document.getElementById('pageTo').value);

  // Validation
  if (!projectId) {
    alert('Please select a Project.');
    return;
  }
  if (isNaN(pageFrom) || isNaN(pageTo) || pageFrom < 1 || pageTo < pageFrom) {
    alert('Please enter a valid page range (From ≤ To, and ≥ 1).');
    return;
  }
  if (pageTo - pageFrom + 1 > 10000) {
    alert('Maximum 10,000 schools allowed per batch.');
    return;
  }

  // Show loading
  const btn = this;
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

  try {
    const res = await fetch(`script/get_school_id_range.php?project_id=${projectId}&from=${pageFrom}&to=${pageTo}`);
    if (!res.ok) throw new Error(`Server error: ${res.status}`);
    
    const schoolIds = await res.json();
    
    if (schoolIds.error) throw new Error(schoolIds.message || schoolIds.error);
    if (!schoolIds || schoolIds.length === 0) {
      alert('No schools found in this page range.');
      return;
    }

    window.open(
      `generate_labels.php?school_ids=${encodeURIComponent(schoolIds.join(','))}&project_id=${projectId}`,
      '_blank'
    );
  } catch (err) {
    console.error(err);
    alert('Error: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});


</script>
