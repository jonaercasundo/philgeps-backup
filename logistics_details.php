<?php 
    $is_logistics_page = true;
    require "template/header.php"; 
    require "script/role_auth.php";
    require "config/db.php";

    // roles allowed to access this page
    $allowed_roles = ['Super Admin', 'Admin', 'Coordinator'];

    // redirect
    redirectIfNotAuthorized($allowed_roles, 'index.php');

?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-3">
        <h2 class="mb-0">Logistics List</h2>
        <a href="#" data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success ms-auto">
            + Add New Logistics
        </a>
    </div>

    </div>
    <table id="logisticsTable" class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>Logistics ID</th>
                <th>Logistics Name</th>
                <th>Region</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

</div>

<?php include "partials/logistics_modals.php"?>

<?php require "template/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- CRUD Operations -->
<script>
    // Function to set the logistics ID for deletion and update source_page URL
    function updateDeleteURL(logisticsId) {
        // Set the logistics ID in the hidden input
        const deleteInput = document.getElementById('delete_logistics_id');
        if (deleteInput) {
            deleteInput.value = logisticsId;
        }
        
        // Update the source_page URL with the logistics ID
        const sourcePageInput = document.getElementById('delete_source_page');
        if (sourcePageInput) {
            sourcePageInput.value = `logistics_details.php?logistics_id=${logisticsId}`;
        }
    }
   
    // Update Edit Modal for logistics
    function updateEdit(logisticsId, warehouseId) {
        const name = document.getElementById("name" + logisticsId + "s").innerHTML;
        const region = document.getElementById("regions" + logisticsId + "s").innerHTML;
        
        document.getElementById("edit_logistics_id").value = logisticsId;
        document.getElementById("edit_logistics_name").value = name.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        document.getElementById("edit_region").value = region.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        
        // Preselect the warehouse in dropdown
        if (warehouseId) {
            setTimeout(() => {
                const warehouseSelect = document.querySelector('#editForm select[name="warehouse_id"]');
                warehouseSelect.value = warehouseId;
            }, 100);
        }
    }

    // Update logistics
    function updateLogistics() {
        const formData = new FormData(document.getElementById('editForm'));
        
        fetch('script/update_logistics.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = 'logistics_details.php?toast=Logistics Updated&type=success';
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

    // Delete logistics
    function deleteLogistics() {
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
                    window.location.href = 'logistics_details.php?toast=Logistics Deleted&type=success';
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
    // Custom addForm function for logistics page
    function addForm(type, scriptUrl) {
        const formData = new FormData(document.getElementById('addForm'));
        
        fetch('script/' + scriptUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
             if (data.success) {
                window.location.href = 'logistics_details.php?toast=Logistics Added&type=success';
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
    }

    // Function to populate dropdowns in edit modal
    function loadEditModalData(logisticsId) {
        // Load warehouses for edit modal
        fetch('script/get_warehouse_list.php')
            .then(response => response.json())
            .then(data => {
                const warehouseSelect = document.querySelector('#editForm select[name="warehouse_id"]');
                warehouseSelect.innerHTML = '<option value="">Select Warehouse</option>';
                if (!data.error) {
                    data.forEach(warehouse => {
                        warehouseSelect.innerHTML += `<option value="${warehouse.warehouse_id}">${warehouse.warehouse_name}</option>`;
                    });
                    
                    // If we have a specific logistics ID, preselect the current warehouse
                    if (logisticsId) {
                        // You'll need to fetch the current logistics data to get the warehouse_id
                        // This would require an additional API call
                    }
                } else {
                    warehouseSelect.innerHTML += '<option value="">Error loading warehouses</option>';
                }
            })
            .catch(error => {
                console.error('Error loading warehouses:', error);
                const warehouseSelect = document.querySelector('#editForm select[name="warehouse_id"]');
                warehouseSelect.innerHTML = '<option value="">Error loading warehouses</option>';
            });
    }

    // Show add modal and load data
    document.getElementById('addModal').addEventListener('show.bs.modal', function () {
        loadAddModalData();
    });

    // Show edit modal and load data
    document.getElementById('editModal').addEventListener('show.bs.modal', function () {
        // Get the logistics ID from the hidden field or trigger
        const logisticsId = document.getElementById('edit_logistics_id').value;
        loadEditModalData(logisticsId);
    });

    // Clear form when modal is hidden
    document.getElementById('addModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addForm').reset();
    });

    document.getElementById('editModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editForm').reset();
    });
</script>

<!-- Table Scripts -->
<script>
  $(document).ready(function() {
    // Initialize the DataTables instance for the logistics table
    const table = $('#logisticsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "script/get_logistics_details.php", 
            type: "GET"
        },
        columns: [
            { data: "logistic_id", className: "text-center" },
            { data: "logistic_name", className: "text-center" },
            { 
                data: "regions", 
                className: "text-center",
                render: function(data, type, row) {
                    return data || 'No regions assigned';
                }
            },
            {
                data: null,
                className: "text-center",
                orderable: false,
                render: function(data, type, row) {
                    const name = row.logistic_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                    const regions = row.regions ? row.regions.replace(/'/g, '&#39;').replace(/"/g, '&quot;') : '';
                    const warehouseId = row.warehouse_id || ''; // Make sure this field is in your query
                    
                    return `
                        <span style="display:none;" id="name${row.logistic_id}s">${name}</span>
                        <span style="display:none;" id="regions${row.logistic_id}s">${regions}</span>
                        <span style="display:none;" id="warehouse${row.logistic_id}s">${warehouseId}</span>
                        <button class="btn btn-warning" onclick="updateEdit(${row.logistic_id}, ${warehouseId})" data-bs-toggle="modal" data-bs-target="#editModal">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </button>
                        <button class="btn btn-danger" 
                                onclick="updateDeleteURL(${row.logistic_id})" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteModal">
                            <i class="bi bi-trash fs-4"></i>
                        </button>
                    `;
                }
            }
        ],
        scrollY: "53vh",
        scrollCollapse: true,
        paging: true,
        responsive: true,
    });
  });
</script>