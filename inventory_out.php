<?php
    $is_warehouse_page = true;
    require "template/header.php";
    require "script/role_auth.php";
    require "config/db.php";

    // roles allowed to access this page
    $allowed_roles = ['Super Admin', 'Admin', 'Office Admin', 'Office Coordinator','Warehouse Admin', 'Warehouse Coordinator'];

    // redirect
    redirectIfNotAuthorized($allowed_roles, 'index.php');


?>

<!-- Main Full-Screen Container -->
<div class="row g-0 h-100">
    <?php if($_SESSION['role'] == "Warehouse Coordinator" || $_SESSION['role'] == "Warehouse Admin"): ?>
    <!-- 1. LEFT SIDEBAR (3 Columns wide on medium/large screens) -->
    <div class="col-md-3 border-end d-flex flex-column vh-100">

        <!-- Header: fixed height -->
        <div class="px-3 d-flex justify-content-between align-items-center py-2 border-bottom flex-shrink-0">
            <h5 class="mb-0 text-dark opacity-75">Subtract Package Items</h5>
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
            <button class="btn btn-outline-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#subtractModal">Subtract Package Items</button>
        </div>
    </div>
    <div class="col-md-9 d-flex flex-column">
<?php else:?>
    <div>
        <?php endif;?>
    <!-- 2. RIGHT MAIN CONTENT AREA (9 Columns wide on medium/large screens) -->

        <!-- Large Main Content/Display Area -->
        <div class="flex-grow-1">
            <div class="bg-white px-4 rounded shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
    <!-- Left group: title + printer -->
    <div class="d-flex align-items-center">
        <h5 class="mb-0 text-dark">Inventory Subtraction</h5>
        <?php if (in_array($_SESSION['role'], ['Warehouse Admin', 'Office Admin'])): ?>
        <a href="report/print_inventory_warehouse.php" class="text-decoration-none text-dark ms-3" target="_blank">
            <i class="bi bi-printer"></i>
        </a>
        <?php endif; ?>
    </div>

    <!-- Right button -->
    <a href="inventory-items.php" class="btn btn-primary">Subtract Specific Item</a>
</div>

                <table id="inventoryTable" class="table table-bordered table-striped">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Inventory ID</th>
                            <th>Warehouse</th>
                            <th>Item</th>
                            <th>Percentage</th>
                            <th>Quantity</th>
                            <th>Order Quantity</th>
                            <th>Status</th>
                            <?php if($_SESSION['role'] != "Warehouse Coordinator"){
                                echo "<th>Action</th>";
                            } ?>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "partials/inventory_modals.php"?>

<?php require "template/footer.php"; ?>

