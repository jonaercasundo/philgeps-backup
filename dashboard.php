<?php require "template/header.php"; 
      require "config/db.php";

      try {
          $stmt = $pdo->prepare("SELECT COUNT(*) AS totalCount FROM projects WHERE status = 'Ongoing' ");
          $stmt->execute();
          $activeProjects = $stmt->fetch(PDO::FETCH_ASSOC);

          $stmt = $pdo->prepare("SELECT COUNT(*) AS totalCount FROM projects WHERE status = 'Completed' ");
          $stmt->execute();
          $completedProjects = $stmt->fetch(PDO::FETCH_ASSOC);

          $stmt = $pdo->prepare("SELECT SUM(amount)AS totalCount FROM invoices WHERE status = 'Paid'");
          $stmt->execute();
          $totalPaid = $stmt->fetch(PDO::FETCH_ASSOC);

          $stmt = $pdo->prepare("SELECT SUM(amount)AS totalCount FROM invoices WHERE status = 'Pending'");
          $stmt->execute();
          $totalPending = $stmt->fetch(PDO::FETCH_ASSOC);


          // Projects per Year
          $stmt = $pdo->query("SELECT YEAR(created_at) as year, COUNT(*) as total 
                              FROM projects WHERE status = 'Ongoing' GROUP BY YEAR(start_date) ORDER BY year");
          $projectsPerYear = $stmt->fetchAll(PDO::FETCH_ASSOC);

          // Projects by Agency
          $stmt = $pdo->query("SELECT agency, COUNT(*) as total 
                              FROM projects GROUP BY agency ORDER BY total DESC");
          $projectsByAgency = $stmt->fetchAll(PDO::FETCH_ASSOC);

          // Total Contract Amount per Year
          $stmt = $pdo->query("SELECT YEAR(created_at) as year, SUM(contract_amount) as total 
                              FROM projects WHERE status = 'Ongoing' GROUP BY YEAR(start_date) ORDER BY year");
          $amountPerYear = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
          die("DB Error: " . $e->getMessage());
      };
?>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-bg-primary shadow">
      <div class="card-body">Active Projects<br><strong><?=$activeProjects['totalCount']?></strong></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-success shadow">
      <div class="card-body">Total Value<br><strong>₱<?=$totalPaid['totalCount']?></strong></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-warning shadow">
      <div class="card-body">Pending Payments<br><strong>₱<?=$totalPending['totalCount']?></strong></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-dark shadow">
      <div class="card-body">Completed Projects<br><strong><?=$completedProjects['totalCount']?></strong></div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Column 1: Pie Chart -->
  <div class="col-md-7 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Projects by Agency</h5>
        <canvas id="projectsByAgencyChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Column 2 -->
  <div class="col-md-5">
    <!-- Bar Chart -->
    <div class="mb-4 card shadow">
      <div class="card-body">
        <h5>Projects per Year</h5>
        <canvas id="projectsPerYearChart" height="200"></canvas>
      </div>
    </div>

    <!-- Line Chart -->
    <div class="card shadow">
      <div class="card-body">
        <h5>Total Contract Amount per Year</h5>
        <canvas id="amountPerYearChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Data from PHP
  const projectsPerYear = <?=json_encode($projectsPerYear)?>;
  const projectsByAgency = <?=json_encode($projectsByAgency)?>;
  const amountPerYear = <?=json_encode($amountPerYear)?>;

  // 1. Projects per Year (Bar Chart)
  new Chart(document.getElementById('projectsPerYearChart'), {
    type: 'bar',
    data: {
      labels: projectsPerYear.map(r => r.year),
      datasets: [{
        label: 'Projects',
        data: projectsPerYear.map(r => r.total),
        backgroundColor: 'rgba(54, 162, 235, 0.7)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    }
  });

  // 2. Projects by Agency (Pie Chart)
  new Chart(document.getElementById('projectsByAgencyChart'), {
    type: 'pie',
    data: {
      labels: projectsByAgency.map(r => r.agency),
      datasets: [{
        label: 'Projects',
        data: projectsByAgency.map(r => r.total),
        backgroundColor: [
          '#007bff','#28a745','#ffc107','#dc3545','#6f42c1','#17a2b8'
        ]
      }]
    }
  });

  // 3. Total Contract Amount per Year (Line Chart)
  new Chart(document.getElementById('amountPerYearChart'), {
    type: 'line',
    data: {
      labels: amountPerYear.map(r => r.year),
      datasets: [{
        label: '₱ Total Amount',
        data: amountPerYear.map(r => r.total),
        borderColor: 'rgba(75, 192, 192, 1)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        fill: true,
        tension: 0.3
      }]
    }
  });
</script>
<?php require "template/footer.php"; ?>
