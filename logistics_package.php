<?php 
$is_logistics_package_page = true;
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Office Admin', 'Office Coordinator', 'Warehouse Admin', 'Warehouse Coordinator'];
redirectIfNotAuthorized($allowed_roles, 'index.php');

function fetchAndGroupLogisticsData($pdo, $search_dr, $page, $limit) {
    $offset = ($page - 1) * $limit;

    // Base query for counting
    $count_query = "SELECT COUNT(DISTINCT dr_no) FROM deliveries";
    $count_params = [];
    if (!empty($search_dr)) {
        $count_query .= " WHERE dr_no LIKE :search_dr";
        $count_params[':search_dr'] = "%" . $search_dr . "%";
    }
    
    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->execute($count_params);
    $total_rows = $stmt_count->fetchColumn();
    
    // Fetch DR numbers for the current page
    $dr_query = "SELECT DISTINCT dr_no FROM deliveries";
    if (!empty($search_dr)) {
        $dr_query .= " WHERE dr_no LIKE :search_dr";
    }
    $dr_query .= " ORDER BY status, delivery_date LIMIT :limit OFFSET :offset";
    
    $stmt_dr = $pdo->prepare($dr_query);
    if (!empty($search_dr)) {
        $stmt_dr->bindValue(':search_dr', "%" . $search_dr . "%", PDO::PARAM_STR);
    }
    $stmt_dr->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt_dr->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt_dr->execute();
    $dr_nos_for_page = $stmt_dr->fetchAll(PDO::FETCH_COLUMN);

    if (empty($dr_nos_for_page)) {
        return ['grouped_deliveries' => [], 'total_pages' => 0];
    }

    $placeholders = implode(',', array_fill(0, count($dr_nos_for_page), '?'));
    $main_query = "
        SELECT 
            d.delivery_id, p_proj.project_name, s.school_id, s.school_name, s.address, d.package_type,
            d.dr_no, d.delivery_date, d.status, k.keystage_num, k.description, l.lot_name, w.warehouse_id,
            w.warehouse_name, pkg.package_id, pkg.package_num, ps.status as package_status, ps.package_status_id,
            GROUP_CONCAT(item.item_name, ' (', pc.qty, ')' SEPARATOR '<br>') AS package_content
        FROM deliveries d
        JOIN projects p_proj ON d.project_id = p_proj.project_id
        JOIN school s ON d.school_id = s.school_id
        LEFT JOIN keystage k ON d.keystage_id = k.keystage_id
        LEFT JOIN lot l ON d.lot_id = l.lot_id
        LEFT JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
        LEFT JOIN warehouse w ON ll.warehouse_id = w.warehouse_id
        LEFT JOIN package_status ps ON d.delivery_id = ps.delivery_id
        LEFT JOIN package pkg ON ps.package_id = pkg.package_id
        LEFT JOIN package_content pc ON pkg.package_id = pc.package_id
        LEFT JOIN item ON pc.item_id = item.item_id
        WHERE d.dr_no IN ($placeholders)
        GROUP BY d.delivery_id, pkg.package_id
        ORDER BY d.status, d.delivery_date, d.dr_no, pkg.package_num
    ";
    
    $stmt_main = $pdo->prepare($main_query);
    $stmt_main->execute($dr_nos_for_page);
    $deliveries = $stmt_main->fetchAll(PDO::FETCH_ASSOC);

    $grouped_deliveries = [];
    $package_counts_per_delivery = [];
    foreach ($deliveries as $row) {
        if ($row['package_id']) {
            $delivery_id = $row['delivery_id'];
            if (!isset($package_counts_per_delivery[$delivery_id])) {
                $package_counts_per_delivery[$delivery_id] = 0;
            }
            $package_counts_per_delivery[$delivery_id]++;
        }

        $dr = $row['dr_no'];
        if (!isset($grouped_deliveries[$dr])) {
            $grouped_deliveries[$dr] = [
                'dr_no' => $dr, 'keystage_num' => $row['keystage_num'], 'description' => $row['description'],
                'lot_name' => $row['lot_name'], 'project_name' => $row['project_name'], 'school_id' => $row['school_id'],
                'school_name' => $row['school_name'], 'address' => $row['address'], 'delivery_date' => $row['delivery_date'],
                'status' => $row['status'], 'packages' => []
            ];
        }
        if ($row['package_id']) {
            $grouped_deliveries[$dr]['packages'][] = $row;
        }
    }

    return [
        'grouped_deliveries' => $grouped_deliveries,
        'total_pages' => ceil($total_rows / $limit),
        'package_counts' => $package_counts_per_delivery,
    ];
}

