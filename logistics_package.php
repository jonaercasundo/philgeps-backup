<?php 
$is_logistics_package_page = true;
require "template/header.php"; 
require "config/db.php";
require "script/role_auth.php";

// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Office Admin', 'Office Coordinator', 'Warehouse Admin', 'Warehouse Coordinator'];
redirectIfNotAuthorized($allowed_roles, 'index.php');

try {
    $search_dr = trim($_GET['search_dr'] ?? '');
    $limit = 10;
    $page = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    // Base queries and params
    $count_query = "SELECT COUNT(DISTINCT dr_no) FROM deliveries";
    $dr_query = "SELECT DISTINCT dr_no FROM deliveries";
    $params = [];
    $count_params = [];

    if (!empty($search_dr)) {
        $count_query .= " WHERE dr_no LIKE :search_dr";
        $dr_query .= " WHERE dr_no LIKE :search_dr";
        $count_params[':search_dr'] = "%" . $search_dr . "%";
    }
    
    // Count total unique deliveries
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($count_params);
    $total_rows = $stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Add order and pagination to dr_query
    $dr_query .= " ORDER BY status, delivery_date LIMIT :limit OFFSET :offset";
    
    // 1. Fetch the DR numbers for the current page
    $dr_stmt = $pdo->prepare($dr_query);
    if (!empty($search_dr)) {
        $dr_stmt->bindValue(':search_dr', "%" . $search_dr . "%", PDO::PARAM_STR);
    }
    $dr_stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $dr_stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $dr_stmt->execute();
    $dr_nos_for_page = $dr_stmt->fetchAll(PDO::FETCH_COLUMN);

    $deliveries = [];
    if (!empty($dr_nos_for_page)) {
        // 2. Fetch all data for those DR numbers
        $placeholders = implode(',', array_fill(0, count($dr_nos_for_page), '?'));
        
        $stmt = $pdo->prepare("
            SELECT 
                d.delivery_id,
                p_proj.project_name,
                s.school_id,
                s.school_name,
                s.address,
                d.package_type,
                d.dr_no,
                d.delivery_date,
                d.status,
                k.keystage_num,
                k.description,
                l.lot_name,
                w.warehouse_id,
                w.warehouse_name,
                pkg.package_id,
                pkg.package_num,
                GROUP_CONCAT(item.item_name, ' (', pc.qty, ')' SEPARATOR '<br>') AS package_content,
                ps.status as package_status,
                ps.package_status_id
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
        ");
        $stmt->execute($dr_nos_for_page);
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $grouped_deliveries = [];
    foreach ($deliveries as $row) {
        $dr = $row['dr_no'];
        if (!isset($grouped_deliveries[$dr])) {
            $grouped_deliveries[$dr] = [
                'dr_no' => $dr,
                'keystage_num' => $row['keystage_num'],
                'description' => $row['description'],
                'lot_name' => $row['lot_name'],
                'project_name' => $row['project_name'],
                'school_id' => $row['school_id'],
                'school_name' => $row['school_name'],
                'address' => $row['address'],
                'delivery_date' => $row['delivery_date'],
                'status' => $row['status'],
                'packages' => []
            ];
        }
        if ($row['package_id']) {
            $grouped_deliveries[$dr]['packages'][] = $row;
        }
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<div class="row g-0 h-100">
    <?php 
    $user_role = $_SESSION['role'];
    if ($user_role == "Office Coordinator" || $user_role == "Warehouse Admin" || $user_role == "Warehouse Coordinator" || $user_role == "Super Admin" || $user_role == "Office Admin"): 
    ?>
    <!-- 1. LEFT SIDEBAR (3 Columns wide on medium/large screens) -->
    <div class="col-md-3 border-end d-flex flex-column vh-100">

        <!-- Header: fixed height -->
        <div class="px-3 d-flex justify-content-between align-items-center py-2 border-bottom flex-shrink-0">
            <h5 class="mb-0 text-dark opacity-75">Add Items</h5> 
        </div>

        <!-- QR Reader container: fixed height and center content -->
        <div class="d-flex justify-content-center align-items-center flex-shrink-0 py-3 border-bottom" style="height: 30vh;">
            <div id="reader"></div>
        </div>

        <!-- USB Scanner Input -->
        <div class="px-3 py-2 border-top flex-shrink-0">
            <input type="text" id="usbScannerInput" class="form-control" placeholder="Scan with USB scanner here" autofocus>
        </div>


        <!-- Scrollable table area: fills remaining space -->
        <div class="flex-grow-1 overflow-auto px-3">
            <table id="itemTable" class="table table-bordered table-striped mb-0">
            <thead class="table-dark text-center">
                <tr>
                <th>Item</th>
                <th>Quantity</th>
                </tr>
            </thead>
            <tbody id="itemBodytable">
                <td colspan="2" class="text-center text-muted">No packages scanned yet</td>
            </tbody>
            </table>
        </div>

        <!-- Footer button: fixed height -->
        <div class="px-3 py-2 border-top flex-shrink-0">
            <button class="btn btn-outline-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#addModal">Add Items</button>
        </div>
    </div>
    <div class="col-md-9 d-flex flex-column">
    <?php else: ?>
    <div class="col-12">
    <?php endif; ?>
        <div class="flex-grow-1">
            <div class="bg-white px-4 py-4 rounded shadow-sm h-100">
                <h5 class="mb-3 text-dark">Deliveries List</h5>
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
                                <td colspan="4">
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
                                    $row_class = $is_inactive ? 'table-secondary' : '';
                                    $row_style = $is_inactive ? 'opacity: 0.6;' : '';
                                ?>
                                    <tr style="<?= $row_style ?>">
                                        <td>
                                        <?php 
                                            $total_packages_in_delivery = count($dr_group['packages']);
                                            ?>
                                            Package #<?= htmlspecialchars($package_index + 1) ?> out of <?= htmlspecialchars($total_packages_in_delivery) ?><br>
                                            Status: <span class="fw-bold"><?= htmlspecialchars(ucfirst(strtolower($package['package_status'] ?? 'Pending'))) ?></span>
                                        </td>
                                        <td><?= $package['package_content'] ?? '<em>No items</em>' ?></td>
                                        <td class="text-center align-middle">
                                            <?php if (!$is_inactive): ?>
                                                <a href="scan.php?id=<?= htmlspecialchars($package['package_status_id']) ?>&delivery_id=<?= htmlspecialchars($package['delivery_id']) ?>"target="_blank" class="btn btn-info btn-sm">Add Photos</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td></td>
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

<?php include "partials/inventory_modals.php"; ?>
<?php require "template/footer.php"; ?>

<!-- QR script -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    function simulateScan(num) {
        const testQRData = JSON.stringify({
            action: "addPackage",
            package: num
        });
        onScanSuccess(testQRData);
    }

    // Handle USB scanner input (package_id only)
    document.getElementById('usbScannerInput').addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            const scannedCode = this.value.trim();
            this.value = ""; // clear input

            if (!scannedCode) return;

            console.log("Scanned Package ID:", scannedCode);

            // Directly call addPackage without JSON wrapping
            onScanSuccess({ package: scannedCode });
        }
    });

    // Global tracking of scanned packages
    const addedPackages = [];

    // Handle package scan success (QR or USB)
    function onScanSuccess(data) {
        const packageID = data.package;

        if (!packageID) {
            console.warn("Invalid scan data:", data);
            return;
        }

        if (!addedPackages.includes(packageID)) {
            addedPackages.push(packageID);
            console.log("Added package:", packageID);
            loadItemsView();
        } else {
            console.log("Package already scanned:", packageID);
        }
    }


    function loadItemsView() {
        const tbody = document.getElementById('itemBodytable');
        if (!tbody) {
            console.error("itemBodytable not found");
            return;
        }
        tbody.innerHTML = '';

        if (addedPackages.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="2" class="text-center text-muted">No packages scanned yet</td>`;
            tbody.appendChild(tr);
            return;
        }

        console.log("Loading Items View for packages:", addedPackages);

        addedPackages.forEach(pkg => {
            fetch(`script/get_package.php?package_id=${encodeURIComponent(pkg)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.items)) {
                    // Add a header row for this package
                    const headerRow = document.createElement('tr');
                    headerRow.innerHTML = `<td style="font-weight:bold; background:#f0f0f0;">Package: LOT ${data.package.lot_name} KEYSTAGE ${data.package.keystage_name} KEYSTAGE ${data.package.description}</td><td contenteditable="true" data-package-id="${data.package.package_id}" onblur="changeQuantity(this)">1</td>`;
                    tbody.appendChild(headerRow);

                    // Add each item row for this package
                    data.items.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-item-id', item.item_id);  // <-- add this
                        tr.innerHTML = `<td>${item.item_name || ''}</td><td data-base-qty="${item.qty || ''}" class="item-${data.package.package_id}">${item.qty || ''}</td>`;
                        tbody.appendChild(tr);
                    });

                } else {
                    console.warn("No items for package", pkg, data);
                }
            })
            .catch(err => console.error("Fetch error (items):", err));
        });
    }
    function changeQuantity(el) {
        const packageId = el.dataset.packageId; // use data-package-id in your td
        const items = document.querySelectorAll(".item-" + packageId);

        const newQty = parseInt(el.textContent.trim()) || 0;

        items.forEach(item => {
            // Assuming item.innerHTML holds the base quantity
            const baseQty = parseInt(item.dataset.baseQty) || 1; // store original qty in data attribute
            item.textContent = baseQty * newQty;
        });
    }

    // Add inventory - bulk submit all items
    function addForm(type, scriptUrl) {
        // Collect all scanned items from the table
        const items = [];
        
        // Get all rows from the item table
        const rows = document.querySelectorAll('#itemBodytable tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 2) {
                const quantity = parseInt(cells[1].textContent.trim());
                const itemId = row.getAttribute('data-item-id');
                
                if (itemId && !isNaN(quantity) && quantity > 0) {
                    items.push({
                        warehouse_id: 1,
                        item_id: parseInt(itemId),
                        quantity: quantity
                    });
                }
            }
        });

        if (items.length === 0) {
            alert('No valid items found to add to inventory.');
            return;
        }

        const password = document.querySelector('#addForm input[name="password"]').value;
        if (!password) {
            alert('Please enter your password.');
            return;
        }

        console.log('Submitting items:', items);

        // Submit ALL items in one bulk request
        const formData = new FormData();
        formData.append('items_json', JSON.stringify(items));
        formData.append('password', password);
        // Show loading state
        const submitBtn = document.querySelector('#addModal .btn-primary');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Adding Items...';
        submitBtn.disabled = true;

        fetch('script/add_inventory.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            if (data.success) {
                // Clear the scanned items table
                document.getElementById('itemBodytable').innerHTML = '<tr><td colspan="2" class="text-center text-muted">No packages scanned yet</td></tr>';
                // Clear the scanned packages
                addedPackages.length = 0;
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                if (modal) modal.hide();
                
                // Use toast from response if available, otherwise use message
                const toastMessage = data.toast || data.message;
                const toastType = data.type || 'success';
                window.location.href = 'inventory.php?toast=' + encodeURIComponent(toastMessage) + '&type=' + toastType;
            } else {
                // If there's a toast in response, redirect to show toast
                if (data.toast) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                    if (modal) modal.hide();
                    window.location.href = 'inventory.php?toast=' + encodeURIComponent(data.toast) + '&type=' + (data.type || 'danger');
                } else {
                    // Otherwise show alert
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            console.error('Network error:', error);
            alert('Error adding items: ' + error.message);
        });
    }

    // Start scanner
    const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>