<!-- CRUD Operations -->
<script>
    // Function to set the inventory ID for deletion
    function updateDeleteURL(inventoryId) {
        const deleteInput = document.getElementById('delete_inventory_id');
        if (deleteInput) {
            deleteInput.value = inventoryId;
        }

        const sourcePageInput = document.getElementById('delete_source_page');
        if (sourcePageInput) {
            sourcePageInput.value = `inventory.php?inventory_id=${inventoryId}`;
        }
    }

    // Update Edit Modal for inventory
    function updateEdit(inventoryId, itemName, warehouseName, quantity) {
        document.getElementById("edit_inventory_id").value = inventoryId;
        document.getElementById("edit_item_name").value = itemName.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        document.getElementById("edit_warehouse_name").value = warehouseName.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        document.getElementById("edit_quantity").value = quantity;

        // Store original quantity for change detection
        document.getElementById("edit_quantity").setAttribute('data-original-value', quantity);

        // Reset remarks fields when opening modal
        document.getElementById("remarks_dropdown").value = "";
        document.getElementById("custom_remarks").value = "";

        // Ensure custom remarks is hidden
        const customRemarksCollapse = new bootstrap.Collapse(document.getElementById('customRemarksCollapse'), {
            toggle: false
        });
        customRemarksCollapse.hide();
    }

    // Update inventory
    function updateInventory() {
        const formData = new FormData(document.getElementById('editForm'));
        const originalQuantity = parseInt(document.getElementById("edit_quantity").getAttribute('data-original-value') || 0);
        const newQuantity = parseInt(document.getElementById("edit_quantity").value);

        // Get remarks value and add to form data
        const dropdown = document.getElementById('remarks_dropdown');
        let remarks = '';

        if (dropdown.value === 'custom') {
            remarks = document.getElementById('custom_remarks').value.trim();
        } else {
            remarks = dropdown.value;
        }

        // Check if quantity changed and no remarks provided
        const quantityChanged = (originalQuantity !== newQuantity);
        if (quantityChanged && !remarks) {
            alert("You've changed the quantity. Please add remarks about this change before proceeding.");

            // Focus on remarks dropdown and return to let user add remarks
            dropdown.focus();
            return;
        }

        // Add remarks to form data
        formData.append('remarks', remarks);

        fetch('script/update_inventory.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = 'inventory.php?toast=Inventory Updated&type=success';
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Response:', text);
                alert('Error: Invalid response from server');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error);
        });
    }


    // Delete inventory
    function deleteInventory() {
        const formData = new FormData(document.getElementById('deleteForm'));

        fetch('script/delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = 'inventory.php?toast=Inventory Deleted&type=success';
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Response:', text);
                alert('Error: Invalid response from server');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error);
        });
    }

    // Update Accept Modal
    function updateAcceptId(inventoryId) {
        document.getElementById("accept_inventory_id").value = inventoryId;
    }

    // Update Reject Modal
    function updateRejectId(inventoryId) {
        document.getElementById("reject_inventory_id").value = inventoryId;
    }

    // Accept inventory
    function acceptInventory() {
        const formData = new FormData(document.getElementById('acceptForm'));

        fetch('script/accept_inventory.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = 'inventory.php?toast=' + encodeURIComponent(data.message) + '&type=success';
                } else {
                    // Redirect with error toast instead of alert
                    window.location.href = 'inventory.php?toast=' + encodeURIComponent(data.message) + '&type=danger';
                }
            } catch (e) {
                console.error('Response:', text);
                window.location.href = 'inventory.php?toast=Invalid response from server&type=danger';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'inventory.php?toast=Network error: ' + encodeURIComponent(error.message) + '&type=danger';
        });
    }

    // Update Reject Modal
    function updateRejectId(inventoryId) {
        document.getElementById("reject_inventory_id").value = inventoryId;

        // Reset reject remarks fields when opening modal
        document.getElementById("reject_remarks_dropdown").value = "";
        document.getElementById("reject_custom_remarks").value = "";

        // Ensure reject custom remarks is hidden
        const rejectCustomRemarksCollapse = new bootstrap.Collapse(document.getElementById('reject_customRemarksCollapse'), {
            toggle: false
        });
        rejectCustomRemarksCollapse.hide();
    }

    // Reject inventory
    function rejectInventory() {
        const formData = new FormData(document.getElementById('rejectForm'));

        // Get reject remarks value
        const dropdown = document.getElementById('reject_remarks_dropdown');
        let remarks = '';

        if (dropdown && dropdown.value === 'custom') {
            const customRemarks = document.getElementById('reject_custom_remarks');
            remarks = customRemarks ? customRemarks.value.trim() : '';
        } else if (dropdown) {
            remarks = dropdown.value;
        }

        // Add remarks to form data
        formData.append('remarks', remarks);

        fetch('script/reject_inventory.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    // Close the reject modal
                    const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
                    rejectModal.hide();

                    window.location.href = 'inventory.php?toast=' + encodeURIComponent(data.message) + '&type=success';
                } else {
                    // Redirect with error toast instead of alert
                    window.location.href = 'inventory.php?toast=' + encodeURIComponent(data.message) + '&type=danger';
                }
            } catch (e) {
                console.error('Response:', text);
                window.location.href = 'inventory.php?toast=Invalid response from server&type=danger';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'inventory.php?toast=Network error: ' + encodeURIComponent(error.message) + '&type=danger';
        });
    }

    // Handle dropdown change to show/hide custom remarks for REJECT modal
    document.addEventListener('DOMContentLoaded', function() {
        // For Edit Modal
        const remarksDropdown = document.getElementById('remarks_dropdown');
        if (remarksDropdown) {
            remarksDropdown.addEventListener('change', function() {
                const customRemarksCollapse = document.getElementById('customRemarksCollapse');
                const bsCollapse = new bootstrap.Collapse(customRemarksCollapse, {
                    toggle: false
                });

                if (this.value === 'custom') {
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                    document.getElementById('custom_remarks').value = '';
                }
            });
        }

        // For Reject Modal
        const rejectRemarksDropdown = document.getElementById('reject_remarks_dropdown');
        if (rejectRemarksDropdown) {
            rejectRemarksDropdown.addEventListener('change', function() {
                const rejectCustomRemarksCollapse = document.getElementById('reject_customRemarksCollapse');
                const bsCollapse = new bootstrap.Collapse(rejectCustomRemarksCollapse, {
                    toggle: false
                });

                if (this.value === 'custom') {
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                    document.getElementById('reject_custom_remarks').value = '';
                }
            });
        }
    });

