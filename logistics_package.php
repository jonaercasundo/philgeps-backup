<?php 
$is_logistics_page = true;
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Office Admin', 'Office Coordinator', 'Logistics'];
redirectIfNotAuthorized($allowed_roles, 'index.php');

function fetchAndGroupLogisticsData($pdo, $search_dr, $page, $limit, $user_role) {
    $offset = ($page - 1) * $limit;
    
    $params = [];
    $where_conditions = [];
    $role_join_sql = '';

    if ($user_role !== 'Logistics') {
        $role_join_sql = ' JOIN package_status ps_filter ON d.delivery_id = ps_filter.delivery_id ';
        $where_conditions[] = "ps_filter.status = 'For Approval'";
    }

    if (!empty($search_dr)) {
        $where_conditions[] = "d.dr_no LIKE :search_dr";
        $params[':search_dr'] = "%" . $search_dr . "%";
    }

    $where_sql = !empty($where_conditions) ? ' WHERE ' . implode(' AND ', $where_conditions) : '';

    // --- Count Query ---
    $count_query = "SELECT COUNT(DISTINCT d.dr_no) FROM deliveries d " . $role_join_sql . $where_sql;
    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->execute($params);
    $total_rows = $stmt_count->fetchColumn();
    
    // --- DR Query ---
    $dr_query = "SELECT DISTINCT d.dr_no FROM deliveries d " . $role_join_sql . $where_sql . " ORDER BY d.status, d.delivery_date LIMIT :limit OFFSET :offset";
    
    $stmt_dr = $pdo->prepare($dr_query);
    foreach ($params as $key => $val) {
        $stmt_dr->bindValue($key, $val);
    }
    $stmt_dr->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt_dr->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt_dr->execute();
    $dr_nos_for_page = $stmt_dr->fetchAll(PDO::FETCH_COLUMN);

    if (empty($dr_nos_for_page)) {
        return ['grouped_deliveries' => [], 'total_pages' => 0, 'package_counts' => []];
    }

    $placeholders = implode(',', array_fill(0, count($dr_nos_for_page), '?'));
    
    $main_query_where_sql = "d.dr_no IN ($placeholders)";
    if ($user_role !== 'Logistics') {
        $main_query_where_sql .= " AND ps.status = 'For Approval'";
    }

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
        WHERE $main_query_where_sql
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
    
    $data = fetchAndGroupLogisticsData($pdo, $search_dr, $page, $limit, $_SESSION['role']);
    $grouped_deliveries = $data['grouped_deliveries'];
    $total_pages = $data['total_pages'];
    $package_counts = $data['package_counts'];

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}?>
<div class="row g-0 h-100">
    <?php 
    $user_role = $_SESSION['role'];
    if ($user_role == "Office Coordinator" || $user_role == "Logistics" || $user_role == "Super Admin" || $user_role == "Office Admin"): 
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
                            <?php if ($_SESSION['role'] !== 'Logistics'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
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
                                            <?php if ($status === 'accepted'): ?>
                                                <a href="scan.php?id=<?= htmlspecialchars($package['package_status_id']) ?>&delivery_id=<?= htmlspecialchars($package['delivery_id']) ?>" target="_blank" class="btn btn-info btn-sm" title="Add Photos">
                                                    <i class="bi bi-image fs-4"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($_SESSION['role'] !== 'Logistics'): ?>
                                            <td class="text-center align-middle">
                                                <?php if ($status === 'for approval'): ?>
                                                    <button class="btn btn-success btn-sm" 
                                                            onclick="openAcceptModal(<?= $package['package_status_id'] ?>)"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#acceptPackageModal"
                                                            title="Accept Package">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" 
                                                            onclick="openRejectModal(<?= $package['package_status_id'] ?>)" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#rejectPackageModal"
                                                            title="Reject Package">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
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

<!-- Accept Package Modal -->
<div class="modal fade" id="acceptPackageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Accept Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to accept this package?</p>
                <form id="acceptPackageForm" onsubmit="event.preventDefault(); submitAcceptPackage();">
                    <input type="hidden" name="package_status_id" id="accept_package_status_id">
                    <div class="mb-3">
                        <label class="form-label">Input password to Continue</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="acceptPackageForm" class="btn btn-success">Accept</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Package Modal -->
<div class="modal fade" id="rejectPackageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                 <form id="rejectPackageForm" onsubmit="event.preventDefault(); submitRejectPackage();">
                    <p>Are you sure you want to reject this package?</p>
                    <input type="hidden" name="package_status_id" id="reject_package_status_id">
                    <div class="mb-3">
                        <label class="form-label">Input password to Continue</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <select class="form-select mb-2" id="reject_package_remarks_dropdown">
                            <option value="">-- Select a remark --</option>
                            <option value="Human error">Human error</option>
                            <option value="Rejected by client">Rejected by client</option>
                            <option value="Damaged during delivery">Damaged during delivery</option>
                            <option value="custom">Add custom remark...</option>
                        </select>
                        
                        <!-- Custom remarks input -->
                        <div class="collapse" id="rejectPackageCustomRemarksCollapse">
                            <div class="mt-2">
                                <label class="form-label">Custom Remark</label>
                                <textarea class="form-control" id="reject_package_custom_remarks" name="custom_remarks" rows="2" placeholder="Due to..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="rejectPackageForm" class="btn btn-danger">Reject</button>
            </div>
        </div>
    </div>
</div>

<?php require "template/footer.php"; ?>

<!-- QR script -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    function openAcceptModal(packageStatusId) {
        document.getElementById('accept_package_status_id').value = packageStatusId;
    }

    function openRejectModal(packageStatusId) {
        document.getElementById('reject_package_status_id').value = packageStatusId;
    }
    
function submitAcceptPackage() {
    const formData = new FormData(document.getElementById('acceptPackageForm'));
    
    fetch('script/accept_package.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                window.location.href = 'logistics_package.php?toast=' + encodeURIComponent(data.message) + '&type=success';
            } else {
                // Close the modal first
                const acceptModal = bootstrap.Modal.getInstance(document.getElementById('acceptPackageModal'));
                if (acceptModal) acceptModal.hide();
                
                // Show error as toast instead of alert
                window.location.href = 'logistics_package.php?toast=' + encodeURIComponent(data.message) + '&type=danger';
            }
        } catch (e) {
            console.error('Response:', text);
            
            // Close the modal first
            const acceptModal = bootstrap.Modal.getInstance(document.getElementById('acceptPackageModal'));
            if (acceptModal) acceptModal.hide();
            
            // Show error as toast
            window.location.href = 'logistics_package.php?toast=Invalid response from server&type=danger';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Close the modal first
        const acceptModal = bootstrap.Modal.getInstance(document.getElementById('acceptPackageModal'));
        if (acceptModal) acceptModal.hide();
        
        // Show error as toast
        window.location.href = 'logistics_package.php?toast=' + encodeURIComponent('Network error: ' + error.message) + '&type=danger';
    });
}

