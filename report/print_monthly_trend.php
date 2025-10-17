<?php 
    require "reports_header.php"; 
    require "../script/role_auth.php";
    require "../config/db.php";

    $allowed_roles = ['Super Admin', 'Office Admin', 'Warehouse Admin'];
    redirectIfNotAuthorized($allowed_roles, '../index.php');

    $selectedProject = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $selectedProjectName = "";

    // handle CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=monthly_trend_report' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        fputcsv($output, ['Month', 'Status', 'Total Deliveries']);
        
        // Fetch data for CSV
        $monthlyTrendQuery = "
            SELECT 
                DATE_FORMAT(d.delivered_date, '%Y-%m') AS month,
                CASE d.status
                    WHEN 'warehouse' THEN 'Warehouse'
                    WHEN 'accepted'  THEN 'Logistics'
                    WHEN 'delivered' THEN 'Schools'
                    ELSE d.status
                END AS status,
                COUNT(*) AS total
            FROM deliveries d
            WHERE d.delivered_date IS NOT NULL
                AND d.status <> 'pending'
                " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
            GROUP BY month, status
            ORDER BY month DESC, status
        ";
        
        $stmt = $pdo->query($monthlyTrendQuery);
        $monthlyTrendData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // CSV Data Rows
        foreach ($monthlyTrendData as $row) {
            fputcsv($output, [
                $row['month'],
                $row['status'],
                $row['total']
            ]);
        }
        
        // Calculate and add totals
        $monthlyTotals = [];
        foreach ($monthlyTrendData as $row) {
            $monthlyTotals[$row['month']] = ($monthlyTotals[$row['month']] ?? 0) + $row['total'];
        }
        
        // Grand total
        $grandTotal = array_sum($monthlyTotals);
        fputcsv($output, []); // Empty row
        fputcsv($output, ['GRAND TOTAL', '', $grandTotal]);
        
        fclose($output);
        exit();
    }

    if ($selectedProject > 0) {
        $stmt = $pdo->prepare("SELECT project_name FROM project WHERE project_id = ?");
        $stmt->execute([$selectedProject]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $selectedProjectName = $result['project_name'] ?? "";
    }

    // Fetch monthly trend data
    $monthlyTrendQuery = "
        SELECT 
            DATE_FORMAT(d.delivered_date, '%Y-%m') AS month,
            CASE d.status
                WHEN 'warehouse' THEN 'Warehouse'
                WHEN 'accepted'  THEN 'Logistics'
                WHEN 'delivered' THEN 'Schools'
                ELSE d.status
            END AS status,
            COUNT(*) AS total
        FROM deliveries d
        WHERE d.delivered_date IS NOT NULL
            AND d.status <> 'pending'
            " . ($selectedProject > 0 ? "AND d.project_id = $selectedProject" : "") . "
        GROUP BY month, status
        ORDER BY month DESC, status
    ";
    
    $stmt = $pdo->query($monthlyTrendQuery);
    $monthlyTrendData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📈 Monthly Delivery Trend Report <?= $selectedProject > 0 ? "- " . htmlspecialchars($selectedProjectName) : "" ?></h4>
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

<table id="monthlyTrendTable" class="table table-bordered shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Month</th>
            <th>Status</th>
            <th>Total Deliveries</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($monthlyTrendData as $row): ?>
        <tr>
            <td class="text-center">
                <?php 
                    $date = new DateTime($row['month'] . '-01');
                    echo $date->format('F Y');
                ?>
            </td>
            <td class="text-center"><?= htmlspecialchars($row['status']) ?></td>
            <td class="text-center"><?= number_format($row['total']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require "../template/footer.php"; ?>

<script>
    $(document).ready(function() {
        $('#monthlyTrendTable').DataTable({
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
// For your current page
function printDeliveryReport() {
    printTable(
        'monthlyTrendTable', 
        'Monthly Delivery Trend Report', 
        '<?= $selectedProject > 0 ? htmlspecialchars($selectedProjectName) : "" ?>'
    );
}
</script>