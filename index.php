<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - PhilGEPS Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height:100vh;">
<!-- TOAST -->
 <?php
 
if (isset($_GET['toast']) && isset($_GET['type'])){
    $toast = $_GET['toast'];
    $type = $_GET['type'];
 echo "<div class='position-fixed bottom-0 end-0 p-3' style='z-index: 9999;'>
  <div id='myToast' class='toast text-bg-$type' role='alert' aria-live='assertive' aria-atomic='true'>
    <div class='toast-header'>
      <strong class='me-auto'>PhilGEPS Tracker</strong>
      <small class='text-muted'></small>
      <button type='button' class='btn-close' data-bs-dismiss='toast' aria-label='Close'></button>
    </div>
    <div class='toast-body' id='toastMessage'>
      $toast
    </div>
  </div>
</div>

<script>
  window.addEventListener('DOMContentLoaded', (event) => {
    const toastEl = document.getElementById('myToast');
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
  });
</script>
";
 };
 ?>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow-lg">
          <div class="card-body p-4">
            <h4 class="text-center mb-3">PhilGEPS Tracker</h4>
            <form method="POST" action="script/authenticate.php">
              <div class="mb-3">
                <label>Email</label>
                <input type="email" name="username" class="form-control" placeholder="Enter email">
              </div>
              <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password">
              </div>
              <button class="btn btn-primary w-100">Login</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
