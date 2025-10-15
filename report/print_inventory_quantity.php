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
        header('Content-Disposition: attachment; filename=inventory_quantity_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Warehouse Name', 'Total Items', 'Total Quantity']);
        
        // Fetch data for CSV (same query as above)
        $inventoryQuantityQuery = "
            SELECT 
                w.warehouse_name,
                COUNT(DISTINCT i.item_id) as total_items,
                SUM(inv.qty) as total_quantity
            FROM inventory inv
            JOIN item i ON inv.item_id = i.item_id
            JOIN warehouse w ON inv.warehouse_id = w.warehouse_id
            WHERE inv.inventory_status = 'Approved'
                AND inv.qty > 0
                " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
            GROUP BY w.warehouse_id, w.warehouse_name
            ORDER BY total_quantity DESC
        ";
        
        $stmt = $pdo->query($inventoryQuantityQuery);
        $inventoryQuantityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // CSV Data Rows
        foreach ($inventoryQuantityData as $row) {
            fputcsv($output, [
                $row['warehouse_name'],
                $row['total_items'],
                $row['total_quantity']
            ]);
        }
        
        // Calculate totals
        $totalItems = array_sum(array_column($inventoryQuantityData, 'total_items'));
        $totalQuantity = array_sum(array_column($inventoryQuantityData, 'total_quantity'));
        
        // Add totals row
        fputcsv($output, []); // Empty row
        fputcsv($output, ['GRAND TOTAL', $totalItems, $totalQuantity]);
        
        fclose($output);
        exit();
    }

    // Fetch inventory quantity summary
    $inventoryQuantityQuery = "
        SELECT 
            w.warehouse_name,
            COUNT(DISTINCT i.item_id) as total_items,
            SUM(inv.qty) as total_quantity
        FROM inventory inv
        JOIN item i ON inv.item_id = i.item_id
        JOIN warehouse w ON inv.warehouse_id = w.warehouse_id
        WHERE inv.inventory_status = 'Approved'
            AND inv.qty > 0
            " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
        GROUP BY w.warehouse_id, w.warehouse_name
        ORDER BY total_quantity DESC
    ";
    
    $stmt = $pdo->query($inventoryQuantityQuery);
    $inventoryQuantityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalItems = array_sum(array_column($inventoryQuantityData, 'total_items'));
    $totalQuantity = array_sum(array_column($inventoryQuantityData, 'total_quantity'));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📦 Inventory Quantity Report <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h4>
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

<table id="inventoryQuantityTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Warehouse Name</th>
            <th>Total Items</th>
            <th>Total Quantity</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inventoryQuantityData as $row): ?>
        <tr>
            <td class="text-start"><?= htmlspecialchars($row['warehouse_name']) ?></td>
            <td class="text-center"><?= number_format($row['total_items']) ?></td>
            <td class="text-end"><?= number_format($row['total_quantity']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="table-secondary">
        <tr>
            <th class="text-start">Total</th>
            <th class="text-center"><?= number_format($totalItems) ?></th>
            <th class="text-end"><?= number_format($totalQuantity) ?></th>
        </tr>
    </tfoot>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#inventoryQuantityTable').DataTable({
            scrollY: "60vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[2, 'desc']]
        });
    });
</script>