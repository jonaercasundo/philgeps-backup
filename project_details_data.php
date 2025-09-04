<?php
require "config/db.php";

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
function e($str) { return htmlspecialchars($str); }

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

$statusColors = [
    'Pending'   => 'bg-warning',
    'Delivered' => 'bg-info',
    'Accepted'  => 'bg-success'
];
?>

<h2>Project Details</h2>
<div class="card mb-4">
    <div class="card-body">
        <h4><?= e($project['project_name']) ?></h4>
        <p><strong>Reference No:</strong> <?= e($project['ref_no']) ?></p>
        <p><strong>Agency:</strong> <?= e($project['agency']) ?></p>
        <p><strong>Contract Amount:</strong> ₱<?= number_format($project['contract_amount'], 2) ?></p>
        <p><strong>Duration:</strong> <?= e($project['start_date']) ?> to <?= e($project['end_date']) ?></p>
        <p><strong>Status:</strong> <?= e($project['status']) ?></p>
    </div>
</div>

<!-- Deliveries -->
<h4 class="mb-3">Deliveries</h4>
<div class="row">
    <?php foreach ($statusColors as $status => $color):
        $filtered = array_filter($deliveries, fn($d) => $d['status'] === $status);
    ?>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center text-white <?= $color ?>">
                <h5 class="mb-0"><?= $status ?></h5>
                <span class="badge bg-dark"><?= count($filtered) ?></span>
            </div>
            <div class="card-body" style="max-height:400px; overflow-y:auto;">
                <?php if ($filtered): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($filtered as $d): ?>
                        <li class="list-group-item">
                            <strong>DR No:</strong> <?= e($d['dr_no']) ?><br>
                            <strong>Date:</strong> <?= e($d['delivery_date']) ?><br>
                            <strong>Content:</strong> <?= e($d['remarks']) ?>
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
<h4>Invoices</h4>
<table class="table table-bordered">
    <thead>
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
            <tr><td colspan="5">No invoices yet.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Documents -->
<h4>Documents</h4>
<ul>
    <?php if ($documents): ?>
        <?php foreach ($documents as $doc): ?>
            <li><?= e($doc['doc_type']) ?> -
                <a href="<?= e($doc['file_path']) ?>" target="_blank"><?= e($doc['file_name']) ?></a>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li>No documents uploaded.</li>
    <?php endif; ?>
</ul>