try {
    $search_dr = trim($_GET['search_dr'] ?? '');
    $limit = 10;
    $page = max(1, intval($_GET['page'] ?? 1));
    
    $data = fetchAndGroupLogisticsData($pdo, $search_dr, $page, $limit);
    $grouped_deliveries = $data['grouped_deliveries'];
    $total_pages = $data['total_pages'];
    $package_counts = $data['package_counts'];

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}?>
<div class="row g-0 h-100">
    <?php 
    $user_role = $_SESSION['role'];
    if ($user_role == "Office Coordinator" || $user_role == "Warehouse Admin" || $user_role == "Warehouse Coordinator" || $user_role == "Super Admin" || $user_role == "Office Admin"): 
    ?>
    <!-- 1. LEFT SIDEBAR (3 Columns wide on medium/large screens) -->
    <div class="col-md-3 border-end d-flex flex-column vh-100">
        <!-- Header: QR Scanner -->
        <div class="px-3 d-flex justify-content-between align-items-center py-2 border-bottom flex-shrink-0">
            <h5 class="mb-0 text-dark opacity-75">QR Scanner</h5> 
        </div>

        <!-- QR Reader container: fixed height and center content -->
        <div class="d-flex justify-content-center align-items-center flex-shrink-0 py-3 border-bottom" style="height: 30vh;">
            <div id="reader"></div>
        </div>

        <!-- USB Scanner Input -->
        <div class="px-3 py-2 border-top flex-shrink-0">
            <input type="text" id="usbScannerInput" class="form-control" placeholder="Scan QR code here" autofocus>
        </div>
        <!-- No more item list or add button -->
    </div>
    <div class="col-md-9 d-flex flex-column">
    <?php else: ?>
    <div class="col-12">
    <?php endif; ?>
        <div class="flex-grow-1">
            <div class="bg-white px-4 py-4 rounded shadow-sm h-100">
                <!-- Search Form -->
                <form method="GET" action="logistics_package.php" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search_dr" class="form-control" placeholder="Search by DR No..." value="<?= htmlspecialchars($_GET['search_dr'] ?? '') ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
                <!-- Table -->
                <table class="table table-bordered shadow-sm" id="resultTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Package Details</th>
                            <th>Contents</th>
                            <th>Photos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grouped_deliveries as $dr_group): ?>
                            <tr class="table-secondary fw-bold">
                                <td colspan="3">
                                    DR No: <?= htmlspecialchars($dr_group['dr_no']) ?> |
                                    School: <?= htmlspecialchars($dr_group['school_name']) ?> <br>
                                    Lot: <?= htmlspecialchars($dr_group['lot_name']) ?>
                                    <?= !empty($dr_group['keystage_num']) ? ' | Keystage: ' . htmlspecialchars($dr_group['keystage_num']) . ' ' . htmlspecialchars($dr_group['description']) : '' ?>
                                </td>
                            </tr>

                            <?php if (!empty($dr_group['packages'])): ?>
                                <?php foreach ($dr_group['packages'] as $package_index => $package): 
                                    $status = strtolower(trim($package['package_status'] ?? ''));
                                    $is_inactive = ($status === 'delivered' || $status === 'pending');
                                    $text_class = $is_inactive ? 'text-muted' : '';
                                ?>
                                    <tr class="<?= $row_class ?>">
                                        <td class="<?= $text_class ?>">
                                            Package <?= htmlspecialchars($package['package_num']) ?> out of <?= $package_counts[$package['delivery_id']] ?? 0 ?><br>
                                            Status: <span class="fw-bold"><?= htmlspecialchars(ucfirst($status ?: 'Pending')) ?></span>
                                        </td>
                                        <td class="<?= $text_class ?>"><?= $package['package_content'] ?? '<em>No items</em>' ?></td>
                                        <td class="text-center align-middle <?= $text_class ?>">
                                            <?php if (!$is_inactive): ?>
                                                <a href="scan.php?id=<?= htmlspecialchars($package['package_status_id']) ?>&delivery_id=<?= htmlspecialchars($package['delivery_id']) ?>" target="_blank" class="btn btn-info btn-sm" title="Add Photos">
                                                    <i class="bi bi-image fs-4"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3"><em>No packages for this delivery.</em></td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center" id="pagination">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&limit=<?= $limit ?>&search_dr=<?= urlencode($search_dr) ?>">Previous</a>
                        </li>

                        <?php
                        $window = 9;
                        $start = max(1, $page - floor($window / 2));
                        $end   = min($total_pages, $start + $window - 1);

                        if ($end - $start + 1 < $window) {
                            $start = max(1, $end - $window + 1);
                        }

                        for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>&search_dr=<?= urlencode($search_dr) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?>&limit=<?= $limit ?>&search_dr=<?= urlencode($search_dr) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php require "template/footer.php"; ?>

<!-- QR script -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    // Handle successful QR code or USB scan
    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`, decodedResult);

        // Only navigate if the decoded text is a URL to scan.php
        if (typeof decodedText === 'string' && decodedText.includes('entry.php')) {
            window.location.href = decodedText;
            return;
        }
        // If it's not a URL for scan.php, do nothing, as per the user's request to remove "add items" functionality.
        console.warn("QR code did not contain a valid scan.php URL or unexpected format:", decodedText);
    }

    // Handle USB scanner input
    document.getElementById('usbScannerInput').addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            const scannedCode = this.value.trim();
            this.value = ""; // clear input
            if (!scannedCode) return;
            onScanSuccess(scannedCode, null); // Pass string to onScanSuccess
        }
    });

    // Start scanner
    const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>