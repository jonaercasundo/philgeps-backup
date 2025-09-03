<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PhilGEPS Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!--link rel="stylesheet" href="assets/css/style.css"-->
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">PhilGEPS Tracker</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "dashboard.php"){echo "active";}; ?>" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "projects.php"){echo "active";}; ?>" href="projects.php">Projects</a></li>
        <!--li class="nav-item"><a class="nav-link" href="procurement.php">Procurement</a></li-->
        <li class="nav-item"><a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "deliveries.php"){echo "active";}; ?>" href="deliveries.php">Deliveries</a></li>
        <li class="nav-item"><a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "billing.php"){echo "active";}; ?>" href="billing.php">Billing</a></li>
        <li class="nav-item"><a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "reports.php"){echo "active";}; ?>" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "users.php"){echo "active";}; ?>" href="users.php">Users</a></li>
      </ul>
      <a href="script/logout.php" class="btn btn-danger">🏃</a>
    </div>
  </div>
</nav>
<nav id="project_detail_nav" class="navbar navbar-expand-lg navbar-dark bg-dark d-flex sticky-top d-none">
     <div class="container-fluid w-100 justify-content-center">
        <ul class="nav navbar-nav justify-content-center">
        <li class="nav-item">
          <?php if(isset($_GET['id'])){ $id=$_GET['id']?>
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "project_details.php"){echo "active";}; ?>" aria-current="page" href="project_details.php?<?php echo "id=$id"?>">Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "schools.php"){echo "active";}; ?>" aria-current="page" href="schools.php?<?php echo "id=$id"?>">Schools</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "lots.php"){echo "active";}; ?>" aria-current="page" href="lots.php?<?php echo "id=$id"?>">Lots</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "keystage.php"){echo "active";}; ?>" aria-current="page" href="keystage.php?<?php echo "id=$id"?>">Keystage</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "packages.php"){echo "active";}; ?>" aria-current="page" href="packages.php?<?php echo "id=$id"?>">Packages</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "items.php"){echo "active";}; ?>" aria-current="page" href="items.php?<?php echo "id=$id"?>">Items</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == "project_reports.php"){echo "active";}; ?>" aria-current="page" href="project_reports.php?<?php echo "id=$id"?>">Reports</a>
        </li>
            <?php }?>
        </ul>
    </div>
</nav>
<div class="container mt-4">
