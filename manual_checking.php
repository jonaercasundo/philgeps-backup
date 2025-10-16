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
    die("DB Error: " . $e->getMessage());
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
                        <div class="list-group" id="billingGroupsList">
                            <?php foreach ($grouped_summary as $group): ?>
                                <div class="list-group-item group-item"
                                    data-group-name="<?= htmlspecialchars(strtolower($group['group_name'])) ?>"
                                    data-dr-numbers="<?= htmlspecialchars(strtolower(implode(' ', $group['dr_numbers']))) ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong><?= htmlspecialchars($group['group_name']) ?></strong>
                                            <span class="badge bg-success"><?= $group['dr_count'] ?></span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-success edit-group-btn"data-group-name="<?= htmlspecialchars($group['group_name']) ?>"
                                                data-group-id="<?= htmlspecialchars($group['group_id']) ?>"
                                                    data-group-id="<?= htmlspecialchars($group['group_id']) ?>"
                                                    title="Add more to Group">
                                                <i class="bi bi-plus"></i>
                                        </button>
                                    </div>

                                    <?php if (!empty($group['dr_numbers'])): ?>
                                        <div class="table table-sm mb-0">
                                            <?php foreach ($group['dr_numbers'] as $dr_no): ?>
                                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                    <span>DR No: <?= htmlspecialchars($dr_no) ?></span>
                                                    <div>
                                                        <button class="btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center px-2 py-1" data-dr="<?= htmlspecialchars($dr_no) ?>">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
                        + Add to Group
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
                            <i class="bi bi-search"></i> Search
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

    // Handle edit group button click (EDIT mode)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-group-btn')) {
            const btn = e.target.closest('.edit-group-btn');
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
            console.error('Error:', error);
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
    const billingGroupsList = document.getElementById('billingGroupsList');
    const noGroupResults = document.getElementById('noGroupResults');

    if (groupSearchInput && billingGroupsList) {
        groupSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const groupItems = billingGroupsList.querySelectorAll('.group-item');
            let visibleCount = 0;

            groupItems.forEach(item => {
                const groupName = item.dataset.groupName;
                const drNumbers = item.dataset.drNumbers;
                
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
                billingGroupsList.style.display = 'none';
                noGroupResults.style.display = 'block';
            } else {
                billingGroupsList.style.display = '';
                noGroupResults.style.display = 'none';
            }

            // Show/hide clear button
            clearGroupSearchBtn.style.display = searchTerm ? 'block' : 'none';
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