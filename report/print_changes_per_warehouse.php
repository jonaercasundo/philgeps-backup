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
        header('Content-Disposition: attachment; filename=changes_per_warehouse_report_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Warehouse Name', 'Total Changes']);
        
        // Fetch data for CSV
        $changesPerWarehouseQuery = "
            SELECT 
                w.warehouse_name,
                COUNT(*) AS total_changes
            FROM inventory_history ih
            JOIN warehouse w ON ih.warehouse_id = w.warehouse_id
            GROUP BY w.warehouse_name
            ORDER BY total_changes DESC
        ";
        
        $stmt = $pdo->query($changesPerWarehouseQuery);
        $changesPerWarehouseData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // CSV Data Rows
        foreach ($changesPerWarehouseData as $row) {
            fputcsv($output, [
                $row['warehouse_name'],
                $row['total_changes']
            ]);
        }
        
        // Grand total
        $grandTotal = array_sum(array_column($changesPerWarehouseData, 'total_changes'));
        fputcsv($output, []); // Empty row
        fputcsv($output, ['GRAND TOTAL', $grandTotal]);
        
        fclose($output);
        exit();
    }

    // Changes per warehouse
    $changesPerWarehouseQuery = "
        SELECT 
            w.warehouse_name,
            COUNT(*) AS total_changes
        FROM inventory_history ih
        JOIN warehouse w ON ih.warehouse_id = w.warehouse_id
        GROUP BY w.warehouse_name
        ORDER BY total_changes DESC
    ";
    $stmt = $pdo->query($changesPerWarehouseQuery);
    $changesPerWarehouse = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>🏭 Changes per Warehouse Report</h4>
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

<table id="changesPerWarehouseTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Warehouse Name</th>
            <th>Total Changes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($changesPerWarehouse as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['warehouse_name']) ?></td>
            <td class="text-center"><?= number_format($row['total_changes']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#changesPerWarehouseTable').DataTable({
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
function printInventoryReport() {
    printTable(
        'changesPerWarehouseTable', 
        'Changes per Warehouse Report', 
        ''
    );
}
</script>