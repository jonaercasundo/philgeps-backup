<?php
require "template/header.php";
require "script/role_auth.php";
require "config/db.php"; // your PDO connection
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
?>
<div class="container">
  <h2 class="mb-4">School List</h2>
 <div class="d-flex mb-3 justify-content-between">
<div class="d-flex mb-3">
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">Import Schools</button><br><br>
</div>
<div class="d-flex mb-3">
    <input class="form-control me-2" id="searchInput" name="q" placeholder="Search items..." aria-label="Search">
    <button class="btn btn-outline-primary" id ="searchButton"type="button">Search</button>
</div>
 </div>
<div class="row mb-3">
  <div class="col-md-3">
    <label>Region</label>
    <select id="filterRegion" class="form-select filter"></select>
  </div>
  <div class="col-md-3">
    <label>Division</label>
    <select id="filterDivision" class="form-select filter" disabled></select>
  </div>
  <div class="col-md-3">
    <label>Municipality</label>
    <select id="filterMunicipality" class="form-select filter" disabled></select>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <button class="btn btn-primary me-2 w-100" id="filterButt" disabled>Filter</button>
    <button id="rmvFilter" class="btn btn-primary w-100" disabled>Remove Filter</button>
  </div>
</div>

  <table class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>School ID</th>
        <th>School Name</th>
        <th>Address</th>
        <th>Contact Person</th>
        <th>Telephone</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="resultTable">
      <?php
      try {
            // Pagination settings
            $id = $_GET['id'];
            $limit = 10; // Records per page
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
            $offset = ($page - 1) * $limit;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM schools_project WHERE project_id = $id");
            $stmt->execute();
            $total_rows = $stmt->fetchColumn();
            $total_pages = ceil($total_rows / $limit);

            // use prepared statements for security
            $stmt = $pdo->prepare("SELECT sp.*, s.* FROM schools_project AS sp JOIN school AS s ON sp.school_id = s.school_id WHERE sp.project_id = $id ORDER BY sp.school_id ASC LIMIT $limit OFFSET $offset");
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("DB Error: " . $e->getMessage());
        }
      ?>
        <?php foreach($projects as $project){ ?>
          <tr>
            <td id="id<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['school_id']) ?></td>
            <td id="name<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['school_name']) ?></td>
            <td>
              <span id="address<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['address']) ?></span>, 
              <span id="municipality<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['municipality']) ?></span>, 
              <span id="division<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['division']) ?></span>, 
              <span id="region<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['region']) ?></span>
            </span></td>
            <td id="person<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['contact_person']) ?></td>
            <td id="contact<?= htmlspecialchars($project['school_id']) ?>s"><?= htmlspecialchars($project['contact']) ?></td>
            <td class="text-center">
              <button class="btn btn-warning mb-1" data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(<?= htmlspecialchars($project['school_id']) ?>)"><i class="bi bi-pencil-square fs-4"></i></button>
              <button  class="btn btn-danger mb-1" data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="document.getElementById('delete_school').value = <?= htmlspecialchars($project['school_id']) ?>;"><i class="bi bi-trash fs-4"></i></button></td>
          </tr>
        <?php }; ?>
    </tbody>
  </table>

 <!-- Pagination controls -->
<nav>
    <ul class="pagination justify-content-center" id="pagination">

        <!-- Previous button -->
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>&id=<?= $_GET['id'] ?>">&lt;</a>
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
                <a class="page-link" href="?page=<?= $i ?>&id=<?= $_GET['id'] ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next button -->
        <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>&id=<?= $_GET['id'] ?>">&gt;</a>
            </li>
        <?php endif; ?>

    </ul>
</nav>

</div>
<?php include "partials/school_modals.php"?>
<script src="assets/js/project_details.js"></script>

<script>
function updateEdit(schoolId){
const id = document.getElementById("id"+schoolId+"s").innerHTML;
const name = document.getElementById("name"+schoolId+"s").innerHTML;
const address = document.getElementById("address"+schoolId+"s").innerHTML;
const person = document.getElementById("person"+schoolId+"s").innerHTML;
const contact = document.getElementById("contact"+schoolId+"s").innerHTML;
const municipalty = document.getElementById("municipality"+schoolId+"s").innerHTML;
const division = document.getElementById("division"+schoolId+"s").innerHTML;
const region = document.getElementById("region"+schoolId+"s").innerHTML;

document.getElementById("editid").value = id;
document.getElementById("editname").value = name;
document.getElementById("editaddress").value = address;
document.getElementById("editcontact").value = contact;
document.getElementById("editperson").value = person;
document.getElementById("editmunicipality").value = municipalty;
document.getElementById("editdivision").value = division;
document.getElementById("editregion").value = region;


}
// Populate region on page load
document.addEventListener("DOMContentLoaded", function () {
    populateFilter("filterRegion", "SELECT DISTINCT region AS options FROM school ORDER BY region ASC");
    hideLoading();
});