</script>


<!-- QR script -->
<script src="https://unpkg.com/html5-qrcode"></script>

<!-- Table Scripts -->
<script>
    $(document).ready(function() {
        // Initialize the DataTables instance for the inventory table
        const table = $('#inventoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "script/get_inventory.php",
                type: "GET"
            },
            columns: [
                {
                    data: "inventory_id",
                    className: "text-center",
                    orderable: true
                },
                {
                    data: "warehouse_name",
                    className: "text-center",
                    orderable: true
                },
                {
                    data: "item_name",
                    className: "text-center",
                    orderable: true
                },
                {
                    data: null,
                    className: "text-center",
                    orderable: true,
                    render: function(data, type, row) {
                        const qty = parseInt(row.qty) || 0;
                        const expectedQty = parseInt(row.expected_qty) || 0;
                        if (expectedQty === 0) {
                            return '0%';
                        }
                        const percentage = (qty / expectedQty) * 100;
                        return percentage.toFixed(2) + '%';
                    }
                },
                {
                    data: "qty",
                    className: "text-center",
                    orderable: true
                },
                {
                    data: "expected_qty",
                    className: "text-center",
                    orderable: true,
                    render: function(data, type, row) {
                        // Format the expected quantity with commas for thousands
                        return data ? parseInt(data).toLocaleString() : '0';
                    }
                },
                {
                    data: "inventory_status",
                    className: "text-center",
                    orderable: true,
                    render: function(data, type, row) {
                        const statusClass = data === 'Approved' ? 'badge bg-success' : 'badge bg-warning text-dark';
                        return `<span class="${statusClass}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    className: "text-center",
                    orderable: false,
                    render: function(data, type, row) {
                        const itemName = row.item_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                        const warehouseName = row.warehouse_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');

                        let actionButtons = '';
                    if('<?= $_SESSION['role']?>' != 'Warehouse Coordinator' && '<?= $_SESSION['role']?>' != 'Office Coordinator'){
                        if (row.inventory_status === 'For Approval') {
                            // Show Accept and Reject buttons for pending items
                            actionButtons = `
                                <button class="btn btn-success btn-sm"
                                        onclick="updateAcceptId(${row.inventory_id})"
                                        data-bs-toggle="modal"
                                        data-bs-target="#acceptModal"
                                        title="Accept Item">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button class="btn btn-danger btn-sm"
                                        onclick="updateRejectId(${row.inventory_id})"
                                        data-bs-toggle="modal"
                                        data-bs-target="#rejectModal"
                                        title="Reject Item">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            `;
                        } else {
                            if('<?= $_SESSION['role']?>' == 'Super Admin' ||'<?= $_SESSION['role']?>' == 'Office Admin'){
                                // Show Edit and Delete for approved items
                                actionButtons = `
                                    <button class="btn btn-warning btn-sm"
                                            onclick="updateEdit(${row.inventory_id}, '${itemName}', '${warehouseName}', ${row.qty})"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            title="Edit Item">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm"
                                            onclick="updateDeleteURL(${row.inventory_id})"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            title="Delete Item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                `;
                            }
                        }
                    }
                        return `
                            <span style="display:none;" id="item${row.inventory_id}">${itemName}</span>
                            <span style="display:none;" id="warehouse${row.inventory_id}">${warehouseName}</span>
                            <span style="display:none;" id="quantity${row.inventory_id}">${row.qty}</span>
                            <div class="text-center align-middle" role="group">
                                ${actionButtons}
                            </div>
                        `;
                    }
                }
            ],
            scrollY: "53vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[0, 'desc']],
            language: {
                emptyTable: "No inventory records found",
                zeroRecords: "No matching inventory records found"
            }
        });
    });
