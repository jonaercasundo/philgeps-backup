<?php 
    $is_warehouse_page = true;
    require "template/header.php"; 
    require "script/role_auth.php";
    require "config/db.php";

    // roles allowed to access this page
    $allowed_roles = ['Super Admin', 'Admin', 'Warehouse Admin','Office Admin'];

    // redirect
    redirectIfNotAuthorized($allowed_roles, 'index.php');

?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-3">
        <h2 class="mb-0">Warehouse List</h2>
        <a href="#" data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success ms-auto">
            + Add New Warehouse
        </a>
    </div>

    </div>
    <table id="warehouseTable" class="table table-bordered table-striped">
        <thead class="table-dark text-center">
            <tr>
                <th>Warehouse ID</th>
                <th>Warehouse Name</th>
                <th>Address</th>
                <th>Contact Info</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

</div>

<?php include "partials/warehouse_modals.php"?>

<?php require "template/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- CRUD Operations -->
<script>
    // Function to set the warehouse ID for deletion and update source_page URL
    function updateDeleteURL(warehouseId) {
        // Set the warehouse ID in the hidden input
        const deleteInput = document.getElementById('delete_warehouse_id');
        if (deleteInput) {
            deleteInput.value = warehouseId;
        }
        
        // Update the source_page URL with the warehouse ID
        const sourcePageInput = document.getElementById('delete_source_page');
        if (sourcePageInput) {
            sourcePageInput.value = `warehouse_details.php?warehouse_id=${warehouseId}`;
        }
    }
   // Update Edit Modal - similar to keystage pattern
    function updateEdit(warehouseId) {
        const name = document.getElementById("name" + warehouseId + "s").innerHTML;
        const address = document.getElementById("address" + warehouseId + "s").innerHTML;
        const contact = document.getElementById("contact" + warehouseId + "s").innerHTML;
        
        document.getElementById("edit_warehouse_id").value = warehouseId;
        document.getElementById("edit_warehouse_name").value = name.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        document.getElementById("edit_warehouse_address").value = address.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        document.getElementById("edit_contact_info").value = contact.replace(/&#39;/g, "'").replace(/&quot;/g, '"');
    }

    // Update warehouse - using the same pattern as keystage
    function updateWarehouse() {
        const formData = new FormData(document.getElementById('editForm'));
        
        fetch('script/update_warehouse.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = 'warehouse_details.php?toast=Warehouse Updated&type=success';
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

    // Delete warehouse - using the same pattern as keystage with password
    function deleteWarehouse() {
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
                    window.location.href = 'warehouse_details.php?toast=Warehouse Deleted&type=success';
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
    
    // Custom addForm function for warehouse page
    function addForm(type, scriptUrl) {
        const formData = new FormData(document.getElementById('addForm'));
        
        fetch('script/' + scriptUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'warehouse_details.php?toast=Warehouse Added&type=success';
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
</script>
<!-- Table Scripts -->
<script>
  $(document).ready(function() {
    // Initialize the DataTables instance for the warehouse table
    const table = $('#warehouseTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            // New URL to fetch server-side processed warehouse data
            url: "script/get_warehouse_details.php", 
            type: "GET"
        },
        columns: [
            { data: "warehouse_id", className: "text-center" },
            { data: "warehouse_name", className: "text-center" },
            { data: "warehouse_address", className: "text-center" },
            { data: "contact_info", className: "text-center" },
            {
        data: null,
        className: "text-center",
        orderable: false,
        render: function(data, type, row) {
            const name = row.warehouse_name.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
            const address = row.warehouse_address.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
            const contact = row.contact_info.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
            
            return `
                <span style="display:none;" id="name${row.warehouse_id}s">${name}</span>
                <span style="display:none;" id="address${row.warehouse_id}s">${address}</span>
                <span style="display:none;" id="contact${row.warehouse_id}s">${contact}</span>
                <button class="btn btn-warning" onclick="updateEdit(${row.warehouse_id})" data-bs-toggle="modal" data-bs-target="#editModal">
                    <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button class="btn btn-danger" 
                        onclick="updateDeleteURL(${row.warehouse_id})" 
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