</div> <!-- container or page wrapper -->

<!-- TOAST -->
<?php
if (!empty($_GET['toast']) && !empty($_GET['type'])):
    $toast = htmlspecialchars($_GET['toast']);
    $type  = htmlspecialchars($_GET['type']);
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

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
  <div class="spinner"></div>
</div>

<style>
.loading-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255,255,255,0.8);
    display: none;
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

<!-- JS -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.1/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.1/js/fixedColumns.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>
<!--script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script-->
<!-- <script src="assets/bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script> -->

<script src="assets/js/add.js"></script>
<script src="assets/js/search.js"></script>
<script src="assets/js/filter.js"></script>
<script>
const loadingOverlay = document.getElementById('loadingOverlay');

function showLoading() {
    if (loadingOverlay) loadingOverlay.style.display = 'flex';
}

function hideLoading() {
    if (loadingOverlay) loadingOverlay.style.display = 'none';
}

// Automatically hide overlay on page load
document.addEventListener('DOMContentLoaded', () => hideLoading());
</script>

</body>
</html>
