<?php require "template/header.php"; 
      require "config/db.php"
?>

<div class="d-flex justify-content-between mb-3">
  <h4>Projects</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">Add Project</button>
</div>
<div class="row mb-3">
  <div class="col-md-3">
    <label>Year</label>
    <select id="year" class="form-select filters"></select>
  </div>
  <div class="col-md-3">
    <label>Agency</label>
    <select id="agency" class="form-select filters"></select>
  </div>
  <div class="col-md-3">
    <label>Status</label>
    <select id="status" class="form-select filters"></select>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <button class="btn btn-primary w-100 me-1" id="filterButt">Filter</button>
    <button class="btn btn-primary w-100" id="rmvFilter">Remove Filter</button>
  </div>
</div>
<table class="table table-bordered shadow-sm">
  <thead class="table-dark">
    <tr>
      <th>Ref No</th><th>Agency</th><th>Project Name</th>
      <th>Amount</th><th>Status</th><th>Actions</th>
    </tr>
  </thead>
  <tbody id="resultTable">
    <?php
    try {
    // Pagination settings
    $limit = 10; // Records per page
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects");
    $stmt->execute();
    $total_rows = $stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // use prepared statements for security
    $stmt = $pdo->prepare("SELECT * FROM projects ORDER BY project_id ASC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
    foreach ($projects as $project) {
       $ref_no = $project['ref_no'];
       $project_name = $project['project_name'];
       $agency = $project['agency'];
       $contract_amount = $project['contract_amount'];
       $status = $project['status'];
       $project_id = $project['project_id'];

    echo "
    <tr>
    <td>$ref_no</td><td>$agency</td><td>$project_name</td>
      <td>₱$contract_amount</td><td>$status</td>
      <td><a href='project_details.php?id=$project_id' class='btn btn-sm btn-info'>View</a></td>
    </tr>
    ";
    }
    ?>
  </tbody>
</table>


 <!-- Pagination controls -->
<nav>
    <ul class="pagination justify-content-center" id="pagination">

        <!-- Previous button -->
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>">&lt;</a>
            </li>
        <?php endif; ?>

        <?php
        $max_links = 8;
        $start = max(1, $page - floor($max_links / 2));
        $end = $start + $max_links - 1;

        // Adjust if near the end
        if ($end > $total_pages) {
            $end = $total_pages;
            $start = max(1, $end - $max_links + 1);
        }

        for ($i = $start; $i <= $end; $i++):
        ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next button -->
        <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>">&gt;</a>
            </li>
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
          <div class="mb-3">
            <label>PhilGEPS Ref No</label>
            <input type="text" name="ref_no" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Agency</label>
            <input type="text" name="agency" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Project Name</label>
            <input type="text" name="project_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Contract Amount</label>
            <input type="number" name="contract_amount" class="form-control" required>
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
            <label for="doc_type">Document Type</label>
                <select name="doc_type" class="form-control" required>
                    <option value="Bidding Document">Bidding Document</option>
                    <option value="Contract">Contract</option>
                    <option value="Purchase Order">Purchase Order</option>
                    <option value="Other">Other</option>
                </select>
          </div>
          <div id="mb-3">
                <label for="documents">Upload Documents</label>
                <input type="file" name="documents[]" multiple class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="addForm('projects','add_project.php')">Save</button>
      </div>
    </div>
  </div>
</div>
<script>
  //populate region filter
document.addEventListener("DOMContentLoaded", function () {
    populateFilter("year", "SELECT DISTINCT YEAR(`created_at`) AS options FROM projects ORDER BY created_at");
    populateFilter("agency", "SELECT DISTINCT agency AS options FROM projects ORDER BY agency ASC");
    populateFilter("status", "SELECT DISTINCT status AS options FROM projects ORDER BY status ASC");
    hideLoading()
});

//filter
document.getElementById("filterButt").addEventListener("click", function() {
    let year = document.getElementById("year").value;
    let agency = document.getElementById("agency").value;
    let status = document.getElementById("status").value;
    let tbody = document.getElementById("resultTable");
    tbody.innerHTML = "";
    let pagination = document.getElementById("pagination");
    pagination.innerHTML = "";

    if(year != ""){
      year = "&year=" + year
    }
    if(agency != ""){
      agency = "&agency=" + agency
    }
    if(status != ""){
      status = "&status=" + status
    }
    showLoading();

     fetch("script/filterProjects.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:  "?placeholder="+year + agency + status
    })
    .then(res => res.json())
    .then(data => {
        if (data.length > 0) {
            data.forEach(row => {
                tbody.innerHTML += `
                                 <tr>
                                  <td>${row.ref_no}</td><td>${row.agency}</td><td>${row.project_name}</td>
                                    <td>₱${row.contract_amount}</td><td>${row.status}</td>
                                    <td><a href='project_details.php?id=${row.project_id}' class='btn btn-sm btn-info'>View</a></td>
                                  </tr>
                            `;
            });
        }
    })
    .catch(err => console.error("Error:", err))
    .finally(() => {
        hideLoading();
    });
    });
</script>
<?php require "template/footer.php"; ?>
