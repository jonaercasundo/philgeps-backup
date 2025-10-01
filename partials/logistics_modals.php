<!-- Delete Logistics Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Delete Logistics</h5></div>
            <div class="modal-body">
                <form id="deleteForm" method="POST" action="script/delete.php">
                     <input type="hidden" name="source_page" id="delete_source_page" value="logistics_details.php">
                    <input type="hidden" id="delete_logistics_id" name="id"> 
                    <input type="hidden" name="table" value="logistics">
                    <input type="hidden" name="condition" value="logistic_id">
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

<!-- Add Logistics Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Logistics</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="addForm">
                    <div class="mb-3">
                        <label>Logistics Name</label>
                        <input type="text" class="form-control" name="logistic_name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="addForm('logistics','add_logistics.php')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Logistics Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Logistics</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" id="edit_logistics_id" name="logistic_id">
                    <div class="mb-3">
                        <label>Logistics Name</label>
                        <input type="text" id="edit_logistics_name" class="form-control" name="logistic_name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="updateLogistics()">Save</button>
            </div>
        </div>
    </div>
</div>