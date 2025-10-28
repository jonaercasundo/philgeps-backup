<?php session_start();
require "config/db.php";

// if (!isset($_SESSION['user_id']) || 
//     !isset($_SESSION['username']) || 
//     !isset($_SESSION['name']) || 
//     !isset($_SESSION['role'])) {
    
//     header("Location: index.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - MMC Project Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">

<?php
// Toast message
if (!empty($_GET['toast']) && !empty($_GET['type'])):
    $toast = htmlspecialchars($_GET['toast'], ENT_QUOTES, 'UTF-8');
    $type = htmlspecialchars($_GET['type'], ENT_QUOTES, 'UTF-8');
?>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="myToast" class="toast text-bg-<?= $type ?>" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto">PhilGEPS Tracker</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body"><?= $toast ?></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const toastEl = document.getElementById('myToast');
  if (toastEl) new bootstrap.Toast(toastEl).show();
});
</script>
<?php endif; ?>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card shadow-lg">
        <div class="card-body p-4">
          <h4 class="text-center mb-3">MMC Project Tracker</h4>
          <form method="POST" action="script/authenticate.php" autocomplete="off">
            <div class="mb-3">
              <label for="username" class="form-label">Email</label>
              <input type="email" id="username" name="username" class="form-control" placeholder="Enter email" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
