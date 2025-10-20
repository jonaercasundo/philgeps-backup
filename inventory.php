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
<?php else:?>
    <div>
        <?php endif;?>
    <!-- 2. RIGHT MAIN CONTENT AREA (9 Columns wide on medium/large screens) -->
    
        <!-- Large Main Content/Display Area -->
        <div class="flex-grow-1">
            <div class="bg-white px-4 rounded shadow-sm h-100">
                 <div class="d-flex align-items-center mb-3">
                       <h5 class="mb-0 text-dark">Inventory List</h5>
                </div>
             

                <table id="inventoryTable" class="table table-bordered table-striped">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Inventory ID</th>
                            <th>Warehouse</th>
                            <th>Item</th>
                            <th>Quantity</th>
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
    }

    // Update inventory
    function updateInventory() {
        const formData = new FormData(document.getElementById('editForm'));
        
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

// Reject inventory
function rejectInventory() {
    const formData = new FormData(document.getElementById('rejectForm'));
    
    fetch('script/reject_inventory.php', {
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
                    data: "qty", 
                    className: "text-center",
                    orderable: true
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
                            <div class="btn-group" role="group">
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
    html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
</script>
