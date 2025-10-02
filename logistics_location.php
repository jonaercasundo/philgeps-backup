<?php 
    $is_logistics_page = true;
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
                <canvas id="logisticsByWarehouseChart" class="h-100"></canvas>
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
                    <h5 class="mb-0 text-dark">Logistics Locations</h5>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success ms-auto">
                        + Assign Location to Logistics
                    </a>
                </div>

                <table id="logisticsLocationTable" class="table table-bordered table-striped">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Location ID</th>
                            <th>Logistics</th>
                            <th>Warehouse</th>
                            <th>Region</th>
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

<?php include "partials/logistics_location_modals.php"?>

<?php require "template/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- CRUD Operations -->
<script>
    // Function to set the location ID for deletion
    function updateDeleteURL(locationId) {
        const deleteInput = document.getElementById('delete_location_id');
        if (deleteInput) {
            deleteInput.value = locationId;
        }
        
        const sourcePageInput = document.getElementById('delete_source_page');
        if (sourcePageInput) {
            sourcePageInput.value = `logistics_location.php?location_id=${locationId}`;
        }
    }

    // Update Edit Modal for logistics location
    function updateEdit(locationId, logisticsName, warehouseName, region) {
        document.getElementById("edit_location_id").value = locationId;
        document.getElementById("edit_logistics_name").value = logisticsName.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        document.getElementById("edit_warehouse_name").value = warehouseName.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        document.getElementById("edit_region").value = region;
    }

    // Update logistics location
    function updateLocation() {
        const formData = new FormData(document.getElementById('editForm'));
        
        fetch('script/update_logistics_location.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = 'logistics_location.php?toast=Location Updated&type=success';
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

    // Delete logistics location
    function deleteLocation() {
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
                    window.location.href = 'logistics_location.php?toast=Location Deleted&type=success';
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
    // Custom addForm function for logistics location page
    function addForm(type, scriptUrl) {
        const formData = new FormData(document.getElementById('addForm'));
        
        fetch('script/' + scriptUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
             if (data.success) {
                window.location.href = 'logistics_location.php?toast=Location Added&type=success';
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
        // Load logistics
        fetch('script/get_logistics_list.php')
            .then(response => response.json())
            .then(data => {
                const logisticsSelect = document.querySelector('#addForm select[name="logistics_id"]');
                logisticsSelect.innerHTML = '<option value="">Select Logistics</option>';
                if (!data.error) {
                    data.forEach(logistics => {
                        logisticsSelect.innerHTML += `<option value="${logistics.logistic_id}">${logistics.logistic_name}</option>`;
                    });
                } else {
                    logisticsSelect.innerHTML += '<option value="">Error loading logistics</option>';
                }
            })
            .catch(error => {
                console.error('Error loading logistics:', error);
                const logisticsSelect = document.querySelector('#addForm select[name="logistics_id"]');
                logisticsSelect.innerHTML = '<option value="">Error loading logistics</option>';
            });

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
        // Initialize the DataTables instance for the logistics location table
        const table = $('#logisticsLocationTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "script/get_logistics_locations.php", 
                type: "GET"
            },
            columns: [
                { 
                    data: "logistics_location_id", 
                    className: "text-center",
                    orderable: true
                },
                { 
                    data: "logistic_name", 
                    className: "text-center",
                    orderable: true
                },
                { 
                    data: "warehouse_name", 
                    className: "text-center",
                    orderable: true
                },
                { 
                    data: "region", 
                    className: "text-center",
                    orderable: true
                },
                {
                    data: null,
                    className: "text-center",
                    orderable: false,
                    render: function(data, type, row) {
                        const logisticsName = row.logistic_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                        const warehouseName = row.warehouse_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                        
                        return `
                            <span style="display:none;" id="logistics${row.logistics_location_id}">${logisticsName}</span>
                            <span style="display:none;" id="warehouse${row.logistics_location_id}">${warehouseName}</span>
                            <span style="display:none;" id="region${row.logistics_location_id}">${row.region}</span>
                            <button class="btn btn-warning" 
                                    onclick="updateEdit(${row.logistics_location_id}, '${logisticsName}', '${warehouseName}', '${row.region}')" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-danger" 
                                    onclick="updateDeleteURL(${row.logistics_location_id})" 
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
            order: [[0, 'desc']],
            language: {
                emptyTable: "No logistics location records found",
                zeroRecords: "No matching location records found"
            }
        });
    });
</script>

<!-- Logistics by Warehouse Chart -->
<script>
    // Fetch data and render chart
    fetch('script/get_logistics_summary.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.logistics_by_warehouse && data.logistics_by_warehouse.logistics_names.length > 0) {
                renderLogisticsByWarehouseChart(data.logistics_by_warehouse);
            } else {
                document.getElementById('logisticsByWarehouseChart').parentElement.innerHTML = 
                    '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-muted text-center">No logistics data available</p></div>';
            }
        })
        .catch(error => {
            console.error('Error fetching logistics by warehouse data:', error);
            document.getElementById('logisticsByWarehouseChart').parentElement.innerHTML = 
                '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-danger text-center">Error loading chart data</p></div>';
        });

    function renderLogisticsByWarehouseChart(chartData) {
        const ctx = document.getElementById('logisticsByWarehouseChart').getContext('2d');
        
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

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.logistics_names,
                datasets: [{
                    label: 'Warehouse Count',
                    data: chartData.warehouse_counts,
                    backgroundColor: backgroundColors.slice(0, chartData.logistics_names.length),
                    borderColor: borderColors.slice(0, chartData.logistics_names.length),
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
                        text: 'Warehouses per Logistics',
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
                                return `Warehouses: ${context.parsed.x}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: false,
                        },
                        ticks: {
                            font: { size: 10 },
                            precision: 0
                        },
                        grid: {
                            color: '#e5e7eb'
                        }
                    },
                    y: {
                        title: {
                            display: false,
                        },
                        ticks: {
                            font: { size: 10 }
                        },
                        grid: {
                            display: false
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