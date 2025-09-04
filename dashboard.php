<?php 
require "template/header.php"; 
require "config/db.php";

try {
    // Aggregate counts and sums in one query
    $stmt = $pdo->query("
        SELECT
            SUM(CASE WHEN status='Ongoing' THEN 1 ELSE 0 END) AS activeProjects,
            SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completedProjects,
            (SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='Paid') AS totalPaid,
            (SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status='Pending') AS totalPending
        FROM projects
    ");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    // Projects per Year
    $stmt = $pdo->query("
        SELECT YEAR(start_date) AS year, COUNT(*) AS total 
        FROM projects WHERE status='Ongoing' 
        GROUP BY YEAR(start_date) 
        ORDER BY year
    ");
    $projectsPerYear = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Projects by Agency
    $stmt = $pdo->query("
        SELECT agency, COUNT(*) AS total 
        FROM projects 
        GROUP BY agency 
        ORDER BY total DESC
    ");
    $projectsByAgency = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total Contract Amount per Year
    $stmt = $pdo->query("
        SELECT YEAR(start_date) AS year, SUM(contract_amount) AS total 
        FROM projects WHERE status='Ongoing'
        GROUP BY YEAR(start_date) 
        ORDER BY year
    ");
    $amountPerYear = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<div class="row mb-4">
  <?php 
  $cards = [
      ['title'=>'Active Projects','value'=>$totals['activeProjects'],'class'=>'primary'],
      ['title'=>'Total Value','value'=>'₱'.$totals['totalPaid'],'class'=>'success'],
      ['title'=>'Pending Payments','value'=>'₱'.$totals['totalPending'],'class'=>'warning'],
      ['title'=>'Completed Projects','value'=>$totals['completedProjects'],'class'=>'dark']
  ];
  foreach($cards as $c): ?>
  <div class="col-md-3">
    <div class="card text-bg-<?=$c['class']?> shadow">
      <div class="card-body"><?= $c['title'] ?><br><strong><?= $c['value'] ?></strong></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <!-- Projects by Agency (Pie) -->
  <div class="col-md-7 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Projects by Agency</h5>
        <canvas id="projectsByAgencyChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Projects per Year & Total Contract Amount per Year -->
  <div class="col-md-5">
    <div class="mb-4 card shadow">
      <div class="card-body">
        <h5>Projects per Year</h5>
        <canvas id="projectsPerYearChart" height="200"></canvas>
      </div>
    </div>
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
const projectsPerYear = <?= json_encode($projectsPerYear) ?>;
const projectsByAgency = <?= json_encode($projectsByAgency) ?>;
const amountPerYear = <?= json_encode($amountPerYear) ?>;

// 1. Projects per Year (Bar)
new Chart(document.getElementById('projectsPerYearChart'), {
  type: 'bar',
  data: {
    labels: projectsPerYear.map(r => r.year),
    datasets: [{ 
      label: 'Projects',
      data: projectsPerYear.map(r => r.total),
      backgroundColor: 'rgba(54,162,235,0.7)',
      borderColor: 'rgba(54,162,235,1)',
      borderWidth: 1
    }]
  }
});

// 2. Projects by Agency (Pie)
new Chart(document.getElementById('projectsByAgencyChart'), {
  type: 'pie',
  data: {
    labels: projectsByAgency.map(r => r.agency),
    datasets: [{
      label: 'Projects',
      data: projectsByAgency.map(r => r.total),
      backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#6f42c1','#17a2b8']
    }]
  }
});

// 3. Total Contract Amount per Year (Line)
new Chart(document.getElementById('amountPerYearChart'), {
  type: 'line',
  data: {
    labels: amountPerYear.map(r => r.year),
    datasets: [{
      label: '₱ Total Amount',
      data: amountPerYear.map(r => r.total),
      borderColor: 'rgba(75,192,192,1)',
      backgroundColor: 'rgba(75,192,192,0.2)',
      fill: true,
      tension: 0.3
    }]
  }
});
</script>
<?php require "template/footer.php"; ?>
