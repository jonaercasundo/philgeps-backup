<?php
// success.php
// You can pass ?status=Accepted or Delivered in URL if you want to display which one
$status = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : "Success";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Confirmation Successful</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">

  <div class="card shadow-lg text-center p-5" style="max-width: 500px; border-radius: 1rem;">
    <div class="mb-4">
      <svg xmlns="http://www.w3.org/2000/svg" width="70" height="70" fill="green" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 10.03 4.47 7.53a.75.75 0 0 0-1.06 1.06L6.97 12.1l6.6-6.6a.75.75 0 0 0-1.06-1.06L6.97 10.03z"/>
      </svg>
    </div>
    <h1 class="mb-3 text-success">Success!</h1>
    <p class="lead">Thank you for confirming.<br>
       Your delivery status is now <strong><?= $status ?></strong>.
    </p>
    <p class="text-muted">You may now close the tab</p>
    <button onclick="window.close();" class="btn btn-secondary mt-3">Close Tab</button>
  </div>

</body>
</html>
