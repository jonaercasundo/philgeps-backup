<div class="modal fade" id="batchDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Batch Delivery</h5></div>
      <div class="modal-body">
        <form method="POST" action="script/save_batch_deliveries.php" id="batchDeliveryAdd">
          <div class="mb-3">
            <label>Project</label>
            <select name="project" class="form-select" id="batchProject" onchange="checkAgency(this, 'Batchdeped')" required>
              <option value="#">Select Project</option>
              <?php 
              foreach($projects as $project){
                $project_id = $project['project_id'];
                $project_name = mb_strimwidth($project['project_name'], 0, 50, '...');
                $extra = ($project['agency'] == 'Deped') ? "data-extra='Deped'" : "";
                echo "<option $extra value='$project_id'>$project_name</option>";
              }
              ?>
            </select>
          </div>

          <div class="mb-3 visually-hidden Batchdeped">
            <label class="form-label">Select Schools</label>
            <div class="table-responsive" style="max-height:300px; overflow-y:auto; border:1px solid #ddd;">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                  <tr>
                    <th><input type="checkbox" id="selectAllSchools"></th>
                    <th>School Name</th>
                    <th>Address</th>
                  </tr>
                </thead>
                <tbody id="schoolsTableBody">
                  <tr><td colspan="3" class="text-center text-muted">Select a project first...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Hidden JSON container -->
          <input type="hidden" id="schools_json" name="schools_json">

          <div class="mb-3 visually-hidden Batchdeped">
            <label>Lot</label>
            <select name="lot" class="form-control" id="Batchlot" onchange="getKeystage(this.value,'batchKeystageSelect')">
              <option value="#">Select Keystage</option>
            </select>
          </div>

          <div class="mb-3 visually-hidden Batchdeped">
            <label>Keystage</label>
            <select name="keystage" class="form-control" id="batchKeystageSelect"></select>
          </div>

          <div class="mb-3 visually-hidden Batchdeped">
            <label>Package Type</label>
            <select name="package_type" class="form-control">
              <option value="c1">C1</option>
              <option value="c2">C2</option>
              <option value="c3">C3</option>
              <option value="c4">C4</option>
              <option value="c5">C5</option>
              <option value="c6">C6</option>
            </select>
          </div>

          <div class="mb-3">
            <label>Date</label>
            <input type="date" name="dateDeliver" class="form-control">
          </div>
        </form>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="saveBatchBtn">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Load schools when project changes
document.addEventListener("DOMContentLoaded", function() {
  const projectSelect = document.getElementById("batchProject");
  const tableBody = document.getElementById("schoolsTableBody");
  const selectAll = document.getElementById("selectAllSchools");

  if (projectSelect) {
    projectSelect.addEventListener("change", function() {
      let projectId = this.value;

      if (!projectId || projectId === "#") {
        tableBody.innerHTML = "<tr><td colspan='3' class='text-center text-muted'>No project selected</td></tr>";
        return;
      }

      fetch("script/batch_school.php?project_id=" + projectId)
        .then(res => res.json())
        .then(data => {
          if (data.length === 0) {
            tableBody.innerHTML = "<tr><td colspan='3' class='text-center text-muted'>No schools found for this project</td></tr>";
            return;
          }

          let rows = "";
          data.forEach(school => {
            rows += `
              <tr>
                <td>
                  <input type="checkbox" class="schoolCheckbox"
                         data-id="${school.school_id}"
                         data-name="${school.school_name}"
                         data-address="${school.address}">
                </td>
                <td>${school.school_name}</td>
                <td>${school.address}</td>
              </tr>`;
          });
          tableBody.innerHTML = rows;

          // re-bind select all
          selectAll.checked = false;
          selectAll.addEventListener("change", function() {
            document.querySelectorAll(".schoolCheckbox").forEach(cb => cb.checked = this.checked);
          });
        })
        .catch(err => {
          console.error("Error loading schools:", err);
        });
    });
  }
});

// Before submit → collect only selected schools into JSON
document.getElementById("saveBatchBtn").addEventListener("click", function() {
  let selected = [];
  document.querySelectorAll(".schoolCheckbox:checked").forEach(cb => {
    selected.push({
      id: cb.dataset.id,
      name: cb.dataset.name,
      address: cb.dataset.address
    });
  });

  document.getElementById("schools_json").value = JSON.stringify(selected);

  document.getElementById("batchDeliveryAdd").submit();
});
</script>
