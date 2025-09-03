<?php require "template/header.php"; 
      require "config/db.php";

      try {
        // Pagination settings
        $limit = 10; // Records per page
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM deliveries");
        $stmt->execute();
        $total_rows = $stmt->fetchColumn();
        $total_pages = ceil($total_rows / $limit);

        // use prepared statements for security
        $stmt = $pdo->prepare("SELECT project_id, project_name, agency FROM projects");
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
        SELECT d.*, p.project_id, p.project_name
        FROM deliveries d JOIN projects p ON p.project_id = d.project_id ORDER BY delivery_id ASC LIMIT $limit OFFSET $offset;
        ");
        $stmt->execute();
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
          die("DB Error: " . $e->getMessage());
      }
?>

<div class="d-flex justify-content-between mb-3">
  <h4>Deliveries</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeliveryModal">Add Delivery</button>
</div>
<div class="row mb-3">
  <div class="col-md-3">
    <label>Year</label>
    <select class="form-select filter" id="year"></select>
  </div>
  <div class="col-md-3">
    <label>Project</label>
    <select class="form-select filter" id="filterProjects" disabled></select>
  </div>
  <div class="col-md-3">
    <label>Status</label>
    <select class="form-select filter" id="filterStatus" disabled></select>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <button class="btn btn-primary w-100 me-2" id="filterButt">Filter</button>
    <button class="btn btn-primary w-100" id="rmvFilter">Remove Filter</button>
  </div>
</div>
<table class="table table-bordered shadow-sm">
  <thead class="table-dark">
    <tr>
      <th>Project</th><th>School</th><th>Address</th><th>Content</th><th>DR No</th><th>Date</th><th>Status</th><th>Actions</th>
    </tr>
  </thead>
  <tbody id="resultTable">
