<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Delete Warehouse</h5></div>
            <div class="modal-body">
                <form id="deleteForm" method="POST" action="script/delete.php">
                     <input type="hidden" name="source_page" id="delete_source_page" value="warehouse_details.php">
                    <input type="text" id="delete_warehouse_id" name="id"> 
                    <input type="hidden" name="table" value="warehouse">
                    <input type="hidden" name="condition" value="warehouse_id">
                    <div class="mb-3">
                        <label class="form-label">Input password to Continue</label>
                        <input type="password" class="form-control" name="deletePassword" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteForm').submit();">Delete</button>
            </div>
        </div>
    </div>
</div>


<!-- Add Warehouse Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Warehouse</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="addForm">
                    <div class="mb-3">
                        <label>Warehouse Name</label>
                        <input type="text" class="form-control" name="warehouse_name" required>
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea class="form-control" name="warehouse_address" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Contact Info</label>
                        <input type="text" class="form-control" name="contact_info" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="addForm('warehouse','add_warehouse.php')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Warehouse Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Warehouse</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" id="edit_warehouse_id" name="warehouse_id">
                    <div class="mb-3">
                        <label>Warehouse Name</label>
                        <input type="text" id="edit_warehouse_name" class="form-control" name="warehouse_name" required>
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea id="edit_warehouse_address" class="form-control" name="warehouse_address" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Contact Info</label>
                        <input type="text" id="edit_contact_info" class="form-control" name="contact_info" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="updateWarehouse()">Save</button>
            </div>
        </div>
    </div>
</div>