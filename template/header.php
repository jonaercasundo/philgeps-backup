<?php
session_start();

if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['username']) || 
    !isset($_SESSION['name']) || 
    !isset($_SESSION['role'])) {
    
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MMC Project Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/bootstrap-5.3.7-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css">

  <!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="assets/uploads/logo/favicon_io/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/uploads/logo/favicon_io/favicon-16x16.png">
<link rel="shortcut icon" href="assets/uploads/logo/favicon_io/favicon.ico">

<!-- Apple Touch Icon -->
<link rel="apple-touch-icon" href="assets/uploads/logo/favicon_io/apple-touch-icon.png">

<!-- Android Chrome -->
<link rel="icon" type="image/png" sizes="192x192" href="assets/uploads/logo/favicon_io/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="assets/uploads/logo/favicon_io/android-chrome-512x512.png">

<!-- Web Manifest -->
<link rel="manifest" href="assets/uploads/logo/favicon_io/site.webmanifest">

<!-- Optional: Theme color for Android address bar -->
<meta name="theme-color" content="#ffffff">

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Leaflet Geocoder Plugin -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

  <style>
    a.disabled {
      pointer-events: none;
      cursor: default;
    }
  </style>
</head>
<body>

<?php
$mainNav = [
    "dashboard.php" => "Dashboard",
    "projects.php" => "Projects",
    "deliveries.php" => "Deliveries",
    "inventory.php" => "Warehouse",
    "logistics.php" => "Logistics",
    "billing.php" => "<span class='text-decoration-line-through'>Billing</span>",
    "reports.php" => "<span class='text-decoration-line-through'>Reports</span>",
    "users.php" => "Users"
];
// Define main navigation links
switch($_SESSION['role']){
  case "Office Admin":
    $mainNav = [
    "dashboard.php" => "Dashboard",
    "projects.php" => "Projects",
    "deliveries.php" => "Deliveries",
    "inventory.php" => "Warehouse",
    "logistics.php" => "Logistics",
    ];
  break;
  case "Warehouse Admin":
    $mainNav = [
    "deliveries.php" => "Deliveries",
    "inventory.php" => "Warehouse"
    ];
    
  break;
  case "Warehouse Coordinator":
    $mainNav = [
      
    ];
    
  break;
  case "Office Coordinator":
    $mainNav = [
      "projects.php" => "Projects",
      "deliveries.php" => "Deliveries",
      "logistics.php" => "Logistics",
    ];
    
  break;
  case "Logistics":
    $mainNav = [
      // "deliveries.php" => "Deliveries",
      "logistics.php" => "Logistics",
    ];
  break;
  case "Viewer":
    $mainNav = [
      "projects.php" => "Projects",
      "deliveries.php" => "Deliveries",
      "logistics.php" => "Logistics",
    ];
    
  break;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">MMC PROJECT Tracker</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <?php foreach($mainNav as $file => $label): ?>
          <li class="nav-item">
            <a class="nav-link <?= ($currentPage === $file) ? 'active' : ''; if($file === "billing.php" || $file === "reports.php"){echo " disabled";}?>" href="<?= $file ?>"><?= $label ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <a href="script/logout.php" class="btn btn-danger">
        <i class="bi bi-box-arrow-right"></i>
      </a>

    </div>
  </div>
</nav>
<?php
// This check assumes $is_warehouse_page is set to true on the relevant pages
if (isset($is_warehouse_page) && $is_warehouse_page === true): 

    $warehouseNav = [
      // 'warehouse.php' => 'Overview',
      'warehouse_details.php' => 'Warehouse',
      'inventory.php' => 'Inventory',
      'warehouse_reports.php' => 'Reports'
  ];
  if($_SESSION['role'] == 'Warehouse Admin'){
    $warehouseNav = [
      // 'warehouse.php' => 'Overview',
      'inventory.php' => 'Inventory',
      'warehouse_reports.php' => 'Reports'
  ];
  }elseif($_SESSION['role'] == 'Warehouse Coordinator'){
    $warehouseNav = [
      // 'warehouse.php' => 'Overview',
      'inventory.php' => 'Inventory'
  ];
  }
?>

<nav id="warehouse_nav" class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid justify-content-center">
        <ul class="nav navbar-nav">
            <?php foreach($warehouseNav as $file => $label): ?>
                 <li class="nav-item">
                    <a 
                        class="nav-link <?= (isset($currentPage) && $currentPage === $file) ? 'active' : '' ?>" 
                        href="<?= $file ?>"
                    >
                        <?= $label ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<?php endif; ?>



<?php
// This check assumes $is_logistics_page is set to true on the relevant pages
if (isset($is_logistics_page) && $is_logistics_page === true): 

    $logisticsNav = [
      'logistics.php' => 'Overview',
      'logistics_details.php' => 'Logistics',
      'logistics_package.php' => 'Packages',
      'logistics_location.php' => 'Location',
      'logistics_reports.php' => 'Reports'
    ];
?>

<nav id="logistics_nav" class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid justify-content-center">
        <ul class="nav navbar-nav">
            <?php foreach($logisticsNav as $file => $label): ?>
                 <li class="nav-item">
                    <a 
                        class="nav-link <?= (isset($currentPage) && $currentPage === $file) ? 'active' : '' ?>" 
                        href="<?= $file ?>"
                    >
                        <?= $label ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<?php endif; ?>
<?php
// Project detail navigation, only if 'id' is set
if (isset($_GET['id'])):
    $id = (int)$_GET['id'];
    include 'config/db.php';
    $stmt = $pdo->query("
        SELECT keystage from projects WHERE project_id = $id
    ");
    $keystage = $stmt->fetch(PDO::FETCH_ASSOC);
    $projectNav = [
        "project_details.php" => "Overview",
        "schools.php" => "Schools",
        "lots.php" => "Lots"
    ];
    if ($keystage && $keystage['keystage'] == 1) {
        $projectNav["keystage.php"] = "Keystage";
    }
    $projectNav += [
        "items.php" => "Items",
        "packages.php" => "Packages",
        "project_reports.php" => "Reports"
    ];
?>

<nav id="project_detail_nav" class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container-fluid justify-content-center">
    <ul class="nav navbar-nav">
      <?php foreach($projectNav as $file => $label): ?>
        <li class="nav-item">
          <a class="nav-link <?= ($currentPage === $file) ? 'active' : '' ?>" href="<?= $file ?>?id=<?= $id ?>"><?= $label ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>
<?php endif; ?>

<?php
// This check assumes $is_deliveries_page is set to true on the relevant pages
if (isset($is_deliveries_page) && $is_deliveries_page === true): 

    $deliveriesNav = [
      'deliveries.php' => 'All Deliveries',
      'manual_checking.php' => 'Deliveries Grouping',
  ];
  if($_SESSION['role'] == 'Warehouse Admin'){
    $deliveriesNav = [

  ];
  }elseif($_SESSION['role'] == 'Warehouse Coordinator'){
    $deliveriesNav = [
      
  ];
  }
?>

<nav id="deliveries_nav" class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid justify-content-center">
        <ul class="nav navbar-nav">
            <?php foreach($deliveriesNav as $file => $label): ?>
                 <li class="nav-item">
                    <a 
                        class="nav-link <?= (isset($currentPage) && $currentPage === $file) ? 'active' : '' ?>" 
                        href="<?= $file ?>"
                    >
                        <?= $label ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<?php endif; ?>


<div class="container-fluid mt-4">
