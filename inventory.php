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
    <!-- 1. LEFT SIDEBAR (3 Columns wide on medium/large screens) -->
    <!-- LEFT SIDEBAR -->
<div class="col-md-3 border-end d-flex flex-column vh-100">

  <!-- Header: fixed height -->
  <div class="px-3 d-flex justify-content-between align-items-center py-2 border-bottom flex-shrink-0">
    <h5 class="mb-0 text-dark opacity-75">Add Items</h5> 
    <div class="form-check form-switch mb-0">
      <input class="form-check-input" type="checkbox" role="switch" checked id="flexSwitchCheckDefault">
      <label class="form-check-label" for="flexSwitchCheckDefault">Item View</label>
    </div>
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
      </tbody>
    </table>
  </div>

  <!-- Footer button: fixed height -->
  <div class="px-3 py-2 border-top flex-shrink-0">
    <button class="btn btn-outline-secondary w-100 mb-2">Add Items</button>
  </div>

</div>


    <!-- 2. RIGHT MAIN CONTENT AREA (9 Columns wide on medium/large screens) -->
    <div class="col-md-9 d-flex flex-column">
        <!-- Large Main Content/Display Area -->
        <div class="flex-grow-1">
            <div class="bg-white px-4 rounded shadow-sm h-100">
                 <div class="d-flex align-items-center mb-3">
                       <h5 class="mb-0 text-dark">Inventory List</h5>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success ms-auto">
                        + Add New Inventory
                    </a>
                </div>
             

                <table id="inventoryTable" class="table table-bordered table-striped">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Inventory ID</th>
                            <th>Warehouse</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Action</th>
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
</script>
<script>
    // Custom addForm function for inventory page
    function addForm(type, scriptUrl) {
    // Collect all rows in the item table
    const tbody = document.getElementById('itemBodytable');
    if (!tbody) {
        alert('Item table body not found');
        return;
    }

    const items = [];
    // Loop over each row to collect item_id and qty
    for (let tr of tbody.rows) {
        // Example: assuming your rows have data attributes or td structure
        // If your row has the item_id in the first cell and qty in the second:
        const itemId = tr.querySelector('td[data-item-id]')?.dataset.itemId 
                       || tr.cells[0]?.dataset.itemId 
                       || tr.cells[0]?.getAttribute('data-item-id') 
                       || null;

        const qty = tr.cells[1]?.textContent || tr.querySelector('td[data-qty]')?.dataset.qty || null;

        if (itemId && qty) {
            items.push({item_id: itemId, qty: parseInt(qty, 10)});
        }
    }

    if (items.length === 0) {
        alert('No items found in the table to add.');
        return;
    }

    // Set the hidden input value
    document.getElementById('items_json').value = JSON.stringify(items);

    const formData = new FormData(document.getElementById('addForm'));

    fetch('script/' + scriptUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
         if (data.success) {
            window.location.href = 'inventory.php?toast=Inventory Added&type=success';
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
}


    // Function to populate dropdowns in add modal
    function loadAddModalData() {
        // Load warehouses
        fetch('script/get_warehouse_list.php')
            .then(response => response.json())
            .then(data => {
                const warehouseSelect = document.querySelector('#addForm select[name="warehouse_id"]');
                warehouseSelect.innerHTML = '<option value="">Select Warehouse</option>';
                if (!data.error) {
                    data.forEach(warehouse => {
                        warehouseSelect.innerHTML += `<option value="${warehouse.warehouse_id}">${warehouse.warehouse_name}</option>`;
                    });
                } else {
                    warehouseSelect.innerHTML += '<option value="">Error loading warehouses</option>';
                }
            })
            .catch(error => {
                console.error('Error loading warehouses:', error);
                const warehouseSelect = document.querySelector('#addForm select[name="warehouse_id"]');
                warehouseSelect.innerHTML = '<option value="">Error loading warehouses</option>';
            });

        // Load items
        fetch('script/get_items_list.php')
            .then(response => response.json())
            .then(data => {
                const itemSelect = document.querySelector('#addForm select[name="item_id"]');
                itemSelect.innerHTML = '<option value="">Select Item</option>';
                if (!data.error) {
                    data.forEach(item => {
                        itemSelect.innerHTML += `<option value="${item.item_id}">${item.item_name}</option>`;
                    });
                } else {
                    itemSelect.innerHTML += '<option value="">Error loading items</option>';
                }
            })
            .catch(error => {
                console.error('Error loading items:', error);
                const itemSelect = document.querySelector('#addForm select[name="item_id"]');
                itemSelect.innerHTML = '<option value="">Error loading items</option>';
            });
    }

    // Show add modal and load data
    document.getElementById('addModal').addEventListener('show.bs.modal', function () {
        loadAddModalData();
    });

    // Clear form when modal is hidden
    document.getElementById('addModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addForm').reset();
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
                    data: null,
                    className: "text-center",
                    orderable: false,
                    render: function(data, type, row) {
                        const itemName = row.item_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                        const warehouseName = row.warehouse_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                        
                        return `
                            <span style="display:none;" id="item${row.inventory_id}">${itemName}</span>
                            <span style="display:none;" id="warehouse${row.inventory_id}">${warehouseName}</span>
                            <span style="display:none;" id="quantity${row.inventory_id}">${row.qty}</span>
                            <button class="btn btn-warning" 
                                    onclick="updateEdit(${row.inventory_id}, '${itemName}', '${warehouseName}', ${row.qty})" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-danger" 
                                    onclick="updateDeleteURL(${row.inventory_id})" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                    }
                }
            ],
            scrollY: "53vh",
            scrollCollapse: true,
            paging: true,
            responsive: true,
            order: [[0, 'desc']], // Add default ordering
            language: {
                emptyTable: "No inventory records found",
                zeroRecords: "No matching inventory records found"
            }
        });
    });
 </script>
 <script>
    function simulateScan(num) {
    const testQRData = JSON.stringify({
        action: "addPackage",
        package: num
    });
    onScanSuccess(testQRData);
}

// Handle USB scanner input
document.getElementById('usbScannerInput').addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        let scannedCode = this.value.trim();
        this.value = ""; // clear input

        try {
            // Try JSON format (same as QR codes)
            let data = JSON.parse(scannedCode);
            onScanSuccess(scannedCode, null); // reuse your QR function
        } catch (e) {
            // If it's just a plain barcode string
            console.log("USB scanned:", scannedCode);
            onScanSuccess(JSON.stringify({action: "addPackage", package: scannedCode}), null);
        }
    }
});

// Global tracking of scanned packages
const addedPackages = [];

// Called when QR is scanned
function onScanSuccess(decodedText, decodedResult) {
    console.log("QR scanned:", decodedText);
    let data;
    try {
        data = JSON.parse(decodedText);
    } catch (e) {
        console.error("QR data not JSON:", decodedText);
        return;
    }

    if (data.action === "addPackage" && data.package) {
        const packageID = data.package;
        if (!addedPackages.includes(packageID)) {
            addedPackages.push(packageID);
            console.log("Added package:", packageID, "Current list:", addedPackages);
        } else {
            console.log("Package duplicate:", packageID);
        }

        // After adding, reload current view (item or lot)
        const checkbox = document.getElementById('flexSwitchCheckDefault');
        if (checkbox.checked) {
            loadItemsView();
        } else {
            loadLotsView();
        }
    } else {
        console.warn("QR action not recognized:", data.action);
    }
}

// Load per-item view
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
                headerRow.innerHTML = `<td colspan="2" style="font-weight:bold; background:#f0f0f0;">Package: LOT ${data.package.lot_name} KEYSTAGE ${data.package.keystage_name} KEYSTAGE ${data.package.description}</td>`;
                tbody.appendChild(headerRow);

                // Add each item row for this package
                data.items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-item-id', item.item_id);  // <-- add this
                    tr.innerHTML = `<td>${item.item_name || ''}</td><td>${item.qty || ''}</td>`;
                    tbody.appendChild(tr);
                });

            } else {
                console.warn("No items for package", pkg, data);
            }
          })
          .catch(err => console.error("Fetch error (items):", err));
    });
}


// Load per lot/keystage view
function loadLotsView() {
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

    console.log("Loading Lots View for packages:", addedPackages);

    addedPackages.forEach(pkg => {
        fetch(`script/get_lots_with_keystages.php?package_id=${encodeURIComponent(pkg)}`)
          .then(res => res.json())
          .then(data => {
            if (data.success && Array.isArray(data.lots)) {
                data.lots.forEach(lot => {
                    const tr = document.createElement('tr');
                    let display = `Lot: ${lot.lot_name}`;
                    if (lot.keystage_num) {
                        display += ` - Keystage ${lot.keystage_num} ${lot.description || ''}`;
                    }
                    tr.innerHTML = `<td>${display}</td><td>${lot.qty || 0}</td>`;
                    tbody.appendChild(tr);
                });
            } else {
                console.warn("No lots for package", pkg, data);
            }
          })
          .catch(err => console.error("Fetch error (lots):", err));
    });
}

// Toggle binding & initial load
document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.getElementById('flexSwitchCheckDefault');
    if (!checkbox) {
        console.error("Checkbox flexSwitchCheckDefault not found");
        return;
    }

    checkbox.addEventListener('change', () => {
        console.log("Toggle changed to:", checkbox.checked);
        if (checkbox.checked) {
            loadItemsView();
        } else {
            loadLotsView();
        }
    });

    // Initial view
    if (checkbox.checked) {
        loadItemsView();
    } else {
        loadLotsView();
    }
});

// Start scanner
html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
html5QrcodeScanner.render(onScanSuccess);
</script>
