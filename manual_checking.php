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

    // Count total deliveries with status 'delivered'
    $stmt = $pdo->query("SELECT COUNT(*) FROM deliveries WHERE status = 'delivered'");
    $total_rows = $stmt->fetchColumn();
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
    WHERE d.status = 'delivered'
      AND bg.dr_no IS NULL
    ORDER BY d.delivery_date DESC
    LIMIT :limit OFFSET :offset
");

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
        <!-- Scrollable content area -->
        <div class="flex-fill" style="max-height: 60vh; overflow-y: auto;">
            <div class="p-3">
                <?php if (empty($grouped_summary)): ?>
                    <div class="text-center text-muted py-4">
                        <small>No billing groups yet</small>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($grouped_summary as $group): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?= htmlspecialchars($group['group_name']) ?></strong>
                                    <span class="badge bg-primary"><?= $group['dr_count'] ?></span>
                                </div>

                                <?php if (!empty($group['dr_numbers'])): ?>
                                    <div class="table table-sm mb-0">
                                        <?php foreach ($group['dr_numbers'] as $dr_no): ?>
                                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span>DR No: <?= htmlspecialchars($dr_no) ?></span>
                                                <div>
                                                    <button class="btn btn-danger btn-sm" data-dr="<?= htmlspecialchars($dr_no) ?>">
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

                <!-- Table - EXACT SAME STRUCTURE as deliveries.php -->
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
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
                </div>

                <!-- Pagination -->
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

    // Show modal with selected deliveries
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

document.addEventListener('click', function(e) {
  const editBtn = e.target.closest('.edit-dr');
  const removeBtn = e.target.closest('.remove-dr');

  if (editBtn) {
    const drNo = editBtn.dataset.dr;
    const checkbox = document.querySelector(`.dr-checkbox[value="${drNo}"]`);
    if (!checkbox) return alert(`DR ${drNo} not found on this page`);
    const row = checkbox.closest('tr');
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    row.classList.add('table-warning');
    setTimeout(() => row.classList.remove('table-warning'), 2000);
  }

  if (removeBtn) {
    const drNo = removeBtn.dataset.dr;
    if (confirm(`Are you sure you want to remove DR ${drNo} from this group?`)) {
      // TODO: Add AJAX or PHP call to remove DR from group
      alert(`Removed DR ${drNo}`);
    }
  }
});

</script>