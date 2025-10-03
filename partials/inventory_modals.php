<!-- Delete Inventory Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Delete Inventory Record</h5></div>
            <div class="modal-body">
                <form id="deleteForm" method="POST" action="script/delete.php">
                    <input type="hidden" name="source_page" id="delete_source_page" value="inventory.php?">
                    <input type="hidden" id="delete_inventory_id" name="id"> 
                    <input type="hidden" name="table" value="inventory">
                    <input type="hidden" name="condition" value="inventory_id">
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

<!-- Add Inventory Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Inventory</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="addForm">
                    <div class="mb-3">
                        <label>Inventory ID</label>
                        <input type="text" class="form-control" name="inventory_id" required>
                        <small class="text-muted">Enter unique inventory identifier</small>
                    </div>
                    <div class="mb-3">
                        <label>Warehouse</label>
                        <select class="form-control" name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Item</label>
                        <select class="form-control" name="item_id" required>
                            <option value="">Select Item</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="0" required>
                    </div>
                </form>
            </div>
           <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="addForm('inventory','add_inventory.php')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Inventory Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Inventory</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" id="edit_inventory_id" name="edit_inventory_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="edit_item_name" readonly>
                        <small class="text-muted">Item cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Warehouse</label>
                        <input type="text" class="form-control" id="edit_warehouse_name" readonly>
                        <small class="text-muted">Warehouse cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Quantity *</label>
                        <input type="number" class="form-control" name="edit_quantity" id="edit_quantity" min="0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" onclick="updateInventory()">Save Changes</button>
            </div>
        </div>
    </div>
</div>