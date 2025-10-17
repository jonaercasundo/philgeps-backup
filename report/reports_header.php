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