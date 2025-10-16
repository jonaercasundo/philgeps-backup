<?php 
    require "reports_header.php"; 
    require "../script/role_auth.php";
    require "../config/db.php";

    $allowed_roles = ['Super Admin', 'Office Admin', 'Warehouse Admin'];
    redirectIfNotAuthorized($allowed_roles, '../index.php');

    $selectedProject = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $selectedDate = isset($_GET['selectedDate']) ? $_GET['selectedDate'] : date('Y-m-d');
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
        header('Content-Disposition: attachment; filename=inventory_by_warehouse_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Warehouse', 'Item Name', 'Unit', 'Quantity', 'As of Date: ' . $selectedDate]);
        
        // Fetch data for CSV
        $inventoryByWarehouseQuery = "
            SELECT 
                w.warehouse_name,
                i.item_name,
                i.unit,
                COALESCE(
                    (SELECT ih.new_qty
                    FROM inventory_history ih
                    WHERE ih.item_id = i.item_id 
                      AND ih.warehouse_id = w.warehouse_id 
                      AND DATE(ih.changed_at) <= :selectedDate
                    ORDER BY ih.changed_at DESC 
                    LIMIT 1),
                    inv.qty
                ) as qty
            FROM inventory inv
            JOIN item i ON inv.item_id = i.item_id
            JOIN warehouse w ON inv.warehouse_id = w.warehouse_id
            WHERE inv.inventory_status = 'Approved'
                " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
            HAVING qty > 0
            ORDER BY w.warehouse_name, i.item_name
        ";

        $stmt = $pdo->prepare($inventoryByWarehouseQuery);
        $stmt->execute(['selectedDate' => $selectedDate]);
        $inventoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // CSV Data Rows
        foreach ($inventoryData as $row) {
            fputcsv($output, [
                $row['warehouse_name'],
                $row['item_name'],
                $row['unit'],
                $row['qty']
            ]);
        }
        
        // Calculate totals
        $warehouseTotals = [];
        $grandTotal = 0;
        
        foreach ($inventoryData as $row) {
            $warehouseTotals[$row['warehouse_name']] = ($warehouseTotals[$row['warehouse_name']] ?? 0) + $row['qty'];
            $grandTotal += $row['qty'];
        }
        
        // Add warehouse totals
        fputcsv($output, []); // Empty row
        fputcsv($output, ['Warehouse Totals:', '', '', '']);
        foreach ($warehouseTotals as $warehouse => $total) {
            fputcsv($output, [$warehouse, 'TOTAL', '', $total]);
        }
        
        // Grand total
        fputcsv($output, []); // Empty row
        fputcsv($output, ['GRAND TOTAL', '', '', $grandTotal]);
        
        // Report metadata
        // fputcsv($output, []);
        // fputcsv($output, ['Report Details:', '', '', '']);
        // fputcsv($output, ['Generated on:', date('Y-m-d H:i:s'), '', '']);
        // fputcsv($output, ['Project:', $selectedProject > 0 ? $selectedProjectName : 'All Projects', '', '']);
        // fputcsv($output, ['Data as of:', $selectedDate, '', '']);
        
        fclose($output);
        exit();
    }

    // Fetch inventory by warehouse data
    $inventoryByWarehouseQuery = "
        SELECT 
            w.warehouse_name,
            i.item_name,
            i.unit,
            COALESCE(
                (SELECT ih.new_qty
                FROM inventory_history ih
                WHERE ih.item_id = i.item_id 
                  AND ih.warehouse_id = w.warehouse_id 
                  AND DATE(ih.changed_at) <= :selectedDate
                ORDER BY ih.changed_at DESC 
                LIMIT 1),
                inv.qty
            ) as qty
        FROM inventory inv
        JOIN item i ON inv.item_id = i.item_id
        JOIN warehouse w ON inv.warehouse_id = w.warehouse_id
        WHERE inv.inventory_status = 'Approved'
            " . ($selectedProject > 0 ? "AND i.project_id = $selectedProject" : "") . "
        HAVING qty > 0
        ORDER BY w.warehouse_name, i.item_name
    ";

    $stmt = $pdo->prepare($inventoryByWarehouseQuery);
    $stmt->execute(['selectedDate' => $selectedDate]);
    $inventoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📦 Inventory by Warehouse Report <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h4>
    <a href="../dashboard.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<div class="row mb-3 align-items-end">
    <div class="col-md-4">
        <label for="dateFilter" class="form-label">Filter by Date</label>
        <form method="GET" class="d-flex gap-2">
            <?php if($selectedProject > 0): ?>
                <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
            <?php endif; ?>
            <input type="date" class="form-control" name="selectedDate" value="<?= htmlspecialchars($selectedDate) ?>">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel"></i> Apply
            </button>
        </form>
    </div>
    <div class="col-md-12 d-flex justify-content-end gap-2">
        <!-- CSV Export Button -->
        <a href="?export=csv<?= $selectedProject > 0 ? '&project_id=' . $selectedProject : '' ?>&selectedDate=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <button class="btn btn-primary" onclick="printDeliveryReport()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>

</div>

<?php if($selectedDate !== date('Y-m-d')): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Showing inventory as of: <strong><?= date('F d, Y', strtotime($selectedDate)) ?></strong>
</div>
<?php endif; ?>

<table id="inventoryWarehouseTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Warehouse Name</th>
            <th>Item Name</th>
            <th>Unit</th>
            <th>Quantity</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inventoryData as $row): ?>
        <tr>
            <td class="text-start"><?= htmlspecialchars($row['warehouse_name']) ?></td>
            <td class="text-start"><?= htmlspecialchars($row['item_name']) ?></td>
            <td class="text-center"><?= htmlspecialchars($row['unit']) ?></td>
            <td class="text-end"><?= number_format($row['qty']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#inventoryWarehouseTable').DataTable({
            scrollY: "60vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[0, 'asc'], [1, 'asc']]
        });
    });
</script>

<script src="print-helper.js"></script>
<script>
// For your current page
function printDeliveryReport() {
    printTable(
        'inventoryWarehouseTable', 
        'Inventory by Warehouse Report', 
        '<?= $selectedProject > 0 ? htmlspecialchars($selectedProjectName) : "" ?>'
    );
}
</script>