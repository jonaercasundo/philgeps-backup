<?php
session_start();

if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['username']) || 
    !isset($_SESSION['name'])) {
    
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
</head>
<body>

<?php
// Define main navigation links
$mainNav = [
    "dashboard.php" => "Dashboard",
    "projects.php" => "Projects",
    "deliveries.php" => "Deliveries",
    "billing.php" => "Billing",
    "reports.php" => "Reports",
    "users.php" => "Users"
];

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
            <a class="nav-link <?= ($currentPage === $file) ? 'active' : '' ?>" href="<?= $file ?>"><?= $label ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <a href="script/logout.php" class="btn btn-danger">🏃</a>
    </div>
  </div>
</nav>

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
        "packages.php" => "Packages",
        "items.php" => "Items",
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

<div class="container mt-4">
