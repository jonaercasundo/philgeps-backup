<?php
require "template/header.php";
require "config/db.php";
require "script/role_auth.php";
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
// Pagination settings
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Total rows
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $total_rows = $stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Projects for current page
    $stmt = $pdo->prepare("SELECT * FROM projects ORDER BY project_id ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between mb-3">
    <h4>Projects</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">Add Project</button>
</div>

<!-- Filters -->
<div class="row mb-3">
    <?php $filters = ['year' => 'Year', 'agency' => 'Agency', 'status' => 'Status']; ?>
    <?php foreach ($filters as $id => $label): ?>
        <div class="col-md-3">
            <label><?= $label ?></label>
            <select id="<?= $id ?>" class="form-select filters"></select>
        </div>
    <?php endforeach; ?>
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100 me-1" id="filterButt">Filter</button>
        <button class="btn btn-secondary w-100" id="rmvFilter">Remove Filter</button>
    </div>
</div>

<!-- Projects Table -->
<table class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Ref No</th><th>Agency</th><th>Project Name</th>
            <th>Amount</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th>
        </tr>
    </thead>
    <tbody id="resultTable">
        <?php foreach ($projects as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['ref_no']) ?></td>
            <td><?= htmlspecialchars($p['agency']) ?></td>
            <td><?= htmlspecialchars($p['project_name']) ?></td>
            <td>₱<?= number_format($p['contract_amount'], 2) ?></td>
            <td><?= htmlspecialchars(date('M d, Y', strtotime($p['start_date']))) ?></td>
            <td><?= htmlspecialchars(date('M d, Y', strtotime($p['end_date']))) ?></td>
            <td><?= htmlspecialchars($p['status']) ?></td>
            <td class="text-center">
                <button class="btn btn-warning btn-sm me-1 mb-2"
                        onclick="updateEdit(
                            <?= $p['project_id'] ?>,
                            '<?= addslashes(htmlspecialchars($p['ref_no'])) ?>',
                            '<?= addslashes(htmlspecialchars($p['project_name'])) ?>',
                            <?= floatval($p['contract_amount']) ?>,
                            <?= floatval($p['ABC']) ?>,
                            '<?= $p['start_date'] ?>',
                            '<?= $p['end_date'] ?>',
                            '<?= addslashes(htmlspecialchars($p['status'])) ?>'
                        )"
                        data-bs-toggle="modal"
                        data-bs-target="#editProjectModal"
                        title="Edit Project">
                    <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <a href="project_details.php?id=<?= $p['project_id'] ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-eye fs-4"></i>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Pagination -->
<nav>
    <ul class="pagination justify-content-center" id="pagination">
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>">&lt;</a></li>
        <?php endif; ?>
        <?php
        $max_links = 8;
        $start = max(1, $page - floor($max_links / 2));
        $end = min($total_pages, $start + $max_links - 1);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>">&gt;</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5>Add Project</h5></div>
            <div class="modal-body">
                <form id="addForm" enctype="multipart/form-data">
                    <?php
                    $fields = [
                        ['label'=>'PhilGEPS Ref No','name'=>'ref_no','type'=>'text', 'id'=>'ref_no'],
                        ['label'=>'Project Name','name'=>'project_name','type'=>'text', 'id' =>'project_name'],
                        ['label'=>'Contract Amount','name'=>'contract_amount','type'=>'text', 'id'=>'contract_formatter'],
                        ['label'=>'ABC','name'=>'ABC','type'=>'text', 'id'=>'ABC_formatter']
                    ];
                    foreach($fields as $f):
                    ?>
                    <div class="mb-3">
                        <label><?= $f['label'] ?></label>
                        <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>" id="<?= $f['id'] ?>"class="form-control" required>
                    </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="rawNumber" id="rawNumber">
                    <input type="hidden" name="rawNumber2" id="rawNumber2">
                    <div class="mb-3">
                        <label for="agency">Agency</label>
                        <select name="agency" class="form-control" onchange="changeAgency(this.value)" required>
                            <option>Select Agency</option>
                            <option value="Deped">Deped</option>
                            <option value="Dpwh">Dpwh</option>
                        </select>
                    </div>
                    <div class="mb-3 visually-hidden" id="includeKeystage">
                        <label>Include Keystage</label>
                        <input type="checkbox" name="keystage" class="checkbox" value="1"><br><br>
                    </div>
                    <div class="mb-3 row">
                        <div class="col">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="">Select Status</option>
                            <option value="Pending" selected>Pending</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="addForm('projects','add_project.php')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" id="edit_project_id">

                    <?php
                    $edit_fields = [
                        ['label'=>'PhilGEPS Ref No','name'=>'ref_no','type'=>'text', 'id'=>'edit_ref_no'],
                        ['label'=>'Project Name','name'=>'project_name','type'=>'text', 'id' =>'edit_project_name'],
                        ['label'=>'Contract Amount','name'=>'contract_amount','type'=>'text', 'id'=>'edit_contract_formatter'],
                        ['label'=>'ABC','name'=>'ABC','type'=>'text', 'id'=>'edit_ABC_formatter']
                    ];
                    foreach($edit_fields as $f):
                    ?>
                    <div class="mb-3">
                        <label><?= $f['label'] ?></label>
                        <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>" id="<?= $f['id'] ?>" class="form-control" required>
                    </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="edit_rawNumber" id="edit_rawNumber">
                    <input type="hidden" name="edit_rawNumber2" id="edit_rawNumber2">
                    <div class="mb-3 row">
                        <div class="col">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="">Select Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-success" onclick="updateProject()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
