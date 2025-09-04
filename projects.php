<?php 
require "template/header.php"; 
require "config/db.php";

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
            <th>Amount</th><th>Status</th><th>Actions</th>
        </tr>
    </thead>
    <tbody id="resultTable">
        <?php foreach ($projects as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['ref_no']) ?></td>
            <td><?= htmlspecialchars($p['agency']) ?></td>
            <td><?= htmlspecialchars($p['project_name']) ?></td>
            <td>₱<?= number_format($p['contract_amount'], 2) ?></td>
            <td><?= htmlspecialchars($p['status']) ?></td>
            <td><a href="project_details.php?id=<?= $p['project_id'] ?>" class="btn btn-sm btn-info">View</a></td>
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
                        ['label'=>'PhilGEPS Ref No','name'=>'ref_no','type'=>'text'],
                        ['label'=>'Agency','name'=>'agency','type'=>'text'],
                        ['label'=>'Project Name','name'=>'project_name','type'=>'text'],
                        ['label'=>'Contract Amount','name'=>'contract_amount','type'=>'number']
                    ];
                    foreach($fields as $f):
                    ?>
                    <div class="mb-3">
                        <label><?= $f['label'] ?></label>
                        <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>" class="form-control" required>
                    </div>
                    <?php endforeach; ?>
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
                        <label>Document Type</label>
                        <select name="doc_type" class="form-select" required>
                            <option value="Bidding Document">Bidding Document</option>
                            <option value="Contract">Contract</option>
                            <option value="Purchase Order">Purchase Order</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Upload Documents</label>
                        <input type="file" name="documents[]" multiple class="form-control" required>
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

<script>
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
                        <td>${p.ref_no}</td>
                        <td>${p.agency}</td>
                        <td>${p.project_name}</td>
                        <td>₱${p.contract_amount}</td>
                        <td>${p.status}</td>
                        <td><a href='project_details.php?id=${p.project_id}' class='btn btn-sm btn-info'>View</a></td>
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