</script>

<!-- QR -->
<script>
    function simulateScan(num) {
        const testQRData = JSON.stringify({
            action: "subtractPackage",
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

        // Directly call subtractPackage without JSON wrapping
        onScanSuccess({ package: scannedCode });
    }
});

// Global tracking of scanned packages
const subtractedPackages = [];

// Handle package scan success (QR or USB)
function onScanSuccess(data) {
    const packageID = data.package;

    if (!packageID) {
        console.warn("Invalid scan data:", data);
        return;
    }

    if (!subtractedPackages.includes(packageID)) {
        subtractedPackages.push(packageID);
        console.log("Subtracted package:", packageID);
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

        if (subtractedPackages.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="2" class="text-center text-muted">No packages scanned yet</td>`;
            tbody.appendChild(tr);
            return;
        }

        console.log("Loading Items View for packages:", subtractedPackages);

        subtractedPackages.forEach(pkg => {
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

    // Subtract inventory - bulk submit all items
    function subtractForm(type, scriptUrl) {
        // Collect all scanned items from the table
        const items = [];

        // Get all rows from the item table
        const rows = document.querySelectorAll('#itemBodytable tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 2) {
                const quantity = parseInt(cells[1].textContent.trim());
                const itemId = row.getAttribute('data-item-id');

                if (itemId && !isNaN(quantity) && quantity > 0) {
                    items.push({
                        item_id: parseInt(itemId),
                        quantity: quantity
                    });
                }
            }
        });

        if (items.length === 0) {
            alert('No valid items found to subtract from inventory.');
            return;
        }

        const password = document.querySelector('#subtractForm input[name="password"]').value;
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
        const submitBtn = document.querySelector('#subtractModal .btn-primary');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Subtracting Items...';
        submitBtn.disabled = true;

        fetch('script/subtract_inventory.php', {
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
                subtractedPackages.length = 0;
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('subtractModal'));
                if (modal) modal.hide();

                // Use toast from response if available, otherwise use message
                const toastMessage = data.toast || data.message;
                const toastType = data.type || 'success';
                window.location.href = 'inventory_out.php?toast=' + encodeURIComponent(toastMessage) + '&type=' + toastType;
            } else {
                // Check if there are insufficient items to show in modal
                if (data.insufficient_items && data.insufficient_items.length > 0) {
                    // Populate the insufficient items table
                    const tbody = document.getElementById('insufficientItemBody');
                    tbody.innerHTML = ''; // Clear previous entries
                    
                    data.insufficient_items.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${item.item_name}</td>
                            <td class="text-center">${item.requested_qty}</td>
                            <td class="text-center">${item.available_qty}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                    
                    // Close the subtract modal and show insufficient inventory modal
                    const subtractModal = bootstrap.Modal.getInstance(document.getElementById('subtractModal'));
                    if (subtractModal) subtractModal.hide();
                    
                    const insufficientModal = new bootstrap.Modal(document.getElementById('insufficientInventoryModal'));
                    insufficientModal.show();
                } else if (data.toast) {
                    // If there's a toast in response, redirect to show toast
                    const modal = bootstrap.Modal.getInstance(document.getElementById('subtractModal'));
                    if (modal) modal.hide();
                    window.location.href = 'inventory_out.php?toast=' + encodeURIComponent(data.toast) + '&type=' + (data.type || 'danger');
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
            alert('Error subtracting items: ' + error.message);
        });
    }

    // Start scanner
    html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>

<!-- auto focus on scanner input -->
<script>
setTimeout(() => {
    const scanner = document.getElementById('usbScannerInput');
    if (scanner) scanner.focus();
}, 100);
</script>