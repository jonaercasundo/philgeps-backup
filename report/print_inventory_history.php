<?php 
    require "reports_header.php"; 
    require "../script/role_auth.php";
    require "../config/db.php";

    $allowed_roles = ['Super Admin', 'Office Admin', 'Warehouse Admin'];
    redirectIfNotAuthorized($allowed_roles, '../index.php');

    // handle CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inventory_history_report_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Date', 'Total Changes']);
        
        // Fetch data for CSV
        $inventoryHistoryQuery = "
            SELECT 
                DATE(changed_at) AS change_date,
                COUNT(*) AS total_changes
            FROM inventory_history
            GROUP BY DATE(changed_at)
            ORDER BY change_date DESC
            LIMIT 30
        ";
        
        $stmt = $pdo->query($inventoryHistoryQuery);
        $inventoryHistoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // CSV Data Rows
        foreach ($inventoryHistoryData as $row) {
            fputcsv($output, [
                $row['change_date'],
                $row['total_changes']
            ]);
        }
        
        // Grand total
        $grandTotal = array_sum(array_column($inventoryHistoryData, 'total_changes'));
        fputcsv($output, []); // Empty row
        fputcsv($output, ['GRAND TOTAL', $grandTotal]);
        
        fclose($output);
        exit();
    }

    // Inventory History Trends
    $inventoryHistoryQuery = "
        SELECT 
            DATE(changed_at) AS change_date,
            COUNT(*) AS total_changes
        FROM inventory_history
        GROUP BY DATE(changed_at)
        ORDER BY change_date ASC
        LIMIT 30
    ";
    $stmt = $pdo->query($inventoryHistoryQuery);
    $inventoryHistoryTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📅 Inventory History Report</h4>
    <a href="../dashboard.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-12 d-flex justify-content-end gap-2">
        <!-- CSV Export Button -->
        <a href="?export=csv" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <button class="btn btn-primary" onclick="printInventoryReport()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<table id="inventoryHistoryTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Date</th>
            <th>Total Changes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inventoryHistoryTrend as $row): ?>
        <tr>
            <td class="text-center">
                <?php 
                    $date = new DateTime($row['change_date']);
                    echo $date->format('F d, Y');
                ?>
            </td>
            <td class="text-center"><?= number_format($row['total_changes']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#inventoryHistoryTable').DataTable({
            scrollY: "60vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[0, 'desc']]
        });
    });
</script>

<script src="print-helper.js"></script>
<script>
function printInventoryReport() {
    printTable(
        'inventoryHistoryTable', 
        'Inventory History Report', 
        ''
    );
}
</script>