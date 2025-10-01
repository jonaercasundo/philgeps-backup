<!-- Delete Logistics Location Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Delete Logistics Location</h5></div>
            <div class="modal-body">
                <form id="deleteForm" method="POST" action="script/delete.php">
                    <input type="hidden" name="source_page" id="delete_source_page" value="logistic_locations.php?">
                    <input type="hidden" id="delete_location_id" name="id"> 
                    <input type="hidden" name="table" value="logistics_location">
                    <input type="hidden" name="condition" value="logistics_location_id">
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

<!-- Add Logistics Location Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Logistics Location</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="addForm">
                    <div class="mb-3">
                        <label>Logistics</label>
                        <select class="form-control" name="logistics_id" required>
                            <option value="">Select Logistics</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Warehouse</label>
                        <select class="form-control" name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Region</label>
                        <input type="text" class="form-control" name="region" required>
                        <small class="text-muted">Enter the region for this logistics location</small>
                    </div>
                </form>
            </div>
           <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="addForm('logistics_location','add_logistics_location.php')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Logistics Location Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Logistics Location</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" id="edit_location_id" name="location_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Logistics Name</label>
                        <input type="text" class="form-control" id="edit_logistics_name" readonly>
                        <small class="text-muted">Logistics cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Warehouse</label>
                        <input type="text" class="form-control" id="edit_warehouse_name" readonly>
                        <small class="text-muted">Warehouse cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Region *</label>
                        <input type="text" class="form-control" name="edit_region" id="edit_region" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="updateLocation()">Save Changes</button>
            </div>
        </div>
    </div>
</div>