<?php 
foreach($deliveries as $delivery){
  $project_name = $delivery['project_name'];
  $dr_no = $delivery['dr_no'];
  $delivery_id = $delivery['delivery_id'];
  $delivery_date = $delivery['delivery_date'];
  $status = $delivery['status'];
  $content = $delivery['remarks'];
  $school = $delivery['school'];
  $address = $delivery['address'];

  echo "<tr>
        <td>".mb_strimwidth($project_name, 0, 50, '...')."</td><td>$school</td><td>$address</td><td>$content</td><td>$dr_no</td><td>$delivery_date</td><td>$status</td>
        <td><button class='btn btn-sm btn-success mb-1' onclick=upadateStatus('$delivery_id')>Change Status</button><a class='btn btn-sm btn-success' href='generate_qr.php?id=$delivery_id' target='_blank'>Generate QR</a></td>
      </tr>";
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

<!-- Add Delivery Modal -->
<div class="modal fade" id="addDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add Delivery</h5></div>
      <div class="modal-body">
        <form method="POST" action="script/save_deliveries.php" enctype="multipart/form-data">
          <div class="mb-3"><label>Project</label>
          <select name="project" class="form-select" id="project" onchange ="checkAgency(this)" required>
            <option value="#">Select Project</option>
            <?php 
            foreach($projects as $project){
              $project_id = $project['project_id'];
              $project_name = mb_strimwidth($project['project_name'], 0, 50, '...');
              if($project['agency'] == 'Deped'){
              echo "
              <option data-extra='Deped' value='$project_id'>$project_name</option>
              ";
              }else{
                echo "
              <option value='$project_id'>$project_name</option>
              ";
              };
            }
            ?>
          </select></div>
          <div class="mb-3 visually-hidden deped">
            <label for="schoolSearch" class="form-label">Search School</label>
            <input name="school" type="text" id="schoolSearch" class="form-control" placeholder="Type school name...">
            <ul id="schoolResults" class="list-group position-absolute w-100" style="z-index:1000;"></ul>
          </div>
            <input type="hidden" id="address" name="address" value="">
          <div class="mb-3 visually-hidden deped"><label>Lot</label>
          <select name="lot" type="text" class="form-control" id="lotSelect" onchange="getKeystage(this.value)">
            <option value="#">Select Keystage</option>
          </select>
          </div>
          <div class="mb-3 visually-hidden deped"><label>Keystage</label>
          <select name="keystage" type="text" class="form-control" id="keystageSelect">
            
          </select>
          </div>
          <div class="mb-3 visually-hidden deped"><label>Package Type</label>
          <select name="package_type" type="text" class="form-control">
            <option value="c1">C1</option>
            <option value="c2">C2</option>
            <option value="c3">C3</option>
            <option value="c4">C4</option>
            <option value="c5">C5</option>
            <option value="c6">C6</option>
          </select>
          </div>
          <div class="mb-3"><label>DR Number</label><input name="DRN" type="text" class="form-control"></div>
          <div class="mb-3"><label>Date</label><input type="date" name="dateDeliver" class="form-control"></div>
          </form>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
      </div>
    </div>
  </div>
</div>
<script>

  function checkAgency(agency){
    let selectedOption = agency.options[agency.selectedIndex];
    let extra = selectedOption.getAttribute("data-extra");
    let projects = document.getElementsByClassName("deped");
    
    switch(extra){
      case 'Deped':  
        for (let project of projects) {project.classList.remove("visually-hidden")};

         // Call backend to get project data
       fetch("script/get_project.php?projectid=" + encodeURIComponent(selectedOption.value))
      .then(res => res.json())
      .then(data => {
        populateSelect("lotSelect", data.lots);

        if (!data.lots || data.lots.length === 0) {
          window.location.href = window.location.pathname + "?toast=No Lots Added to Project&type=danger";
        }
      })
      .catch(err => console.error("Error:", err));


      break;
      default: 
      for (let project of projects) {project.classList.add("visually-hidden")};
        break;
    }

  }

//populate year filter
document.addEventListener("DOMContentLoaded", function () {
    // On page load, populate regions
    populateFilter("year", "SELECT DISTINCT Year(created_at) AS options FROM deliveries ORDER BY created_at ASC");
    hideLoading()
});

//filter
document.getElementById("filterButt").addEventListener("click", function() {
    let year = document.getElementById("year").value;
    let project = document.getElementById("filterProjects").value;
    let status = document.getElementById("filterStatus").value;
    let tbody = document.getElementById("resultTable");
    tbody.innerHTML = "";
    let pagination = document.getElementById("pagination");
    pagination.innerHTML = "";

    if(year != ""){
      year = "year=" + year
    }
    if(project != ""){
      project = "&project_id=" + project
    }
    if(status != ""){
      status = "&status=" + status
    }
    showLoading();

     fetch("script/filterDeliveries.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:  year + project + status
    })
    .then(res => res.json())
    .then(data => {
        if (data.length > 0) {
            data.forEach(row => {
                tbody.innerHTML += `
                <tr>
        <td>${truncateText(row.project_name)}</td><td>${row.school}</td><td>${row.address}</td><td>${row.remarks}</td><td>${row.dr_no}</td><td>${row.delivery_date}</td><td>${row.status}</td>
        <td><button class='btn btn-sm btn-success mb-1' onclick=upadateStatus('${row.delivery_id}')>Change Status</button><a style='height:100px;' class='btn btn-sm btn-success' href='generate_qr.php?id=${row.delivery_id}' target='_blank'><img style='height:100%;' src='generate_qr.php?id=123'></a></td>
      </tr>`;
            });
        }
    })
    .catch(err => console.error("Error:", err))
    .finally(() => {
        hideLoading();
    });
    });
    
//filter populate
    document.getElementById("year").addEventListener("change", function() {
    let year = document.getElementById("year").value;
    if(year != ""){
    document.getElementById("filterProjects").disabled = false;
    }else{
    document.getElementById("rmvFilter").click()
    }
    populateFilter("filterProjects", "SELECT DISTINCT p.project_id, p.project_name AS options FROM deliveries d JOIN projects p ON d.project_id = p.project_id WHERE YEAR(d.created_at) = '" + year + "' ORDER BY p.project_id ASC;" );
});


document.getElementById("filterProjects").addEventListener("change", function() {
    document.getElementById("filterStatus").disabled = false;
    let project_id = document.getElementById("filterProjects").value;
    populateFilter("filterStatus", "SELECT DISTINCT status AS options FROM deliveries WHERE project_id = '" + project_id + "' ORDER BY status ASC" );
});
</script>
<script src="assets/js/ajax.js"></script>
<?php require "template/footer.php"; ?>
