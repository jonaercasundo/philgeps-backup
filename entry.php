<?php
$id = $_GET['id'] ?? null;
$delivery_id = $_GET['delivery_id'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Loading...</title>
  <script>
    const id = "<?= $id ?>";
    const delivery_id = "<?= $delivery_id ?>";
    
    if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/philgeps/sw.js')
        .then(reg => console.log('Service Worker registered:', reg.scope))
        .catch(err => console.error('Service Worker registration failed:', err));
    }
    if (navigator.onLine) {
      // If online → continue to scan.php with same params
      window.location.href = `scan.php?id=${id}&delivery_id=${delivery_id}`;
    } else {
      // If offline → go to offline page
      window.location.href = `offline_scan.php?id=${id}&delivery_id=${delivery_id}`;
    }
  </script>
</head>
<body>
  Redirecting...
</body>
</html>
