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

    // ALWAYS go to scan.php first (QR MUST be deterministic)
    window.location.replace(
      `scan.php?id=${id}&delivery_id=${delivery_id}`
    );
  </script>
</head>
<body>
  Redirecting...
</body>
</html>
