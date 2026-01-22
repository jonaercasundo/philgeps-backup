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
            <h5 class="mb-0 text-dark opacity-75">Add Specific Items</h5> 
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
                <td colspan="2" class="text-center text-muted">No items scanned yet</td>
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
    // Global tracking of scanned items
    const scannedItems = {}; // Using an object to store item ID, name, and quantity

    function simulateScan(num) {
        const testQRData = JSON.stringify({
            action: "addItem",
            item: num
        });
        onScanSuccess(testQRData);
    }

    // Handle USB scanner input (item_id only)
    document.getElementById('usbScannerInput').addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            const scannedCode = this.value.trim();
            this.value = ""; // clear input

            if (!scannedCode) return;

            console.log("Scanned Item ID:", scannedCode);
            handleItemScan(scannedCode);
        }
    });

    // Handle item scan success (QR or USB)
    function onScanSuccess(decodedText, decodedResult) {
        let itemID;
        try {
            const data = JSON.parse(decodedText);
            if (data && data.item) {
                itemID = data.item;
            } else {
                 console.warn("QR code is not in the expected format (e.g., {\"item\":\"123\"})", decodedText);
                 // Fallback to treat the whole string as itemID
                 itemID = decodedText.trim();
            }
        } catch (e) {
            // Assume it's a raw item_id if not JSON
            itemID = decodedText.trim();
        }

        if (itemID) {
            handleItemScan(itemID);
        } else {
            console.warn("Scanned data is empty or invalid.", decodedText);
        }
    }

    function handleItemScan(itemID) {
        if (scannedItems[itemID]) {
            // If item already exists, increment its quantity
            scannedItems[itemID].quantity++;
            console.log("Incremented quantity for item:", itemID);
            loadItemsView();
        } else {
            // If new item, fetch its details
            console.log("Scanned new item:", itemID);
            fetch(`script/get_item_details.php?item_id=${encodeURIComponent(itemID)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        scannedItems[itemID] = {
                            name: data.item.item_name,
                            quantity: 1
                        };
                        loadItemsView();
                    } else {
                        console.warn("Could not find details for item", itemID, data.message);
                        alert("Error: " + data.message);
                    }
                })
                .catch(err => {
                    console.error("Fetch error (item details):", err);
                    alert("Error fetching item details. Please check the console.");
                });
        }
    }

    function loadItemsView() {
        const tbody = document.getElementById('itemBodytable');
        if (!tbody) {
            console.error("itemBodytable not found");
            return;
        }
        tbody.innerHTML = '';

        if (Object.keys(scannedItems).length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="2" class="text-center text-muted">No items scanned yet</td>`;
            tbody.appendChild(tr);
            return;
        }

        console.log("Loading Items View for items:", scannedItems);

        for (const itemId in scannedItems) {
            const item = scannedItems[itemId];
            const tr = document.createElement('tr');
            tr.setAttribute('data-item-id', itemId);
            tr.innerHTML = `
                <td>${item.name}</td>
                <td contenteditable="true" onblur="updateQuantity(this, '${itemId}')">${item.quantity}</td>
            `;
            tbody.appendChild(tr);
        }
    }

    function updateQuantity(el, itemId) {
        const newQty = parseInt(el.textContent.trim()) || 0;
        if (scannedItems[itemId]) {
            if (newQty > 0) {
                scannedItems[itemId].quantity = newQty;
            } else {
                // If quantity is set to 0 or invalid, remove the item from the list
                delete scannedItems[itemId];
                loadItemsView(); // Refresh view to remove the row
            }
        }
    }

    // Add inventory - bulk submit all items
    function addForm(type, scriptUrl) {
        // Collect all scanned items from the object
        const items = [];
        
        for (const itemId in scannedItems) {
            const item = scannedItems[itemId];
            if (item.quantity > 0) {
                items.push({
                    warehouse_id: 1, // Assuming a default warehouse_id, update if needed
                    item_id: parseInt(itemId),
                    quantity: item.quantity
                });
            }
        }

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
                // Clear the scanned items object and table
                Object.keys(scannedItems).forEach(key => delete scannedItems[key]);
                loadItemsView();

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                if (modal) modal.hide();
                
                const toastMessage = data.toast || data.message;
                const toastType = data.type || 'success';
                window.location.href = 'inventory-items.php?toast=' + encodeURIComponent(toastMessage) + '&type=' + toastType;
            } else {
                if (data.toast) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                    if (modal) modal.hide();
                    window.location.href = 'inventory-items.php?toast=' + encodeURIComponent(data.toast) + '&type=' + (data.type || 'danger');
                } else {
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

<!-- auto focus on scanner input -->
<script>
setTimeout(() => {
    const scanner = document.getElementById('usbScannerInput');
    if (scanner) scanner.focus();
}, 100);
</script>