// When region changes, populate division
document.getElementById("filterRegion").addEventListener("change", function() {
    const region = this.value;
    const divisionSelect = document.getElementById("filterDivision");
    const municipalitySelect = document.getElementById("filterMunicipality");

    // Reset lower-level filters
    divisionSelect.innerHTML = "<option value=''>Select Division</option>";
    divisionSelect.disabled = !region;
    municipalitySelect.innerHTML = "<option value=''>Select Municipality</option>";
    municipalitySelect.disabled = true;

    // Enable filter buttons
    document.getElementById("filterButt").disabled = !region;
    document.getElementById("rmvFilter").disabled = !region;

    if(region){
        populateFilter("filterDivision", "SELECT DISTINCT division AS options FROM school WHERE region='" + region + "' ORDER BY division ASC");
    }
});

// When division changes, populate municipality
document.getElementById("filterDivision").addEventListener("change", function() {
    const division = this.value;
    const municipalitySelect = document.getElementById("filterMunicipality");

    // Reset municipality
    municipalitySelect.innerHTML = "<option value=''>Select Municipality</option>";
    municipalitySelect.disabled = !division;

    if(division){
        populateFilter("filterMunicipality", "SELECT DISTINCT municipality AS options FROM school WHERE division='" + division + "' ORDER BY municipality ASC");
    }
});

// Remove filters button
document.getElementById("rmvFilter").addEventListener("click", function(){
    document.getElementById("filterRegion").value = "";
    document.getElementById("filterDivision").value = "";
    document.getElementById("filterMunicipality").value = "";
    document.getElementById("filterDivision").disabled = true;
    document.getElementById("filterMunicipality").disabled = true;

    updateTable(1);
});


// Helper: get selected filters
function getFilters() {
    let region = document.getElementById("filterRegion").value;
    let division = document.getElementById("filterDivision").value;
    let municipality = document.getElementById("filterMunicipality").value;
    let search = document.getElementById("searchInput").value.trim();
    let params = new URLSearchParams();
    if(region) params.append("region", region);
    if(division) params.append("division", division);
    if(municipality) params.append("municipality", municipality);
    if(search) params.append("search", search);
    return params.toString();
}

// Update table via AJAX
function updateTable(page = 1) {
    let tbody = document.getElementById("resultTable");
    tbody.innerHTML = "";
    showLoading();
    fetch("script/filterSchool.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: getFilters() + "&page=" + page + "&limit=10"
    })
    .then(res => res.json())
    .then(data => {
        if(data.rows && data.rows.length){
            tbody.innerHTML = data.rows.map(row => `
                <tr>
                    <td id='id${truncateText(row.school_id)}s'>${truncateText(row.school_id)}</td>
                    <td id='name${truncateText(row.school_id)}s'>${row.school_name}</td>
                    <td>
                    <span id="address${truncateText(row.school_id)}s">${row.address}</span>, 
                    <span id="municipality${truncateText(row.school_id)}s">${row.municipality}</span>, 
                    <span id="division${truncateText(row.school_id)}s">${row.division}</span>, 
                    <span id="region${truncateText(row.school_id)}s">${row.region}</span>
                    </td>
                    <td id='person${truncateText(row.school_id)}s'>${row.contact_person}</td>
                    <td id='contact${truncateText(row.school_id)}s'>${row.contact}</td>
                    <td class="text-center">
                        <button class="btn btn-warning mb-1" data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(${row.school_id})"><i class="bi bi-pencil-square fs-4"></i></button>
                        <a href="delete_lot.php?id=${row.school_id}" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this School?')"><i class="bi bi-trash fs-4"></i></a>
                    </td>
                </tr>`).join("");
        }
        renderPagination(data, page);
    })
    .finally(() => hideLoading());
}

// Pagination rendering
function renderPagination(data, currentPage) {
    let pagination = document.getElementById("pagination");
    pagination.innerHTML = "";
    if(!data.total_pages || data.total_pages <= 1) return;

    const createPage = (i, text = i) => `
        <li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="updateTable(${i})">${text}</a>
        </li>`;

    pagination.innerHTML += createPage(currentPage-1, "«");
    let start = Math.max(1, currentPage-4);
    let end = Math.min(data.total_pages, start+7);
    for(let i=start; i<=end; i++) pagination.innerHTML += createPage(i);
    pagination.innerHTML += createPage(currentPage+1, "»");
}

// Event listeners
document.getElementById("filterButt").addEventListener("click", () => updateTable(1));
document.getElementById("searchButton").addEventListener("click", () => updateTable(1));
document.getElementById("searchInput").addEventListener("keypress", e => { if(e.key==="Enter") updateTable(1); });

</script>
<?php require "template/footer.php"; ?>