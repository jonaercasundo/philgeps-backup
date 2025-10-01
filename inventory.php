<?php 
    $is_warehouse_page = true;
    require "template/header.php"; 
    require "script/role_auth.php";
    require "config/db.php";

    // roles allowed to access this page
    $allowed_roles = ['Super Admin', 'Admin', 'Warehouse Admin'];

    // redirect
    redirectIfNotAuthorized($allowed_roles, 'index.php');


?>

<!-- Main Full-Screen Container -->
<div class="row g-0 h-100">
    <!-- 1. LEFT SIDEBAR (3 Columns wide on medium/large screens) -->
    <div class="col-md-3 border-end d-flex flex-column">
        <div class="px-3">
                <h5 class="mb-0 text-dark opacity-75">Summary</h5>
        </div>
        
        <div class="flex-fill p-3 border-top d-flex flex-column">
            <div class="chart-container h-100">
                <canvas id="inventoryByItemChart" class="h-100"></canvas>
            </div>
        </div>

         <div class="flex-fill p-3 border-top d-flex flex-column">
            <a href="#" class="btn btn-outline-secondary w-100 mb-2">
                View Something
            </a>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

<!-- CRUD Operations -->
<script>
    // Custom addForm function for inventory page
    function addForm(type, scriptUrl) {
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

<!-- Summary Scripts -->
<script>
    $(document).ready(function () {
        $.getJSON("script/get_warehouse_summary.php", function (data) {
            $("#warehouse_count").text(data.warehouse_count);
            $("#logistics_count").text(data.logistics_count);
        }).fail(function () {
            $("#warehouse_count").text("ERROR");
            $("#logistics_count").text("ERROR");
        });
    });
</script>

<!-- Inventory by Item Chart -->
<script>
    // Fetch data and render chart
    fetch('script/get_warehouse_summary.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            
            if (data.inventory_by_item && data.inventory_by_item.item_names.length > 0) {
                renderInventoryByItemChart(data.inventory_by_item);
            } else {
                document.getElementById('inventoryByItemChart').parentElement.innerHTML = 
                    '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-muted text-center">No inventory data available</p></div>';
            }
        })
        .catch(error => {
            console.error('Error fetching inventory by item data:', error);
            document.getElementById('inventoryByItemChart').parentElement.innerHTML = 
                '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-danger text-center">Error loading chart data</p></div>';
        });

    function renderInventoryByItemChart(chartData) {
        const ctx = document.getElementById('inventoryByItemChart').getContext('2d');
        
        // Use consistent indigo color palette (same as warehouse chart)
        const backgroundColors = [
            'rgba(79, 70, 229, 0.8)',   // indigo-600
            'rgba(99, 102, 241, 0.8)',  // indigo-500
            'rgba(129, 140, 248, 0.8)', // indigo-400
            'rgba(67, 56, 202, 0.8)',   // indigo-700
            'rgba(165, 180, 252, 0.8)', // indigo-300
            'rgba(49, 46, 129, 0.8)'    // indigo-800
        ];
        
        const borderColors = [
            'rgba(79, 70, 229, 1)',
            'rgba(99, 102, 241, 1)',
            'rgba(129, 140, 248, 1)',
            'rgba(67, 56, 202, 1)',
            'rgba(165, 180, 252, 1)',
            'rgba(49, 46, 129, 1)'
        ];

        // Create the chart with horizontal layout for better fit in sidebar
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.item_names,
                datasets: [{
                    label: 'Total Quantity',
                    data: chartData.total_quantities,
                    backgroundColor: backgroundColors.slice(0, chartData.item_names.length),
                    borderColor: borderColors.slice(0, chartData.item_names.length),
                    borderWidth: 1,
                    borderRadius: 4, 
                }]
            },
            options: {
                indexAxis: 'y', 
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Total Quantity by Item',
                        font: {
                            size: 14,
                            weight: '600'
                        },
                        color: '#1f2937',
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(31, 41, 55, 0.9)',
                        titleFont: { size: 12 },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function(context) {
                                return `Quantity: ${context.parsed.x}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: false, // Hide title for cleaner look
                        },
                        ticks: {
                            font: { size: 10 },
                            precision: 0 // Ensure whole numbers
                        },
                        grid: {
                            color: '#e5e7eb' // Light gray grid lines
                        }
                    },
                    y: {
                        title: {
                            display: false, // Hide title for cleaner look
                        },
                        ticks: {
                            font: { size: 10 }
                        },
                        grid: {
                            display: false // Hide vertical grid lines
                        }
                    }
                },
                animation: {
                    duration: 750,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
</script>



