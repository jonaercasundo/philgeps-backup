<?php 
    require "../template/header.php"; 
    require "../script/role_auth.php";
    require "../config/db.php";

    $allowed_roles = ['Super Admin', 'Office Admin', 'Warehouse Admin'];
    redirectIfNotAuthorized($allowed_roles, '../index.php');

    $selectedProject = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $selectedProjectName = "";

    // Handle CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=delivery_status_report_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Status', 'Total Deliveries', 'Percentage']);
        
        // Fetch data for CSV
        $deliveryStatusQuery = "
            SELECT 
                COUNT(*) AS total,
                CASE d.status
                    WHEN 'pending'   THEN 'Pending'
                    WHEN 'accepted'  THEN 'Accepted'
                    WHEN 'delivered' THEN 'Delivered'
                    WHEN 'cancelled' THEN 'Cancelled'
                    ELSE d.status
                END AS status
            FROM deliveries d
            " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
            GROUP BY 
                CASE d.status
                    WHEN 'pending'   THEN 'Pending'
                    WHEN 'accepted'  THEN 'Accepted'
                    WHEN 'delivered' THEN 'Delivered'
                    WHEN 'cancelled' THEN 'Cancelled'
                    ELSE d.status
                END
            ORDER BY total DESC
        ";
        
        $stmt = $pdo->query($deliveryStatusQuery);
        $deliveryStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total for percentage
        $grandTotal = array_sum(array_column($deliveryStatusData, 'total'));
        
        // CSV Data Rows
        foreach ($deliveryStatusData as $row) {
            $percentage = $grandTotal > 0 ? number_format(($row['total'] / $grandTotal) * 100, 2) : 0;
            fputcsv($output, [
                $row['status'],
                $row['total'],
                $percentage . '%'
            ]);
        }
        
        // Total Row
        fputcsv($output, ['Total', $grandTotal, '100%']);
        
        fclose($output);
        exit();
    }


    if ($selectedProject > 0) {
        $stmt = $pdo->prepare("SELECT project_name FROM project WHERE project_id = ?");
        $stmt->execute([$selectedProject]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $selectedProjectName = $result['project_name'] ?? "";
    }

    // Fetch data directly
    $deliveryStatusQuery = "
        SELECT 
            COUNT(*) AS total,
            CASE d.status
                WHEN 'pending'   THEN 'Pending'
                WHEN 'accepted'  THEN 'Accepted'
                WHEN 'delivered' THEN 'Delivered'
                WHEN 'cancelled' THEN 'Cancelled'
                ELSE d.status
            END AS status
        FROM deliveries d
        " . ($selectedProject > 0 ? "WHERE d.project_id = $selectedProject" : "") . "
        GROUP BY 
            CASE d.status
                WHEN 'pending'   THEN 'Pending'
                WHEN 'accepted'  THEN 'Accepted'
                WHEN 'delivered' THEN 'Delivered'
                WHEN 'cancelled' THEN 'Cancelled'
                ELSE d.status
            END
        ORDER BY total DESC
    ";
    
    $stmt = $pdo->query($deliveryStatusQuery);
    $deliveryStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total for percentage
    $grandTotal = array_sum(array_column($deliveryStatusData, 'total'));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📊 Delivery Status Report <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h4>
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
        <button class="btn btn-primary" onclick="printDeliveryReport()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<table id="deliveryStatusTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Status</th>
            <th>Total Deliveries</th>
            <th>Percentage</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($deliveryStatusData as $row): ?>
        <tr>
            <td class="text-center"><?= htmlspecialchars($row['status']) ?></td>
            <td class="text-center"><?= number_format($row['total']) ?></td>
            <td class="text-center">
                <?= $grandTotal > 0 ? number_format(($row['total'] / $grandTotal) * 100, 2) : 0 ?>%
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="table-secondary">
        <tr>
            <th class="text-center">Total</th>
            <th class="text-center"><?= number_format($grandTotal) ?></th>
            <th class="text-center">100%</th>
        </tr>
    </tfoot>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#deliveryStatusTable').DataTable({
            scrollY: "60vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[1, 'desc']]
        });
    });
</script>

<script src="print-helper.js"></script>
<script>
// For your current page
function printDeliveryReport() {
    printTable(
        'deliveryStatusTable', 
        'Delivery Status Report', 
        '<?= $selectedProject > 0 ? htmlspecialchars($selectedProjectName) : "" ?>'
    );
}
</script>