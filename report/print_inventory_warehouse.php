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
        header('Content-Disposition: attachment; filename=inventory_report_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // SECTION 1: Inventory Quantity Summary
        fputcsv($output, ['INVENTORY QUANTITY SUMMARY']);
        fputcsv($output, []);
        fputcsv($output, ['Warehouse Name', 'Total Items', 'Total Quantity']);
        
        // Fetch inventory quantity data
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
        
        foreach ($inventoryQuantityData as $row) {
            fputcsv($output, [
                $row['warehouse_name'],
                $row['total_items'],
                $row['total_quantity']
            ]);
        }
        
        $totalItems = array_sum(array_column($inventoryQuantityData, 'total_items'));
        $totalQuantity = array_sum(array_column($inventoryQuantityData, 'total_quantity'));
        
        fputcsv($output, []);
        fputcsv($output, ['TOTAL', $totalItems, $totalQuantity]);
        
        // SECTION 2: Inventory by Warehouse Details
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, ['INVENTORY BY WAREHOUSE DETAILS']);
        fputcsv($output, ['As of Date: ' . $selectedDate]);
        fputcsv($output, []);
        fputcsv($output, ['Warehouse', 'Item Name', 'Unit', 'Quantity']);
        
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
        $inventoryByWarehouseData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($inventoryByWarehouseData as $row) {
            fputcsv($output, [
                $row['warehouse_name'],
                $row['item_name'],
                $row['unit'],
                $row['qty']
            ]);
        }
        
        fclose($output);
        exit();
    }

    // Fetch Inventory Quantity Data
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

    $totalItems = array_sum(array_column($inventoryQuantityData, 'total_items'));
    $totalQuantity = array_sum(array_column($inventoryQuantityData, 'total_quantity'));

    // Fetch Inventory by Warehouse Data
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
    $inventoryByWarehouseData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📦 Inventory Report <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h4>
    <a href="../dashboard.php<?= $selectedProject > 0 ? '?project_id=' . $selectedProject : '' ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<div class="row my-3 align-items-end">
    <div class="col-md-12 d-flex justify-content-end gap-2">
        <a href="?export=csv<?= $selectedProject > 0 ? '&project_id=' . $selectedProject : '' ?>&selectedDate=<?= htmlspecialchars($selectedDate) ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <button class="btn btn-primary" onclick="printCombinedReport()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<!--Tables -->
<div id="InventoryReport">
    <!-- Inventory Quantity Summary Table -->
    <h5 class="mt-4 mb-3">Inventory Quantity Summary</h5>
    <table id="inventoryQuantityTable" class="table table-bordered shadow-sm mb-5">
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

    <hr>
    <!-- Inventory by Warehouse Details Table -->
    <div class="row my-3">
        <div class="col-md-6 col-sm-12">
            <h5 class="mb-0">Inventory by Warehouse Details</h5>
        </div>
        <div class="col-md-6 col-sm-12">
            <form method="GET" class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
            <?php if($selectedProject > 0): ?>
                <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
            <?php endif; ?>

            <label for="dateFilter" class="form-label mb-0">
                <strong>Filter by Date:</strong>
            </label>

            <input 
                type="date" 
                class="form-control form-control-sm" 
                id="dateFilter" 
                name="selectedDate" 
                value="<?= htmlspecialchars($selectedDate) ?>" 
                style="max-width: 200px;"
            >

            <button type="submit" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                <i class="bi bi-funnel"></i> Apply
            </button>
            </form>
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
            <?php foreach ($inventoryByWarehouseData as $row): ?>
            <tr>
                <td class="text-start"><?= htmlspecialchars($row['warehouse_name']) ?></td>
                <td class="text-start"><?= htmlspecialchars($row['item_name']) ?></td>
                <td class="text-center"><?= htmlspecialchars($row['unit']) ?></td>
                <td class="text-end"><?= number_format($row['qty']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#inventoryQuantityTable').DataTable({
            scrollY: "30vh",
            scrollCollapse: true,
            paging: false,
            responsive: true,
            order: [[2, 'desc']],
            searching: false
        });

        $('#inventoryWarehouseTable').DataTable({
            scrollY: "50vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[0, 'asc'], [1, 'asc']]
        });

        // Reload DataTables after print
        window.addEventListener('printComplete', function() {
            location.reload();
        });
    });
</script>

<script src="print-helper.js"></script>

<script>
    function printCombinedReport() {
        const table = $('#inventoryWarehouseTable').DataTable();

        // Use .one() to bind a single-use event listener.
        // When the table is redrawn with all entries, the print function will execute.
        table.one('draw.dt', function () {
            printMultipleTables(
                ['inventoryQuantityTable', 'inventoryWarehouseTable'],
                'Inventory Report',
                '<?= $selectedProject > 0 ? htmlspecialchars($selectedProjectName) : "" ?>',
                ['Inventory Quantity Summary', 'Inventory by Warehouse Details - As of <?= date("F d, Y", strtotime($selectedDate)) ?>']
            );
        }).page.len(-1).draw(); // Set page length to show all entries and redraw.
    }
</script>