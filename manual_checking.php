<?php 
$is_deliveries_page = true;
require "template/header.php"; 
require "script/role_auth.php";
require "config/db.php";

// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');

try {
    $limit = 10;
    $page = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    // Get search parameter
    $search = trim($_GET['search'] ?? '');

    // Build WHERE clause for search
    $searchCondition = "d.status = 'delivered' AND bg.dr_no IS NULL";
    $searchParams = [];
    
    if (!empty($search)) {
        $searchCondition .= " AND (
            d.dr_no LIKE :search OR
            p.project_name LIKE :search OR
            s.school_name LIKE :search OR
            s.address LIKE :search OR
            l.lot_name LIKE :search OR
            k.keystage_num LIKE :search OR
            k.description LIKE :search
        )";
        $searchParams[':search'] = "%$search%";
    }

    // Count total deliveries with search filter
    $countQuery = "SELECT COUNT(DISTINCT d.delivery_id) FROM deliveries d
        LEFT JOIN keystage k ON k.keystage_id = d.keystage_id
        JOIN lot l ON l.lot_id = d.lot_id
        JOIN projects p ON d.project_id = p.project_id
        JOIN school s ON d.school_id = s.school_id
        LEFT JOIN billing_grouped bg 
            ON CONVERT(d.dr_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = 
            CONVERT(bg.dr_no USING utf8mb4) COLLATE utf8mb4_unicode_ci
        WHERE $searchCondition";
    
    $countStmt = $pdo->prepare($countQuery);
    foreach ($searchParams as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total_rows = $countStmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Fetch deliveries with status 'delivered'
    $stmt = $pdo->prepare("
    SELECT 
        d.delivery_id,
        p.project_name,
        s.school_id,
        s.school_name,
        s.address,
        d.dr_no,
        d.delivery_date,
        d.status,
        k.keystage_num,
        k.description,
        l.lot_name,
        COALESCE(pkg_items.items_contents, '') AS items_contents
    FROM deliveries d
    LEFT JOIN keystage k ON k.keystage_id = d.keystage_id
    JOIN lot l ON l.lot_id = d.lot_id
    JOIN projects p ON d.project_id = p.project_id
    JOIN school s ON d.school_id = s.school_id
    LEFT JOIN billing_grouped bg 
        ON CONVERT(d.dr_no USING utf8mb4) COLLATE utf8mb4_unicode_ci = 
        CONVERT(bg.dr_no USING utf8mb4) COLLATE utf8mb4_unicode_ci
    LEFT JOIN (
        SELECT 
            x.delivery_id,
            GROUP_CONCAT(
                CONCAT(
                    'Package ', x.rn, ' out of ', x.total_packages, 
                    ' — ', x.colored_pkg_status, '<br>', 
                    x.items
                )
                SEPARATOR '<br><br>'
            ) AS items_contents
        FROM (
            SELECT 
                d.delivery_id,
                p.package_id,
                ROW_NUMBER() OVER (PARTITION BY d.delivery_id ORDER BY p.package_id) AS rn,
                COUNT(*) OVER (PARTITION BY d.delivery_id) AS total_packages,
                GROUP_CONCAT(CONCAT(i.item_name, ' (', pc.qty, ')') SEPARATOR '<br>') AS items,
                CASE 
                    WHEN COALESCE(MAX(dp.status), 'PENDING') = 'DELIVERED' THEN
                        CONCAT('<span class=\"text-success font-weight-bold\">DELIVERED</span>')
                    WHEN COALESCE(MAX(dp.status), 'PENDING') = 'ACCEPTED' THEN
                        CONCAT('<span class=\"text-primary font-weight-bold\">ACCEPTED</span>')
                    WHEN COALESCE(MAX(dp.status), 'PENDING') = 'WAREHOUSE' THEN
                        CONCAT('<span class=\"text-info font-weight-bold\">WAREHOUSE</span>')
                    ELSE
                        CONCAT('<span class=\"text-warning font-weight-bold\">PENDING</span>')
                END AS colored_pkg_status
            FROM deliveries d
            LEFT JOIN package p 
                ON (
                    (d.keystage_id IS NOT NULL AND d.keystage_id = p.keystage_id)
                    OR (d.keystage_id IS NULL AND d.lot_id = p.lot_id)
                )
            JOIN package_content pc ON pc.package_id = p.package_id
            JOIN item i ON pc.item_id = i.item_id
            LEFT JOIN package_status dp 
                ON dp.delivery_id = d.delivery_id 
                AND dp.package_id = p.package_id
            WHERE d.status = 'delivered'
            GROUP BY d.delivery_id, p.package_id
        ) x
        GROUP BY x.delivery_id
    ) pkg_items ON pkg_items.delivery_id = d.delivery_id
        WHERE $searchCondition
        AND bg.dr_no IS NULL
        ORDER BY d.delivery_date DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($searchParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group deliveries by dr_no
    $grouped_deliveries = [];
    foreach ($deliveries as $row) {
        $dr = $row['dr_no'];
        if (!isset($grouped_deliveries[$dr])) {
            $grouped_deliveries[$dr] = [
                'dr_no' => $dr,
                'project_name' => $row['project_name'],
                'school_name' => $row['school_name'],
                'delivery_date' => $row['delivery_date'],
                'deliveries' => []
            ];
        }
        $grouped_deliveries[$dr]['deliveries'][] = $row;
    }

} catch (PDOException $e) {
    die("DB  " . $e->getMessage());
}

// After your existing try-catch block for deliveries
require "script/get_billing_grouped_summary.php";
$grouped_summary = getBillingGroupSummary($pdo);
?>

<!-- Main Full-Screen Container -->
<div class="row g-0 h-100">
    <!-- LEFT SIDEBAR -->
    <div class="col-md-3 border-end d-flex flex-column bg-light">
        <div class="p-3 border-bottom bg-dark text-white">
            <h5 class="mb-0">Billing Groups</h5>
        </div>

        <div class="flex-fill d-flex flex-column">
            <!-- Search Bar for Billing Groups -->
            <div class="p-3 border-bottom bg-white">
                <div class="input-group input-group-sm">
                    <input type="text" 
                           id="groupSearchInput" 
                           class="form-control" 
                           placeholder="Search groups or DR...">
                    <button class="btn btn-outline-secondary" type="button" id="clearGroupSearch">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>

            <!-- Scrollable content area -->
            <div class="flex-fill" style="max-height: 60vh; overflow-y: auto;">
                <div class="p-3">
                    <?php if (empty($grouped_summary)): ?>
                        <div class="text-center text-muted py-4">
                            <small>No billing groups yet</small>
                        </div>
                    <?php else: ?>
                       <!-- Billing Groups Accordion -->
                        <div class="accordion" id="billingGroupsAccordion">
                        <?php foreach ($grouped_summary as $index => $group): ?>
                            <?php 
                            $collapseId = "collapseGroup" . $index;
                            $headingId = "headingGroup" . $index;
                            $groupName = htmlspecialchars($group['group_name']);
                            ?>
                            
                            <div class="accordion-item border-0 border-bottom">
                            <!-- Accordion Header -->
                            <h2 class="accordion-header" id="<?= $headingId ?>">
                                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                    <button class="accordion-button collapsed bg-light fw-semibold flex-grow-1 border-0 shadow-none"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?= $collapseId ?>"
                                            aria-expanded="false"
                                            aria-controls="<?= $collapseId ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <strong><?= $groupName ?></strong>
                                        <span class="badge bg-success"><?= $group['dr_count'] ?></span>
                                    </div>
                                    </button>

                                    <button class="btn btn-sm btn-outline-success ms-2 add-group-btn"
                                            data-group-name="<?= $groupName ?>"
                                            data-group-id="<?= htmlspecialchars($group['group_id']) ?>"
                                            title="Add more to Group">
                                    <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary ms-2 edit-group-btn"
                                            data-group-name="<?= $groupName ?>"
                                            data-group-id="<?= htmlspecialchars($group['group_id']) ?>"
                                            title="Edit Group">
                                    <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info ms-2 qr-scan-btn"
                                            data-group-name="<?= $groupName ?>"
                                            data-group-id="<?= htmlspecialchars($group['group_id']) ?>"
                                            title="Scan QR Code to Add Delivery">
                                    <i class="bi bi-qr-code"></i>
                                    </button>
                                </div>
                            </h2>

                            <!-- Accordion Body -->
                            <div id="<?= $collapseId ?>" 
                                class="accordion-collapse collapse" 
                                aria-labelledby="<?= $headingId ?>" 
                                data-bs-parent="#billingGroupsAccordion">
                                <div class="accordion-body bg-white">

                                <?php if (!empty($group['dr_numbers'])): ?>
                                    <div class="list-group">
                                    <?php foreach ($group['dr_numbers'] as $dr_no): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>DR No: <?= htmlspecialchars($dr_no) ?></span>
                                        <button class="btn btn-sm btn-danger delete-group-btn" 
                                                data-dr="<?= htmlspecialchars($dr_no) ?>"
                                                title="Remove DR">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0"><em>No DRs in this group yet.</em></p>
                                <?php endif; ?>
                                </div>
                            </div>
                            </div>
                        <?php endforeach; ?>
                        </div>

                        <div id="noGroupResults" class="text-center text-muted py-4" style="display: none;">
                            <i class="bi bi-search" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">No groups or DR numbers found</p>
                        </div>
                        <div id="noGroupResults" class="text-center text-muted py-4" style="display: none;">
                            <i class="bi bi-search" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">No groups or DR numbers found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats - Fixed at bottom -->
            <div class="border-top p-3 bg-white">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="fw-bold text-primary"><?= count($grouped_summary) ?></div>
                        <small class="text-muted">Groups</small>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-success"><?= array_sum(array_column($grouped_summary, 'dr_count')) ?></div>
                        <small class="text-muted">Total DRs</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. RIGHT MAIN CONTENT AREA (9 Columns wide on medium/large screens) -->
    <div class="col-md-9 d-flex flex-column">
        <!-- Large Main Content/Display Area -->
        <div class="flex-grow-1 p-4">
            <div class="bg-white rounded shadow-sm h-100">
                <div class="d-flex align-items-center mb-3">
                    <h5 class="mb-0 text-dark">Manual Checking Page</h5>
                    <button class="btn btn-success ms-auto" id="addToGroupBtn">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="mb-3">
                    <form method="GET" action="" class="d-flex gap-2">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by DR No, Project, or School..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="?" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                    <?php if (!empty($search)): ?>
                        <small class="text-muted mt-2 d-block">
                            Found <?= $total_rows ?> result<?= $total_rows != 1 ? 's' : '' ?> for "<?= htmlspecialchars($search) ?>"
                        </small>
                    <?php endif; ?>
                </div>

                <!-- Table - EXACT SAME STRUCTURE as deliveries.php -->
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                    <?php if (empty($grouped_deliveries)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3">
                                <?php if (!empty($search)): ?>
                                    No deliveries found matching your search.
                                <?php else: ?>
                                    No deliveries available for billing.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                    <table class="table table-bordered shadow-sm">
                        <thead class="table-dark">
                            <tr>
                                <th></th>
                                <th>Delivery Details</th>
                                <th>Items</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_deliveries as $dr_group): ?>
                                <tr class="table-secondary fw-bold">
                                    <td class="text-center">
                                        <input type="checkbox" class="dr-checkbox" 
                                               name="selected_dr[]" 
                                               value="<?= htmlspecialchars($dr_group['dr_no']) ?>"
                                               data-dr="<?= htmlspecialchars($dr_group['dr_no']) ?>">
                                    </td>
                                    <td colspan="3">
                                        DR No: <?= htmlspecialchars($dr_group['dr_no']) ?> — 
                                        Project: <?= htmlspecialchars($dr_group['project_name']) ?> — 
                                        School: <?= htmlspecialchars($dr_group['school_name']) ?>
                                    </td>
                                </tr>

                                <?php foreach ($dr_group['deliveries'] as $d): ?>
                                    <tr>
                                        <td></td>
                                        <td>
                                            LOT <?= htmlspecialchars($d['lot_name']) ?> 
                                            <?= !empty($d['keystage_num']) ? "Keystage " . $d['keystage_num'] . " " . $d['description'] : '' ?>
                                        </td>
                                        <td>
                                            <?= !empty($d['items_contents']) ? $d['items_contents'] : '<em>No items</em>' ?>
                                        </td>
                                        <td><?= htmlspecialchars($d['delivery_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <!-- Previous -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&limit=<?= $limit ?>">Previous</a>
                        </li>

                        <?php
                        $window = 9;
                        $start = max(1, $page - floor($window / 2));
                        $end = min($total_pages, $start + $window - 1);

                        if ($end - $start + 1 < $window) {
                            $start = max(1, $end - $window + 1);
                        }

                        for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next -->
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?>&limit=<?= $limit ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add to Group Modal -->
<div class="modal fade" id="addToGroupModal" tabindex="-1" aria-labelledby="addToGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addToGroupModalLabel">Add to Billing Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="groupIdInput">
                <input type="hidden" id="isEditMode" value="0">
                <div class="mb-3">
                    <label for="groupNameInput" class="form-label">Group Name</label>
                    <input type="text" class="form-control" id="groupNameInput" placeholder="Enter group name (e.g., Group 1, Group 2)" required>
                </div>
                <div id="selectedDRList" class="mb-3">
                    <strong>Selected Deliveries:</strong>
                    <ul id="drList" class="mt-2"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAddToGroup">Confirm Add to Group</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete DR from Billing Group</h5>
      </div>
      <div class="modal-body">
        <form id="deleteForm" method="POST" action="script/delete.php">
          <input type="hidden" name="source_page" value="<?= basename($_SERVER['PHP_SELF']) . '?' . http_build_query($_GET) ?>">
          <input type="hidden" id="delete_dr_no" name="id">
          <input type="hidden" name="table" value="billing_grouped">
          <input type="hidden" name="condition" value="dr_no">
          <div class="mb-3">
            <label>Are you sure you want to remove <strong id="drToDeleteText"></strong> from the billing group?</label>
          </div>
          <div class="mb-3">
            <label>Input password to Continue</label>
            <input type="password" name="deletePassword" class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteForm').submit();">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Billing Group Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Billing Group</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="editGroupForm">
                    <input type="hidden" id="edit_group_id" name="edit_group_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Group Name *</label>
                        <input type="text" class="form-control" name="edit_group_name" id="edit_group_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="edit_status" id="edit_status" required>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">DR Numbers in this Group</label>
                        <div id="edit_dr_list" class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                            <!-- DR numbers will be listed here -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="updateBillingGroup()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Scan Modal -->
<div class="modal fade" id="qrScanModal" tabindex="-1" aria-labelledby="qrScanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrScanModalLabel">QR Scanner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <!-- QR Reader container: fixed height and center content -->
                    <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                        <div id="qrReader" style="width: 100%; height: 100%; max-width: 300px; max-height: 200px;"></div>
                    </div>

                    <!-- USB Scanner Input -->
                    <div class="mt-3">
                        <input type="text" id="usbScannerInput" class="form-control" placeholder="Scan QR code here" autofocus>
                    </div>
                    
                    <div class="mt-3">
                        <div id="qrResult" class="alert alert-info" style="display: none;"></div>
                    </div>
                    
                    <!-- Scanned Deliveries List -->
                    <div class="mt-3">
                        <h6>Scanned Deliveries (<span id="scannedCount">0</span>)</h6>
                        <div class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                            <ul id="scannedDeliveriesList" class="list-group list-group-flush mb-0">
                                <li class="list-group-item d-flex justify-content-between align-items-center text-muted">
                                    No deliveries scanned yet
                                </li>
                            </ul>
                        </div>
                        <div class="mt-2 d-flex justify-content-between">
                            <button id="clearScannedBtn" class="btn btn-sm btn-outline-danger" style="display: none;">Clear All</button>
                            <button id="finishScanningBtn" class="btn btn-sm btn-success" style="display: none;">Finish Scanning & Add to Group</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require "template/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
    // Get selected DR numbers
    function getSelectedDRs() {
        const selected = [];
        document.querySelectorAll('.dr-checkbox:checked').forEach(checkbox => {
            selected.push(checkbox.value);
        });
        return selected;
    }

    // Show modal with selected deliveries (ADD mode)
    document.getElementById('addToGroupBtn').addEventListener('click', function() {
        const selectedDRs = getSelectedDRs();
        
        if (selectedDRs.length === 0) {
            window.location.href = '?toast=Please select at least one delivery to add to the billing group&type=danger';
            return;
        }
        
        // Populate the list in modal
        const drList = document.getElementById('drList');
        drList.innerHTML = '';
        selectedDRs.forEach(dr => {
            const li = document.createElement('li');
            li.textContent = `DR No: ${dr}`;
            drList.appendChild(li);
        });
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('addToGroupModal'));
        modal.show();
    });

    // Handle add group button click (Add Populate mode)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-group-btn')) {
            const btn = e.target.closest('.add-group-btn');
            const groupName = btn.dataset.groupName;
            const groupId = btn.dataset.groupId;
            
            const selectedDRs = getSelectedDRs();
            
            if (selectedDRs.length === 0) {
                window.location.href = '?toast=Please select at least one delivery to add to the billing group&type=danger';
                return;
            }
            
            // Set to EDIT mode
            document.getElementById('isEditMode').value = '1';
            document.getElementById('groupIdInput').value = groupId;
            document.getElementById('groupNameInput').value = groupName;
            document.getElementById('addToGroupModalLabel').textContent = 'Edit Billing Group';
            document.getElementById('confirmAddToGroup').textContent = 'Confirm Edit Group';
            
            // Populate the list in modal
            const drList = document.getElementById('drList');
            drList.innerHTML = '';
            selectedDRs.forEach(dr => {
                const li = document.createElement('li');
                li.textContent = `DR No: ${dr}`;
                drList.appendChild(li);
            });
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('addToGroupModal'));
            modal.show();
        }
    });

    // Handle confirm button
    document.getElementById('confirmAddToGroup').addEventListener('click', function() {
        const selectedDRs = getSelectedDRs();
        const groupName = document.getElementById('groupNameInput').value.trim();
        
        if (selectedDRs.length === 0) {
            window.location.href = '?toast=No deliveries selected&type=danger';
            return;
        }
        
        if (groupName === '') {
            alert('Please enter a group name');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('group_name', groupName);
        selectedDRs.forEach(dr => {
            formData.append('selected_dr[]', dr);
        });
        
        // Disable button during request
        const confirmBtn = this;
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Processing...';
        
        // Send AJAX request
        fetch('script/add_billing_grouped.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('addToGroupModal')).hide();
                // Clear group name input
                document.getElementById('groupNameInput').value = '';
                // Uncheck all checkboxes
                document.querySelectorAll('.dr-checkbox:checked').forEach(cb => cb.checked = false);
                // Redirect with success message
                window.location.href = `?toast=${encodeURIComponent(data.toast)}&type=${data.type}`;
            } else {
                window.location.href = `?toast=${encodeURIComponent(data.toast)}&type=${data.type}`;
            }
        })
        .catch(error => {
            console.error('', error);
            window.location.href = '?toast=An error occurred while processing your request&type=danger';
        })
        .finally(() => {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm Add to Group';
        });
    });

    // Handle remove DR from group - Show delete modal
    document.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.btn-danger[data-dr]');

        if (removeBtn) {
            const drNo = removeBtn.dataset.dr;
            
            // Set DR number in modal
            document.getElementById('delete_dr_no').value = drNo;
            document.getElementById('drToDeleteText').textContent = `DR ${drNo}`;
            
            // Show delete modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    });

    // Billing Groups Search Functionality
    const groupSearchInput = document.getElementById('groupSearchInput');
    const clearGroupSearchBtn = document.getElementById('clearGroupSearch');
    const billingGroupsAccordion = document.getElementById('billingGroupsAccordion');
    const noGroupResults = document.getElementById('noGroupResults');

    if (groupSearchInput && billingGroupsAccordion) {
        groupSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const accordionItems = billingGroupsAccordion.querySelectorAll('.accordion-item');
            let visibleCount = 0;

            accordionItems.forEach(item => {
                // Get group name from the button text
                const accordionButton = item.querySelector('.accordion-button strong');
                const groupName = accordionButton ? accordionButton.textContent.toLowerCase() : '';
                
                // Get all DR numbers from the list group items
                const drItems = item.querySelectorAll('.list-group-item span');
                let drNumbers = '';
                drItems.forEach(span => {
                    drNumbers += span.textContent.toLowerCase() + ' ';
                });
                
                // Check if search term matches group name or any DR number
                if (groupName.includes(searchTerm) || drNumbers.includes(searchTerm)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (visibleCount === 0 && searchTerm !== '') {
                billingGroupsAccordion.style.display = 'none';
                noGroupResults.style.display = 'block';
            } else {
                billingGroupsAccordion.style.display = '';
                noGroupResults.style.display = 'none';
            }

            // Show/hide clear button
            clearGroupSearchBtn.style.display = searchTerm ? 'inline-block' : 'none';
        });

        // Clear search
        clearGroupSearchBtn.addEventListener('click', function() {
            groupSearchInput.value = '';
            groupSearchInput.dispatchEvent(new Event('input'));
        });

        // Initialize clear button visibility
        clearGroupSearchBtn.style.display = 'none';
    }


</script>

<script>
    // Handle edit group button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-group-btn')) {
            const btn = e.target.closest('.edit-group-btn');
            const groupName = btn.dataset.groupName;
            const groupId = btn.dataset.groupId;
            
            // Fetch group details
            fetch(`script/get_billing_group.php?group_id=${groupId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateEditGroupModal(
                            data.group.group_id, 
                            data.group.group_name, 
                            data.group.status, 
                            data.group.dr_numbers,
                            data.group.status_options  // Pass the status options
                        );
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editGroupModal'));
                        modal.show();
                    } else {
                        alert('Error loading group details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('', error);
                    alert('Error loading group details');
                });
        }
    });
        
    // Update Edit Group Modal
    function updateEditGroupModal(groupId, groupName, status, drNumbers, statusOptions) {   
        document.getElementById("edit_group_id").value = groupId;
        document.getElementById("edit_group_name").value = groupName.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        
        // Populate status dropdown with fetched options
        const statusSelect = document.getElementById("edit_status");
        statusSelect.innerHTML = '';
        
        statusOptions.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option.charAt(0).toUpperCase() + option.slice(1); // Capitalize first letter
            if (option === status) {
                optionElement.selected = true;
            }
            statusSelect.appendChild(optionElement);
        });
        
        // Populate DR numbers list
        const drListContainer = document.getElementById("edit_dr_list");
        drListContainer.innerHTML = '';
        
        if (drNumbers && drNumbers.length > 0) {
            const ul = document.createElement('ul');
            ul.className = 'list-unstyled mb-0';
            drNumbers.forEach(dr => {
                const li = document.createElement('li');
                li.className = 'mb-1';
                li.innerHTML = `<i class="bi bi-file-text"></i> DR No: ${dr}`;
                ul.appendChild(li);
            });
            drListContainer.appendChild(ul);
        } else {
            drListContainer.innerHTML = '<p class="text-muted mb-0"><em>No DRs in this group</em></p>';
        }
    }

// Update billing group
    function updateBillingGroup() {
        const formData = new FormData(document.getElementById('editGroupForm'));
        
        // Disable button during request
        const saveBtn = event.target;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        
        fetch('script/update_billing_group.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = `?toast=${encodeURIComponent(data.message)}&type=success`;
                } else {
                    alert(' ' + (data.message || 'Unknown error'));
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Changes';
                }
            } catch (e) {
                console.error('Response:', text);
                alert(' Invalid response from server');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
            }
        })
        .catch(error => {
            console.error('', error);
            alert(' ' + error);
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        });
    }

</script>

<!-- QR script -->
<script src="https://unpkg.com/html5-qrcode"></script>

<style>
    /* Constrain the video element created by HTML5-QRCode library */
    #qrReader video {
        max-width: 100% !important;
        max-height: 200px !important;
        width: auto !important;
        height: auto !important;
    }
</style>

<script>
    let html5QrCode = null;
    let currentGroupId = null;
    let scannedDeliveries = []; // Array to store scanned delivery IDs
    
    // Handle QR scan button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.qr-scan-btn')) {
            const btn = e.target.closest('.qr-scan-btn');
            currentGroupId = btn.dataset.groupId;
            
            // Show the QR scan modal
            const modal = new bootstrap.Modal(document.getElementById('qrScanModal'));
            modal.show();
            
            // Start the QR scanner after a short delay to ensure modal is fully displayed
            setTimeout(startQrScanner, 500);
        }
    });
    
    function startQrScanner() {
        const readerElement = document.getElementById('qrReader');
        
        // Clear any previous scanner
        if (html5QrCode) {
            html5QrCode.stop().catch(err => console.log("Scanner already stopped", err));
        }
        
        // Initialize the scanned list when starting the scanner
        scannedDeliveries = [];
        updateScannedListUI();
        
        // Create new scanner instance
        html5QrCode = new Html5Qrcode("qrReader");
        
        // Configuration for the scanner - similar to logistics_package.php
        const config = { fps: 10, qrbox: 250 };
        
        // Start the camera scan
        html5QrCode.start(
            { facingMode: "environment" }, // Use rear camera if available
            config,
            onScanSuccess
        ).catch(err => {
            console.error("Error starting camera:", err);
            // Fallback to front camera if rear is not available
            html5QrCode.start(
                { facingMode: "user" },
                config,
                onScanSuccess
            ).catch(err => {
                console.error("Error starting front camera:", err);
                document.getElementById('qrResult').textContent = "Camera could not be accessed. Please allow camera permissions.";
                document.getElementById('qrResult').style.display = 'block';
            });
        });
    }
    
    function onScanSuccess(decodedText, decodedResult) {
        // Process the scanned data
        try {
            // Check if the decoded text is a URL containing delivery_id
            if (typeof decodedText === 'string' && decodedText.includes('delivery_id=')) {
                // Parse the URL to extract the delivery_id
                const url = new URL(decodedText);
                const deliveryId = url.searchParams.get('delivery_id');
                
                if (deliveryId) {
                    // Get the DR number for this delivery_id
                    getDrNumberByDeliveryId(deliveryId, currentGroupId);
                } else {
                    document.getElementById('qrResult').textContent = "No delivery_id found in QR code";
                    document.getElementById('qrResult').className = 'alert alert-danger';
                    document.getElementById('qrResult').style.display = 'block';
                }
            } else {
                // If it's not a URL, try to parse as JSON
                const deliveryInfo = JSON.parse(decodedText);
                const deliveryId = deliveryInfo.delivery_id || deliveryInfo.dr_no;
                
                if (deliveryId) {
                    // Add the delivery to the selected group
                    addDeliveryToGroup(deliveryId, currentGroupId);
                } else {
                    document.getElementById('qrResult').textContent = "Invalid QR code format";
                    document.getElementById('qrResult').className = 'alert alert-danger';
                    document.getElementById('qrResult').style.display = 'block';
                }
            }
        } catch (e) {
            // If not JSON and not a URL, treat as plain text (DR number)
            // But first check if it's a URL without proper protocol
            if (decodedText.includes('delivery_id=')) {
                try {
                    // Add a dummy protocol to parse the URL
                    const url = new URL('http://' + decodedText.replace(/^https?:\/\//, ''));
                    const deliveryId = url.searchParams.get('delivery_id');
                    
                    if (deliveryId) {
                        // Get the DR number for this delivery_id
                        getDrNumberByDeliveryId(deliveryId, currentGroupId);
                    } else {
                        document.getElementById('qrResult').textContent = "No delivery_id found in QR code";
                        document.getElementById('qrResult').className = 'alert alert-danger';
                        document.getElementById('qrResult').style.display = 'block';
                    }
                } catch (urlError) {
                    document.getElementById('qrResult').textContent = "Invalid QR code format: " + e.message;
                    document.getElementById('qrResult').className = 'alert alert-danger';
                    document.getElementById('qrResult').style.display = 'block';
                }
            } else {
                addDeliveryToGroup(decodedText, currentGroupId);
            }
        }
    }
    
    // Function to get DR number by delivery ID
    function getDrNumberByDeliveryId(deliveryId, groupId) {
        // Show processing message
        document.getElementById('qrResult').innerHTML = `Fetching delivery information for ID: <strong>${deliveryId}</strong>...`;
        document.getElementById('qrResult').className = 'alert alert-info';
        document.getElementById('qrResult').style.display = 'block';
        
        // Make AJAX request to get the DR number for this delivery ID
        fetch(`script/get_delivery_info.php?delivery_id=${encodeURIComponent(deliveryId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.dr_no) {
                // Successfully got the DR number, add to scanned list
                addToScannedList(data.dr_no, groupId);
            } else {
                document.getElementById('qrResult').innerHTML = ` ${data.message || 'Could not find delivery information'}`;
                document.getElementById('qrResult').className = 'alert alert-danger';
                document.getElementById('qrResult').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching delivery info:', error);
            document.getElementById('qrResult').innerHTML = `Error fetching delivery information: ${error.message}`;
            document.getElementById('qrResult').className = 'alert alert-danger';
            document.getElementById('qrResult').style.display = 'block';
        });
    }
    
    // Function to add a delivery to the scanned list
    function addToScannedList(drNo, groupId) {
        // Check if the delivery is already in the list
        if (!scannedDeliveries.includes(drNo)) {
            scannedDeliveries.push(drNo);
            updateScannedListUI();
            
            document.getElementById('qrResult').innerHTML = `Delivery <strong>${drNo}</strong> added to scan list. Total: <strong>${scannedDeliveries.length}</strong>`;
            document.getElementById('qrResult').className = 'alert alert-success';
            document.getElementById('qrResult').style.display = 'block';
        } else {
            document.getElementById('qrResult').innerHTML = `Delivery <strong>${drNo}</strong> is already in the scan list.`;
            document.getElementById('qrResult').className = 'alert alert-warning';
            document.getElementById('qrResult').style.display = 'block';
        }
    }
    
    // Function to update the UI showing scanned deliveries
    function updateScannedListUI() {
        const listElement = document.getElementById('scannedDeliveriesList');
        const countElement = document.getElementById('scannedCount');
        
        if (scannedDeliveries.length === 0) {
            listElement.innerHTML = '<li class="list-group-item d-flex justify-content-between align-items-center text-muted">No deliveries scanned yet</li>';
            document.getElementById('clearScannedBtn').style.display = 'none';
            document.getElementById('finishScanningBtn').style.display = 'none';
        } else {
            let listHTML = '';
            scannedDeliveries.forEach((drNo, index) => {
                listHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>DR: ${drNo}</span>
                        <button class="btn btn-sm btn-outline-danger remove-delivery-btn" data-index="${index}">
                            <i class="bi bi-x"></i>
                        </button>
                    </li>
                `;
            });
            listElement.innerHTML = listHTML;
            
            // Show the clear and finish buttons
            document.getElementById('clearScannedBtn').style.display = 'inline-block';
            document.getElementById('finishScanningBtn').style.display = 'inline-block';
        }
        
        // Update count
        countElement.textContent = scannedDeliveries.length;
        
        // Add event listeners to remove buttons
        document.querySelectorAll('.remove-delivery-btn').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                removeFromScannedList(index);
            });
        });
    }
    
    // Function to remove a delivery from the scanned list
    function removeFromScannedList(index) {
        if (index >= 0 && index < scannedDeliveries.length) {
            const removedDrNo = scannedDeliveries.splice(index, 1)[0];
            document.getElementById('qrResult').innerHTML = `Delivery <strong>${removedDrNo}</strong> removed from scan list.`;
            document.getElementById('qrResult').className = 'alert alert-info';
            document.getElementById('qrResult').style.display = 'block';
            
            updateScannedListUI();
        }
    }
    
    // Function to clear all scanned deliveries
    function clearScannedList() {
        if (scannedDeliveries.length > 0) {
            if (confirm(`Are you sure you want to clear all ${scannedDeliveries.length} scanned deliveries?`)) {
                scannedDeliveries = [];
                updateScannedListUI();
                
                document.getElementById('qrResult').innerHTML = 'All scanned deliveries cleared.';
                document.getElementById('qrResult').className = 'alert alert-info';
                document.getElementById('qrResult').style.display = 'block';
            }
        }
    }
    
    // Function to add all scanned deliveries to the group
    function addAllToGroup() {
        if (scannedDeliveries.length === 0) {
            document.getElementById('qrResult').innerHTML = 'No deliveries to add. Please scan some deliveries first.';
            document.getElementById('qrResult').className = 'alert alert-warning';
            document.getElementById('qrResult').style.display = 'block';
            return;
        }
        
        if (!confirm(`Are you sure you want to add all ${scannedDeliveries.length} deliveries to the group?`)) {
            return;
        }
        
        // Show processing message
        document.getElementById('qrResult').innerHTML = `Validating and adding ${scannedDeliveries.length} deliveries to group...`;
        document.getElementById('qrResult').className = 'alert alert-info';
        document.getElementById('qrResult').style.display = 'block';
        
        // Create form data to send to the server
        const formData = new FormData();
        scannedDeliveries.forEach(drNo => {
            formData.append('selected_dr[]', drNo);
        });
        
        // Get the group name from the button that opened the modal
        const groupName = document.querySelector(`.qr-scan-btn[data-group-id="${currentGroupId}"]`).dataset.groupName;
        formData.append('group_name', groupName);
        
        // Send AJAX request to add all deliveries to the group
        fetch('script/add_billing_grouped.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('qrResult').innerHTML = `Successfully added ${scannedDeliveries.length} deliveries to group!`;
                document.getElementById('qrResult').className = 'alert alert-success';
                
                // Clear the scanned list
                scannedDeliveries = [];
                updateScannedListUI();
                
                // Stop the scanner after successful scan
                if (html5QrCode) {
                    html5QrCode.stop().catch(err => console.log("Error stopping scanner", err));
                }
                
                // Refresh the page after a short delay to show the updated groups
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                document.getElementById('qrResult').innerHTML = `Error: ${data.message}`;
                document.getElementById('qrResult').className = 'alert alert-danger';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('qrResult').innerHTML = `Error adding deliveries: ${error.message}`;
            document.getElementById('qrResult').className = 'alert alert-danger';
        });
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
    
    // Add event listener for clear scanned button
    document.getElementById('clearScannedBtn').addEventListener('click', function() {
        clearScannedList();
    });
    
    // Add event listener for finish scanning button
    document.getElementById('finishScanningBtn').addEventListener('click', function() {
        addAllToGroup();
    });
    
    // Add event listener for modal close to stop scanner and reset scanned list
    document.getElementById('qrScanModal').addEventListener('hidden.bs.modal', function () {
        if (html5QrCode) {
            html5QrCode.stop().catch(err => console.log("Error stopping scanner", err));
        }
        // Reset scanned list when modal closes
        scannedDeliveries = [];
        updateScannedListUI();
    });
    
    // This function is kept for backward compatibility if needed elsewhere
    function addDeliveryToGroup(deliveryId, groupId) {
        // Add to scanned list instead of directly to group
        addToScannedList(deliveryId, groupId);
    }
    
    // Clean up the scanner when modal is closed
    document.getElementById('qrScanModal').addEventListener('hidden.bs.modal', function () {
        if (html5QrCode) {
            html5QrCode.stop().catch(err => console.log("Error stopping scanner", err));
        }
    });
</script>