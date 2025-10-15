<?php 
    require "../template/header.php"; 
    require "../script/role_auth.php";
    require "../config/db.php";

    $allowed_roles = ['Super Admin', 'Office Admin', 'Warehouse Admin'];
    redirectIfNotAuthorized($allowed_roles, '../index.php');

    $selectedProject = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $selectedProjectName = "";

    if ($selectedProject > 0) {
        $stmt = $pdo->prepare("SELECT project_name FROM project WHERE project_id = ?");
        $stmt->execute([$selectedProject]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $selectedProjectName = $result['project_name'] ?? "";
    }

    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=accepted_by_lot_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Lot Name', 'Total Deliveries', 'Accepted', 'Pending', 'Delivered', 'Acceptance Rate (%)']);
        
        // Fetch data for CSV (same query as above)
        $progressPerLotQuery = "
            SELECT 
                l.lot_name,
                COUNT(*) AS total,
                SUM(CASE WHEN d.status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN d.status = 'accepted' THEN 1 ELSE 0 END) AS accepted
            FROM deliveries d
            LEFT JOIN keystage k ON d.keystage_id = k.keystage_id
            JOIN lot l ON l.lot_id = COALESCE(d.lot_id, k.lot_id)
            " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
            GROUP BY l.lot_name
            ORDER BY l.lot_name
        ";

        $stmt = $pdo->query($progressPerLotQuery);
        $progressPerLotData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // CSV Data Rows
        foreach ($progressPerLotData as $row) {
            $acceptanceRate = $row['total'] > 0 ? number_format(($row['accepted'] / $row['total']) * 100, 2) : 0;
            
            fputcsv($output, [
                $row['lot_name'],
                $row['total'],
                $row['accepted'],
                $row['pending'],
                $row['delivered'],
                $acceptanceRate
            ]);
        }
        
        // Calculate totals
        $totalDeliveries = array_sum(array_column($progressPerLotData, 'total'));
        $totalAccepted = array_sum(array_column($progressPerLotData, 'accepted'));
        $totalPending = array_sum(array_column($progressPerLotData, 'pending'));
        $totalDelivered = array_sum(array_column($progressPerLotData, 'delivered'));
        $totalAcceptanceRate = $totalDeliveries > 0 ? number_format(($totalAccepted / $totalDeliveries) * 100, 2) : 0;
        
        // Add totals row
        fputcsv($output, []); // Empty row
        fputcsv($output, ['TOTAL', $totalDeliveries, $totalAccepted, $totalPending, $totalDelivered, $totalAcceptanceRate]);
        
        fclose($output);
        exit();
    }

    // Fetch progress per lot
    $progressPerLotQuery = "
        SELECT 
            l.lot_name,
            COUNT(*) AS total,
            SUM(CASE WHEN d.status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN d.status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN d.status = 'accepted' THEN 1 ELSE 0 END) AS accepted
        FROM deliveries d
        LEFT JOIN keystage k ON d.keystage_id = k.keystage_id
        JOIN lot l ON l.lot_id = COALESCE(d.lot_id, k.lot_id)
        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY l.lot_name
        ORDER BY l.lot_name
    ";

    $stmt = $pdo->query($progressPerLotQuery);
    $progressPerLotData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalDeliveries = array_sum(array_column($progressPerLotData, 'total'));
    $totalAccepted = array_sum(array_column($progressPerLotData, 'accepted'));
    $totalPending = array_sum(array_column($progressPerLotData, 'pending'));
    $totalDelivered = array_sum(array_column($progressPerLotData, 'delivered'));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>✅ Accepted by Lot Report <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h4>
    <a href="../dashboard.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-12 d-flex justify-content-end gap-2">
        <!-- CSV Export Button -->
        <a href="?export=csv<?= $selectedProject > 0 ? '&project_id=' . $selectedProject : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<table id="acceptedLotTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Lot Name</th>
            <th>Total Deliveries</th>
            <th>Accepted</th>
            <th>Pending</th>
            <th>Delivered</th>
            <th>Acceptance Rate (%)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($progressPerLotData as $row): ?>
        <tr>
            <td class="text-start"><?= htmlspecialchars($row['lot_name']) ?></td>
            <td class="text-center"><?= number_format($row['total']) ?></td>
            <td class="text-center"><?= number_format($row['accepted']) ?></td>
            <td class="text-center"><?= number_format($row['pending']) ?></td>
            <td class="text-center"><?= number_format($row['delivered']) ?></td>
            <td class="text-center">
                <?= $row['total'] > 0 ? number_format(($row['accepted'] / $row['total']) * 100, 2) : 0 ?>%
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="table-secondary">
        <tr>
            <th class="text-start">Total</th>
            <th class="text-center"><?= number_format($totalDeliveries) ?></th>
            <th class="text-center"><?= number_format($totalAccepted) ?></th>
            <th class="text-center"><?= number_format($totalPending) ?></th>
            <th class="text-center"><?= number_format($totalDelivered) ?></th>
            <th class="text-center">
                <?= $totalDeliveries > 0 ? number_format(($totalAccepted / $totalDeliveries) * 100, 2) : 0 ?>%
            </th>
        </tr>
    </tfoot>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#acceptedLotTable').DataTable({
            scrollY: "60vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[5, 'desc']]
        });
    });
</script>