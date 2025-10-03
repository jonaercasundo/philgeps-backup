<?php
require "config/db.php";

// Get project ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo "<div class='alert alert-danger'>Invalid project ID.</div>";
    exit;
}

// Helper functions
function fetchAllByProject(PDO $pdo, string $table, int $projectId) {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE project_id = ?");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function e($str) {
    return htmlspecialchars($str);
}

// Fetch project
$stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    echo "<div class='alert alert-danger'>Project not found.</div>";
    exit;
}

// Fetch related data
$deliveries = fetchAllByProject($pdo, 'deliveries', $id);
$invoices   = fetchAllByProject($pdo, 'invoices', $id);
$documents  = fetchAllByProject($pdo, 'documents', $id);

// Progress calculation
$totalDeliveries = count($deliveries);
$deliveredDeliveries = count(array_filter($deliveries, fn($d) => $d['status'] === 'delivered'));
$progressPercent = $totalDeliveries > 0 ? round(($deliveredDeliveries / $totalDeliveries) * 100) : 0;

// Document checklist
$requiredDocs = [
    'BAC Resolution', 
    'Notice of Award', 
    'Notice to Proceed', 
    'Delivery Receipt', 
    'Inspection Report'
];
$uploadedTypes = array_column($documents, 'doc_type');

// Status color map
$statusColors = [
    'pending'   => 'bg-warning',
    'delivered' => 'bg-info',
    'accepted'  => 'bg-success'
];
?>

<!-- MAIN HTML START -->
<div class="container my-4">

    <h2 class="mb-4">Project Overview</h2>

    <!-- Project Details -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title"><?= e($project['project_name']) ?></h4>
            <p><strong>Reference No:</strong> <?= e($project['ref_no']) ?></p>
            <p><strong>Agency:</strong> <?= e($project['agency']) ?></p>
            <p><strong>Contract Amount:</strong> ₱<?= number_format($project['contract_amount'], 2) ?></p>
            <p><strong>Duration:</strong> <?= e($project['start_date']) ?> to <?= e($project['end_date']) ?></p>
            <p><strong>Status:</strong> <?= e($project['status']) ?></p>

            <!-- Progress Bar -->
            <div class="mt-3">
                <label><strong>Delivery Progress:</strong> <?= $progressPercent ?>%</label>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progressPercent ?>%;" aria-valuenow="<?= $progressPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deliveries by Status -->
    <h4 class="mb-3">Deliveries</h4>
    <div class="row">
        <?php foreach ($statusColors as $status => $color): ?>
            <?php 
                $filtered = array_filter($deliveries, fn($d) => $d['status'] === $status); 
            ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header text-white <?= $color ?> d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= $status ?></h5>
                        <span class="badge bg-dark"><?= count($filtered) ?></span>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php if ($filtered): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($filtered as $d): ?>
                                    <li class="list-group-item">
                                        <strong>DR No:</strong> <?= e($d['dr_no']) ?><br>
                                        <strong>Date:</strong> <?= e($d['delivery_date']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0">No <?= strtolower($status) ?> deliveries.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Invoices -->
    <h4 class="mt-5">Invoices</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Invoice No</th>
                    <th>Date of Statement</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($invoices): ?>
                    <?php foreach ($invoices as $i): ?>
                        <tr>
                            <td><?= e($i['invoice_no']) ?></td>
                            <td><?= e($i['invoice_date']) ?></td>
                            <td>₱<?= number_format($i['amount'], 2) ?></td>
                            <td><?= e($i['status']) ?></td>
                            <td><?= $i['payment_date'] ? e($i['payment_date']) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted">No invoices yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Document Checklist -->
    <h4 class="mt-5">Document Checklist</h4>
    <ul class="list-group mb-4">
        <?php foreach ($requiredDocs as $doc): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= $doc ?>
                <?php if (in_array($doc, $uploadedTypes)): ?>
                    <span class="badge bg-success">Uploaded</span>
                <?php else: ?>
                    <button class="badge bg-danger" data-bs-toggle="modal" data-bs-target="#addModal" onclick="uploadFile('<?= $doc ?>')">Missing</button>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

<!-- Add File Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5 id="addTitle">Error reload the page</h5></div>
      <div class="modal-body">
        <form enctype="multipart/form-data" id="addForm">
          <input type="hidden" name="project_id" value="<?=$_GET['id']?>" class="form-control">
          <input type="hidden" id ="document_type" name="doc_type" class="form-control">
          <div class="mb-3"><label>Upload File</label><input require id="editid" name="file" type="file" class="form-control"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" onclick="addForm('project_details.php','add_document.php')">Save</button>
      </div>
    </div>
  </div>
</div>

    <!-- Uploaded Documents -->
    <h4>All Uploaded Documents</h4>
    <ul>
        <?php if ($documents): ?>
            <?php foreach ($documents as $doc): ?>
                <li>
                    <?= e($doc['doc_type']) ?> —
                    <a href="philgeps/<?= e($doc['file_path']) ?>" target="_blank"><?= e($doc['file_name']) ?></a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="text-muted">No documents uploaded.</li>
        <?php endif; ?>
    </ul>

</div>