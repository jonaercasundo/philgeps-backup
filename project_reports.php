<?php 
require "template/header.php"; 
require "config/db.php";

try {
    // 1. Summary Cards
    $stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM school) AS totalSchools,
            (SELECT COUNT(*) FROM deliveries WHERE dr_no = ' ') AS noDrNo,
            (SELECT COUNT(DISTINCT s.school_id) 
                FROM school s 
                LEFT JOIN deliveries d 
                ON s.school_id = CAST(SUBSTRING_INDEX(d.school, ' ', 1) AS UNSIGNED)
                WHERE d.delivery_id IS NULL) AS schoolsNoDeliveries,
            (SELECT COUNT(*) FROM deliveries WHERE status='Pending' AND delivery_date < CURDATE()) AS overduePending
    ");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Schools per Region (Pie)
    $stmt = $pdo->query("
        SELECT region, COUNT(*) AS total 
        FROM school 
        GROUP BY region 
        ORDER BY total DESC
    ");
    $schoolsPerRegion = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Items per Lot (Bar)
    $stmt = $pdo->query("
        SELECT l.lot_name, COUNT(i.item_id) AS total
        FROM lot l
        LEFT JOIN keystage k ON l.lot_id = k.lot_id
        LEFT JOIN package p ON k.keystage_id = p.keystage_id
        LEFT JOIN package_content pc ON p.package_id = pc.package_id
        LEFT JOIN item i ON pc.item_id = i.item_id
        GROUP BY l.lot_name
        ORDER BY l.lot_name
    ");
    $itemsPerLot = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Deliveries by Delivered Date (Line/Graph)
    $stmt = $pdo->query("
        SELECT DATE(delivered_date) AS ddate, COUNT(*) AS total
        FROM deliveries
        WHERE status='Delivered'
        GROUP BY DATE(delivered_date)
        ORDER BY ddate
    ");
    $deliveriesByDate = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<div class="row mb-4">
  <?php 
  $cards = [
      ['title'=>'Total Schools','value'=>$totals['totalSchools'],'class'=>'primary'],
      ['title'=>'Deliveries w/o DR No','value'=>$totals['noDrNo'],'class'=>'secondary'],
      ['title'=>'Schools w/o Deliveries','value'=>$totals['schoolsNoDeliveries'],'class'=>'warning'],
      ['title'=>'Overdue Pending Deliveries','value'=>$totals['overduePending'],'class'=>'danger']
  ];
  foreach($cards as $c): ?>
  <div class="col-md-3">
    <div class="card text-bg-<?=$c['class']?> shadow">
      <div class="card-body text-center">
        <h6><?= $c['title'] ?></h6>
        <h3><strong><?= $c['value'] ?></strong></h3>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <!-- Schools per Region (Pie) -->
  <div class="col-md-6 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Schools per Region</h5>
        <canvas id="schoolsPerRegionChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Items per Keystage (Bar) -->
  <div class="col-md-6 mb-4">
    <div class="card shadow">
      <div class="card-body">
        <h5>Items per Lot</h5>
        <canvas id="itemsPerKeystageChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Deliveries by Delivered Date (Line Graph) -->
  <div class="col-md-12">
    <div class="card shadow">
      <div class="card-body">
        <h5>Deliveries Over Time</h5>
        <canvas id="deliveriesByDateChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const schoolsPerRegion = <?= json_encode($schoolsPerRegion) ?>;
const itemsPerLot = <?= json_encode($itemsPerLot) ?>;
const deliveriesByDate = <?= json_encode($deliveriesByDate) ?>;

// 1. Schools per Region (Pie)
new Chart(document.getElementById('schoolsPerRegionChart'), {
  type: 'pie',
  data: {
    labels: schoolsPerRegion.map(r => r.region),
    datasets: [{
      label: 'Schools',
      data: schoolsPerRegion.map(r => r.total),
      backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#6f42c1','#17a2b8','#20c997','#6610f2']
    }]
  }
});


new Chart(document.getElementById('itemsPerKeystageChart'), {
  type: 'bar',
  data: {
    labels: itemsPerLot.map(r => r.lot_name),
    datasets: [{ 
      label: 'Items',
      data: itemsPerLot.map(r => r.total),
      backgroundColor: 'rgba(54,162,235,0.7)',
      borderColor: 'rgba(54,162,235,1)',
      borderWidth: 1
    }]
  },
  options: { 
    scales: { y: { beginAtZero: true } },
    plugins: { title: { display: true, text: 'Items per Lot' } }
  }
});


// 3. Deliveries by Delivered Date (Line)
new Chart(document.getElementById('deliveriesByDateChart'), {
  type: 'line',
  data: {
    labels: deliveriesByDate.map(r => r.ddate),
    datasets: [{
      label: 'Deliveries',
      data: deliveriesByDate.map(r => r.total),
      borderColor: 'rgba(75,192,192,1)',
      backgroundColor: 'rgba(75,192,192,0.2)',
      fill: true,
      tension: 0.3
    }]
  }
});
</script>

<?php require "template/footer.php"; ?>
