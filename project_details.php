<?php
require "template/header.php";
$id = (int)($_GET['id'] ?? 0);
?>
<div class="container mt-4" id="projectContainer">
    <div class="text-center my-5" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Loading project details...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    fetch(`project_details_data.php?id=<?= $id ?>`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('projectContainer').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('projectContainer').innerHTML =
                '<div class="alert alert-danger">Failed to load project details.</div>';
            console.error(err);
        });
});
</script>

<?php require "template/footer.php"; ?>
