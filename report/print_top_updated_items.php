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
        header('Content-Disposition: attachment; filename=top_updated_items_report_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Item Name', 'Update Count']);
        
        // Fetch data for CSV
        $topUpdatedItemsQuery = "
            SELECT 
                i.item_name,
                COUNT(*) AS update_count
            FROM inventory_history ih
            JOIN item i ON ih.item_id = i.item_id
            GROUP BY i.item_name
            ORDER BY update_count DESC
            LIMIT 5
        ";
        
        $stmt = $pdo->query($topUpdatedItemsQuery);
        $topUpdatedItemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // CSV Data Rows
        foreach ($topUpdatedItemsData as $row) {
            fputcsv($output, [
                $row['item_name'],
                $row['update_count']
            ]);
        }
        
        // Grand total
        $grandTotal = array_sum(array_column($topUpdatedItemsData, 'update_count'));
        fputcsv($output, []); // Empty row
        fputcsv($output, ['GRAND TOTAL', $grandTotal]);
        
        fclose($output);
        exit();
    }

    // Top 5 most updated items
    $topUpdatedItemsQuery = "
        SELECT 
            i.item_name,
            COUNT(*) AS update_count
        FROM inventory_history ih
        JOIN item i ON ih.item_id = i.item_id
        GROUP BY i.item_name
        ORDER BY update_count DESC
        LIMIT 5
    ";
    $stmt = $pdo->query($topUpdatedItemsQuery);
    $topUpdatedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>🏷️ Top Updated Items Report</h4>
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

<table id="topUpdatedItemsTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Rank</th>
            <th>Item Name</th>
            <th>Update Count</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $rank = 1;
        foreach ($topUpdatedItems as $row): 
        ?>
        <tr>
            <td class="text-center"><?= $rank++ ?></td>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td class="text-center"><?= number_format($row['update_count']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#topUpdatedItemsTable').DataTable({
            scrollY: "60vh",
            scrollCollapse: true,
            paging: false,
            responsive: true,
            searching: false,
            ordering: false
        });
    });
</script>

<script src="print-helper.js"></script>
<script>
function printInventoryReport() {
    printTable(
        'topUpdatedItemsTable', 
        'Top Updated Items Report', 
        ''
    );
}
</script>