const rawNumber   = document.getElementById("rawNumber");
const rawNumber2   = document.getElementById("rawNumber2");
document.getElementById("contract_formatter").addEventListener("input", function (e) {
 let value = e.target.value.replace(/,/g, ""); // strip commas

    // only allow digits + decimal point
    if (!/^\d*\.?\d*$/.test(value)) {
        e.target.value = e.target.value.slice(0, -1);
        return;
    }

    if (value) {
        // split int & decimal
        let parts = value.split(".");
        parts[0] = Number(parts[0]).toLocaleString();
        e.target.value = parts.join(".");
    }

    rawNumber.value = value; // store raw value in hidden input
});

document.getElementById("ABC_formatter").addEventListener("input", function (e) {
 let value = e.target.value.replace(/,/g, ""); // strip commas

    // only allow digits + decimal point
    if (!/^\d*\.?\d*$/.test(value)) {
        e.target.value = e.target.value.slice(0, -1);
        return;
    }

    if (value) {
        // split int & decimal
        let parts = value.split(".");
        parts[0] = Number(parts[0]).toLocaleString();
        e.target.value = parts.join(".");
    }

    rawNumber2.value = value; // store raw value in hidden input
});

function changeAgency(agency) {
    if(agency === "Deped") {
        document.getElementById("includeKeystage").classList.remove("visually-hidden");
    } else{
        document.getElementById("includeKeystage").classList.add("visually-hidden");
    }
}

function changeEditAgency(agency) {
    if(agency === "Deped") {
        document.getElementById("edit_includeKeystage").classList.remove("visually-hidden");
    } else{
        document.getElementById("edit_includeKeystage").classList.add("visually-hidden");
    }
}

// Function to update the edit modal with project data
function updateEdit(projectId, refNo, projectName, contractAmount, abc, startDate, endDate, status) {
    document.getElementById("edit_project_id").value = projectId;
    document.getElementById("edit_ref_no").value = refNo;
    document.getElementById("edit_project_name").value = projectName;

    // Format contract amount with commas
    const formattedAmount = parseFloat(contractAmount).toLocaleString('en-PH', {minimumFractionDigits: 2});
    document.getElementById("edit_contract_formatter").value = formattedAmount;
    document.getElementById("edit_rawNumber").value = contractAmount;

    // Format ABC with commas
    const formattedABC = parseFloat(abc).toLocaleString('en-PH', {minimumFractionDigits: 2});
    document.getElementById("edit_ABC_formatter").value = formattedABC;
    document.getElementById("edit_rawNumber2").value = abc;

    document.getElementById("edit_start_date").value = startDate;
    document.getElementById("edit_end_date").value = endDate;
    document.getElementById("edit_status").value = status;
}

// Update project function
function updateProject() {
    const formData = new FormData(document.getElementById('editForm'));

    // Get the raw numbers for contract amount and ABC
    const contractAmount = document.getElementById("edit_contract_formatter").value.replace(/,/g, "");
    const abc = document.getElementById("edit_ABC_formatter").value.replace(/,/g, "");

    // Update the hidden inputs with raw values
    document.getElementById("edit_rawNumber").value = contractAmount;
    document.getElementById("edit_rawNumber2").value = abc;

    // Add the raw values to the form data
    formData.set('rawNumber', contractAmount);
    formData.set('rawNumber2', abc);

    fetch('script/update_project.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                window.location.href = 'projects.php?toast=Project Updated&type=success';
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        } catch (e) {
            console.error('Response:', text);
            alert('Error: Invalid response from server');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    // Populate filter selects
    const filters = {
        year: "SELECT DISTINCT YEAR(`created_at`) AS options FROM projects ORDER BY created_at",
        agency: "SELECT DISTINCT agency AS options FROM projects ORDER BY agency ASC",
        status: "SELECT DISTINCT status AS options FROM projects ORDER BY status ASC"
    };

    Object.entries(filters).forEach(([id, sql]) => populateFilter(id, sql));

    hideLoading();

    // Filter click
    document.getElementById("filterButt").addEventListener("click", () => {
        const params = new URLSearchParams();
        ["year","agency","status"].forEach(f => {
            const v = document.getElementById(f).value;
            if(v) params.append(f,v);
        });

        const tbody = document.getElementById("resultTable");
        const pagination = document.getElementById("pagination");
        tbody.innerHTML = "";
        pagination.innerHTML = "";
        showLoading();

        fetch("script/filterProjects.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if(data.length){
                tbody.innerHTML = data.map(p => `
                    <tr>
                        <td>\${p.ref_no}</td>
                        <td>\${p.agency}</td>
                        <td>\${p.project_name}</td>
                        <td>₱\${parseFloat(p.contract_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                        <td>\${new Date(p.start_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
                        <td>\${new Date(p.end_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
                        <td>\${p.status}</td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm me-1"
                                    onclick="updateEdit(
                                        \${p.project_id},
                                        '\${p.ref_no.replace(/'/g, "\\'")}',
                                        '\${p.project_name.replace(/'/g, "\\'")}',
                                        \${parseFloat(p.contract_amount)},
                                        \${parseFloat(p.ABC)},
                                        '\${p.start_date}',
                                        '\${p.end_date}',
                                        '\${p.status.replace(/'/g, "\\'")}'
                                    )"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editProjectModal"
                                    title="Edit Project">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href='project_details.php?id=\${p.project_id}' class='btn btn-sm btn-info'>View</a>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(console.error)
        .finally(() => hideLoading());
    });

    // Remove Filter
    document.getElementById("rmvFilter").addEventListener("click", () => location.reload());
});
</script>

<?php require "template/footer.php"; ?>