function submitRejectPackage() {
    const formData = new FormData(document.getElementById('rejectPackageForm'));
    
    const dropdown = document.getElementById('reject_package_remarks_dropdown');
    let remarks = '';
    
    if (dropdown && dropdown.value === 'custom') {
        const customRemarks = document.getElementById('reject_package_custom_remarks');
        remarks = customRemarks ? customRemarks.value.trim() : '';
    } else if (dropdown) {
        remarks = dropdown.value;
    }
    
    formData.append('remarks', remarks);
    
    fetch('script/reject_package.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectPackageModal'));
                if (rejectModal) rejectModal.hide();
                
                window.location.href = 'logistics_package.php?toast=' + encodeURIComponent(data.message) + '&type=success';
            } else {
                // Close the modal first
                const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectPackageModal'));
                if (rejectModal) rejectModal.hide();
                
                // Show error as toast instead of alert
                window.location.href = 'logistics_package.php?toast=' + encodeURIComponent(data.message) + '&type=danger';
            }
        } catch (e) {
            console.error('Response:', text);
            
            // Close the modal first
            const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectPackageModal'));
            if (rejectModal) rejectModal.hide();
            
            window.location.href = 'logistics_package.php?toast=Invalid response from server&type=danger';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Close the modal first
        const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectPackageModal'));
        if (rejectModal) rejectModal.hide();
        
        // Show error as toast
        window.location.href = 'logistics_package.php?toast=' + encodeURIComponent('Network error: ' + error.message) + '&type=danger';
    });
}

    // Handle dropdown change to show/hide custom remarks for REJECT modal
    document.addEventListener('DOMContentLoaded', function() {
        const rejectPackageRemarksDropdown = document.getElementById('reject_package_remarks_dropdown');
        if (rejectPackageRemarksDropdown) {
            rejectPackageRemarksDropdown.addEventListener('change', function() {
                const rejectPackageCustomRemarksCollapse = document.getElementById('rejectPackageCustomRemarksCollapse');
                const bsCollapse = new bootstrap.Collapse(rejectPackageCustomRemarksCollapse, {
                    toggle: false
                });
                
                if (this.value === 'custom') {
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                    document.getElementById('reject_package_custom_remarks').value = '';
                }
            });
        }
    });

    // Handle successful QR code or USB scan
    function onScanSuccess(decodedText, decodedResult) {

        // Only navigate if the decoded text is a URL to scan.php
        if (typeof decodedText === 'string' && decodedText.includes('entry.php')) {
            window.location.href = decodedText;
            return;
        }
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