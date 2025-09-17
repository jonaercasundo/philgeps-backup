<?php 
require "template/header.php"; 
require "config/db.php";
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
try {
    // 1. Overall Progress Summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'Pending') AS pending,
            SUM(status = 'Delivered') AS delivered,
            SUM(status = 'Accepted') AS accepted
        FROM deliveries WHERE project_id = $reportId
    ");
    $deliveryStats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($deliveryStats['accepted']!=0){
    $deliveryStats['accepted'] = $deliveryStats['accepted']/2; // Adjust accepted count for display
    }
    $overallProgress = $deliveryStats['total'] > 0 
        ? round(($deliveryStats['delivered']+$deliveryStats['accepted']) / $deliveryStats['total'] * 100, 1)
        : 0;

    // 2. Progress per Region
    $stmt = $pdo->query("
        SELECT 
            s.region,
            COUNT(*) AS total,
            SUM(d.status = 'Pending') AS pending,
            SUM(d.status = 'Delivered') AS delivered,
            SUM(d.status = 'Accepted') AS accepted
        FROM deliveries d
        JOIN school s ON s.school_id = d.school_id
        WHERE d.project_id = $reportId
        GROUP BY s.region
        ORDER BY s.region
    ");
    $progressPerRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Progress per Lot
    $stmt = $pdo->query("
        SELECT 
            l.lot_name,
            COUNT(*) AS total,
            SUM(d.status = 'Pending')   AS pending,
            SUM(d.status = 'Delivered') AS delivered,
            SUM(d.status = 'Accepted')  AS accepted
        FROM deliveries d
        LEFT JOIN keystage k ON d.keystage_id = k.keystage_id
        JOIN lot l ON l.lot_id = COALESCE(l.lot_id, k.lot_id)
        WHERE d.project_id = $reportId
        GROUP BY l.lot_name
        ORDER BY l.lot_name
    ");
    $progressPerLot = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<style>
  #deliveryStatusChart {
   height: 250px !important;
  }
</style>
<div class="row mb-4">
  <!-- Overall Progress -->
  <div class="col-md-3">
    <div class="card text-bg-success shadow">
      <div class="card-body text-center">
        <h6>Overall Progress</h6>
        <h3><strong><?= $overallProgress ?>%</strong></h3>
        <div class="progress mt-2" style="height: 10px;">
          <div id="overallProgressBar" class="progress-bar bg-dark" style="width: <?= $overallProgress ?>%;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Status Breakdown -->
  <?php 
  $statuses = [
      ['label' => 'Pending', 'value' => $deliveryStats['pending'], 'color' => 'warning'],
      ['label' => 'Delivered', 'value' => $deliveryStats['delivered'], 'color' => 'info'],
      ['label' => 'Accepted', 'value' => $deliveryStats['accepted'], 'color' => 'primary']
  ];
  foreach ($statuses as $s): ?>
    <div class="col-md-3">
      <div class="card text-bg-<?= $s['color'] ?> shadow">
        <div class="card-body text-center">
          <h6><?= $s['label'] ?></h6>
          <h3><strong><?= $s['value'] ?></strong></h3>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <!-- Accepted Deliveries by Region -->
  <div class="col-md-6 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Accepted Deliveries by Region (%)</h5>
        <canvas id="acceptedPerRegionChart" height="180"></canvas>
      </div>
    </div>
  </div>

  <!-- Delivered Deliveries by Region -->
  <div class="col-md-6 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Delivered Deliveries by Region (%)</h5>
        <canvas id="deliveredPerRegionChart" height="180"></canvas>
      </div>
    </div>
  </div>

  <!-- Accepted Deliveries by Lot -->
  <div class="col-md-6 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Accepted Deliveries by Lot (%)</h5>
        <canvas id="acceptedPerLotChart" height="180"></canvas>
      </div>
    </div>
  </div>

  <!-- Delivered Deliveries by Lot -->
  <div class="col-md-6 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Delivered Deliveries by Lot (%)</h5>
        <canvas id="deliveredPerLotChart" height="180"></canvas>
      </div>
    </div>
  </div>
</div>


<div class="row">
  <div class="col-md-6 offset-md-3">
    <div class="card shadow">
      <div class="card-body">
        <h5 class="text-center">Delivery Status Breakdown</h5>
        <div style="max-width: 300px; margin: auto;">
          <canvas id="deliveryStatusChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let acceptedPerRegionChart, deliveredPerRegionChart, acceptedPerLotChart, deliveredPerLotChart, deliveryStatusChart;

async function fetchReportData() {
  const response = await fetch("script/get_report_data.php?id=<?= $reportId ?>");
  return await response.json();
}

function animateCount(element, start, end, duration = 800) {
  let startTime = null;
  function step(currentTime) {
    if (!startTime) startTime = currentTime;
    const progress = Math.min((currentTime - startTime) / duration, 1);
    const value = Math.floor(progress * (end - start) + start);
    element.innerText = value + (element.dataset.suffix || "");
    if (progress < 1) {
      requestAnimationFrame(step);
    }
  }
  requestAnimationFrame(step);
}

function renderCharts(data) {
  const { deliveryStats, overallProgress, progressPerRegion, progressPerLot } = data;

  // Update counters
  document.querySelector(".card.text-bg-success strong").innerText = overallProgress + "%";
  document.querySelectorAll(".card.text-bg-warning strong")[0].innerText = deliveryStats.pending;
  document.querySelectorAll(".card.text-bg-info strong")[0].innerText = deliveryStats.delivered;
  document.querySelectorAll(".card.text-bg-primary strong")[0].innerText = deliveryStats.accepted;

  // Overall Progress % text + bar
  const overallCard = document.querySelector(".card.text-bg-success strong");
  animateCount(overallCard, parseInt(overallCard.innerText) || 0, overallProgress);
  const progressBar = document.getElementById("overallProgressBar");
  progressBar.style.width = overallProgress + "%";

  // Pending
  const pendingEl = document.querySelector(".card.text-bg-warning strong");
  animateCount(pendingEl, parseInt(pendingEl.innerText) || 0, deliveryStats.pending);

  // Delivered
  const deliveredEl = document.querySelector(".card.text-bg-info strong");
  animateCount(deliveredEl, parseInt(deliveredEl.innerText) || 0, deliveryStats.delivered);

  // Accepted
  const acceptedEl = document.querySelector(".card.text-bg-primary strong");
  animateCount(acceptedEl, parseInt(acceptedEl.innerText) || 0, deliveryStats.accepted);


  // Update Progress Bar width
  progressBar.style.width = overallProgress + "%";
  progressBar.innerText = overallProgress + "%";

  // Destroy old charts if they exist
  [acceptedPerRegionChart, deliveredPerRegionChart, acceptedPerLotChart, deliveredPerLotChart, deliveryStatusChart]
    .forEach(c => { if (c) c.destroy(); });

  // Accepted % by Region
  acceptedPerRegionChart = new Chart(document.getElementById('acceptedPerRegionChart'), {
    type: 'bar',
    data: {
      labels: progressPerRegion.map(r => r.region),
      datasets: [{
        label: 'Accepted (%)',
        data: progressPerRegion.map(r => r.total > 0 ? (r.accepted / r.total * 100).toFixed(1) : 0),
        backgroundColor: 'rgba(13,110,253,0.7)'
      }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
  });

  // Delivered % by Region
  deliveredPerRegionChart = new Chart(document.getElementById('deliveredPerRegionChart'), {
    type: 'bar',
    data: {
      labels: progressPerRegion.map(r => r.region),
      datasets: [{
        label: 'Delivered (%)',
        data: progressPerRegion.map(r => r.total > 0 ? (r.delivered / r.total * 100).toFixed(1) : 0),
        backgroundColor: 'rgba(255,193,7,0.7)'
      }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
  });

  // Accepted % by Lot
  acceptedPerLotChart = new Chart(document.getElementById('acceptedPerLotChart'), {
    type: 'bar',
    data: {
      labels: progressPerLot.map(r => r.lot_name),
      datasets: [{
        label: 'Accepted (%)',
        data: progressPerLot.map(r => r.total > 0 ? (r.accepted / r.total * 100).toFixed(1) : 0),
        backgroundColor: 'rgba(40,167,69,0.7)'
      }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
  });

  // Delivered % by Lot
  deliveredPerLotChart = new Chart(document.getElementById('deliveredPerLotChart'), {
    type: 'bar',
    data: {
      labels: progressPerLot.map(r => r.lot_name),
      datasets: [{
        label: 'Delivered (%)',
        data: progressPerLot.map(r => r.total > 0 ? (r.delivered / r.total * 100).toFixed(1) : 0),
        backgroundColor: 'rgba(255,159,64,0.7)'
      }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
  });

  // Pie Chart
  deliveryStatusChart = new Chart(document.getElementById('deliveryStatusChart'), {
    type: 'pie',
    data: {
      labels: ['Pending', 'Delivered', 'Accepted'],
      datasets: [{
        data: [deliveryStats.pending, deliveryStats.delivered, deliveryStats.accepted],
        backgroundColor: ['#ffc107', '#17a2b8', '#0d6efd']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
  });
}

// Auto refresh every 1 minute
async function updateDashboard() {
  const data = await fetchReportData();
  renderCharts(data);
}
updateDashboard(); // first load
setInterval(updateDashboard, 60000); // every 1 min
</script>


<?php require "template/footer.php"; ?>
