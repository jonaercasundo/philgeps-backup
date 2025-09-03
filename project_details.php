<?php
require "template/header.php";
require "config/db.php"; // your PDO connection

// Get project ID from URL
$id = (int) $_GET['id'];

// Fetch Project Details
$stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo "<div class='alert alert-danger'>Project not found.</div>";
    require "template/footer.php";
    exit;
}

// Fetch Deliveries
$stmt = $pdo->prepare("SELECT * FROM deliveries WHERE project_id = ?");
$stmt->execute([$id]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Invoices
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE project_id = ?");
$stmt->execute([$id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE project_id = ?");
$stmt->execute([$id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mt-4">
    <h2>Project Details</h2>
    <div class="card mb-4">
        <div class="card-body">
            <h4><?= htmlspecialchars($project['project_name']) ?></h4>
            <p><strong>Reference No:</strong> <?= htmlspecialchars($project['ref_no']) ?></p>
            <p><strong>Agency:</strong> <?= htmlspecialchars($project['agency']) ?></p>
            <p><strong>Contract Amount:</strong> ₱<?= number_format($project['contract_amount'], 2) ?></p>
            <p><strong>Duration:</strong> <?= $project['start_date'] ?> to <?= $project['end_date'] ?></p>
            <p><strong>Status:</strong> <?= $project['status'] ?></p>
        </div>
    </div>

    <!-- Deliveries -->
    <h4>Deliveries</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>DR No</th>
                <th>Date</th>
                <th>Status</th>
                <th>Content</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($deliveries): ?>
                <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['dr_no']) ?></td>
                    <td><?= $d['delivery_date'] ?></td>
                    <td><?= $d['status'] ?></td>
                    <td><?= htmlspecialchars($d['remarks']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No deliveries yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

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
                    <td><?= htmlspecialchars($i['invoice_no']) ?></td>
                    <td><?= $i['invoice_date'] ?></td>
                    <td>₱<?= number_format($i['amount'], 2) ?></td>
                    <td><?= $i['status'] ?></td>
                    <td><?= $i['payment_date'] ?: '-' ?></td>
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
                <li>
                    <?= $doc['doc_type'] ?> - 
                    <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank">
                        <?= htmlspecialchars($doc['file_name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>No documents uploaded.</li>
        <?php endif; ?>
    </ul>
</div>
<script src="assets/js/project_details.js">
<?php require "template/footer.php"; ?>
