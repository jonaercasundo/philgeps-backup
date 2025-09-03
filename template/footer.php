</div>
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

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
  <div class="spinner"></div>
</div>

<style>
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.8);
    display: none; /* hidden by default */
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }

  .spinner {
    border: 6px solid #f3f3f3;
    border-top: 6px solid #007bff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
</style>

<script src="assets/js/add.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/loading.js"></script>
<script src="assets/js/search.js"></script>
<script src="assets/js/filter.js"></script>

</body>
</html>
