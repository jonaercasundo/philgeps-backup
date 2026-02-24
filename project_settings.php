<?php
require "template/header.php";
require "config/db.php"; // your PDO connection
require "script/role_auth.php";
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
?>

  <div class="container mt-4">
    <h2 class="mb-3">Project Settings</h2>
  </div>

<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>