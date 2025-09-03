<?php
require "template/header.php";
require "config/db.php"; // your PDO connection
?>
<div class="container">
  <h2 class="mb-4">School List</h2>
 <div class="d-flex mb-3 justify-content-between">
<div class="d-flex mb-3">
<button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addModal">Add School</button><br><br>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">Import From File</button><br><br>
</div>
<div class="d-flex mb-3">
    <input class="form-control me-2" id="search" name="q" placeholder="Search items..." aria-label="Search">
    <button class="btn btn-outline-primary" onclick="fetchData('school', ['school_id', 'school_name', 'address', 'contact_person', 'contact', 'municipality', 'division', 'region'])" type="button">Search</button>
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

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM school WHERE project_id = $id");
            $stmt->execute();
            $total_rows = $stmt->fetchColumn();
            $total_pages = ceil($total_rows / $limit);

            // use prepared statements for security
            $stmt = $pdo->prepare("SELECT * FROM school WHERE project_id = $id ORDER BY school_id ASC LIMIT $limit OFFSET $offset");
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
            <td>
              <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(<?= htmlspecialchars($project['school_id']) ?>)">Edit School</button>
              <a href="delete_lot.php?id=<?= $lot['lot_id'] ?>" class="btn btn-danger btn-sm"
              onclick="return confirm('Are you sure you want to delete this School?')">Delete</a></td>
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
<!-- Add School Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add School</h5></div>
      <div class="modal-body">
        <form method="POST" id="addForm">
          <input type="hidden" value="<?=$_GET['id']?>" name="project_id" class="form-control">
          <div class="mb-3"><label>School ID</label><input type="text" name="school_id" class="form-control"></div>
          <div class="mb-3"><label>School Name</label><input type="text" name="school_name" class="form-control"></div>
          <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control"></div>
          <div class="mb-3"><label>Contact Person</label><input type="text" name="person" class="form-control"></div>
          <div class="mb-3"><label>Contact</label><input type="text" name="contact" class="form-control"></div>
          <div class="mb-3"><label>Municipality</label><input type="text" name="municipality" class="form-control"></div>
          <div class="mb-3"><label>Division</label><input type="text" name="division" class="form-control"></div>
          <div class="mb-3"><label>Region</label><input type="text" name="region" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" onclick="addForm('schools','add_school.php')">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Import School Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Import Schools</h5></div>
      <div class="modal-body">
        <form>
          <input type="hidden" value="<?=$_GET['id']?>" class="form-control">
          <div class="mb-3"><label>CSV file</label><input type="file" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit School Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Edit School</h5></div>
      <div class="modal-body">
        <form method="POST" action="script/edit_school.php">
          <input type="hidden" name="project_id" value="<?=$_GET['id']?>" class="form-control">
          <div class="mb-3"><label>School ID</label><input required id="editid" name="id" type="text" class="form-control"></div>
          <div class="mb-3"><label>School Name</label><input required id="editname" name="school" type="text" class="form-control"></div>
          <div class="mb-3"><label>Address</label><input required id="editaddress" name="address" type="text" class="form-control"></div>
          <div class="mb-3"><label>Contact Person</label><input required id="editperson" name="person" type="text" class="form-control"></div>
          <div class="mb-3"><label>Contact</label><input required id="editcontact" name="contact" type="text" class="form-control"></div>
          <div class="mb-3"><label>Municipality</label><input required id="editmunicipality" name="municipality" type="text" class="form-control"></div>
          <div class="mb-3"><label>Division</label><input required id="editdivision" name="division" type="text" class="form-control"></div>
          <div class="mb-3"><label>Region</label><input required id="editregion" name="region" type="text" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" id="">Save</button>
      </div>
    </div>
  </div>
</div>
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
//populate region filter
document.addEventListener("DOMContentLoaded", function () {
    // On page load, populate regions
    populateFilter("filterRegion", "SELECT DISTINCT region AS options FROM school ORDER BY region ASC");
    hideLoading()
});

//filter
document.getElementById("filterButt").addEventListener("click", function() {
    let region = document.getElementById("filterRegion").value;
    let division = document.getElementById("filterDivision").value;
    let municipality = document.getElementById("filterMunicipality").value;
    let tbody = document.getElementById("resultTable");
    tbody.innerHTML = "";
    let pagination = document.getElementById("pagination");
    pagination.innerHTML = "";

    if(region != ""){
      region = "region=" + region
    }
    if(division != ""){
      division = "&division=" + division
    }
    if(municipality != ""){
      municipality = "&municipality=" + municipality
    }
    showLoading();

     fetch("script/filterSchool.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:  region + division + municipality
    })
    .then(res => res.json())
    .then(data => {
        if (data.length > 0) {
            data.forEach(row => {
                tbody.innerHTML += `
                                <tr>
                                    <td id="id${row.school_id}s">${row.school_id}</td>
                                    <td id="name${row.school_id}s">${row.school_name}</td>
                                    <td>
                                    <span id="address${row.school_id}s">${row.address}</span>, 
                                    <span id="municipality${row.school_id}s">${row.municipality}</span>, 
                                    <span id="division${row.school_id}s">${row.division}</span>, 
                                    <span id="region${row.school_id}s">${row.region}</span>
                                    </span></td>
                                    <td id="person${row.school_id}s">${row.contact_person}</td>
                                    <td id="contact${row.school_id}s">${row.contact}</td>
                                    <td>
                                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" onclick="updateEdit(${row.school_id})">Edit School</button>
                                    <a href="delete_lot.php?id=" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this School?')">Delete</a></td>
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
    //populate filters
    document.getElementById("filterRegion").addEventListener("change", function() {
    let region = document.getElementById("filterRegion").value;
    if(region != ""){
    document.getElementById("filterDivision").disabled = false;
    document.getElementById("filterButt").disabled = false;
    document.getElementById("rmvFilter").disabled = false;
    }else{
    document.getElementById("rmvFilter").click()
    document.getElementById("filterButt").disabled = true;
    document.getElementById("rmvFilter").disabled = true;
    }
    populateFilter("filterDivision", "SELECT DISTINCT division AS options FROM school WHERE region = '" + region + "' ORDER BY division ASC" );
});
document.getElementById("filterDivision").addEventListener("change", function() {
    document.getElementById("filterMunicipality").disabled = false;
    let division = document.getElementById("filterDivision").value;
    populateFilter("filterMunicipality", "SELECT DISTINCT municipality AS options FROM school WHERE division = '" + division + "' ORDER BY municipality ASC" );
});
</script>
<?php require "template/footer.php"; ?>