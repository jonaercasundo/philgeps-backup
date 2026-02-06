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
                    <input type="hidden" name="items_json" id="items_json">
                    <div class="mb-3">
                        <label>Enter Password to Proceed</label>
                        <input type="password" class="form-control" name="password" min="0" required>
                    </div>
                </form>
            </div>
           <div class="modal-footer">
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

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <select class="form-select mb-2" id="remarks_dropdown">
                            <option value="">-- Select a remark --</option>
                            <option value="Human error">Human error</option>
                            <option value="Reject">Reject</option>
                            <option value="Damaged during handling">Damaged during handling</option>
                            <option value="custom">Add custom remark...</option>
                        </select>
                        
                        <!-- Custom remarks input -->
                        <div class="collapse" id="customRemarksCollapse">
                            <div class="mt-2">
                                <label class="form-label">Custom Remark</label>
                                <textarea class="form-control" id="custom_remarks" rows="2" placeholder="Enter your custom remark..."></textarea>
                            </div>
                        </div>
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

<!-- Accept Inventory Modal -->
<div class="modal fade" id="acceptModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Accept Inventory</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="acceptForm">
                    <input type="hidden" id="accept_inventory_id" name="accept_inventory_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Input Password to Continue *</label>
                        <input type="password" class="form-control" name="accept_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-success" onclick="acceptInventory()">Accept Inventory</button>
            </div>
        </div>
    </div>
</div>

<!-- Subtract Inventory Modal -->
<div class="modal fade" id="subtractModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Subtract Inventory</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="subtractForm">
                    <input type="hidden" name="items_json" id="items_json">
                    <div class="mb-3">
                        <label>Enter Password to Proceed</label>
                        <input type="password" class="form-control" name="password" min="0" required>
                    </div>
                </form>
            </div>
           <div class="modal-footer">
                <button class="btn btn-primary" onclick="subtractForm('inventory','subtract_inventory.php')">Subtract</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Inventory Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Reject Inventory</h5>
            </div>
            <div class="modal-body">
                <form method="POST" id="rejectForm">
                    <input type="hidden" id="reject_inventory_id" name="reject_inventory_id">

                    <div class="mb-3">
                        <label class="form-label">Input Password to Continue *</label>
                        <input type="password" class="form-control" name="reject_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <select class="form-select mb-2" id="reject_remarks_dropdown">
                            <option value="">-- Select a remark --</option>
                            <option value="Human error">Human error</option>
                            <option value="Reject">Reject</option>
                            <option value="Damaged during handling">Damaged during handling</option>
                            <option value="custom">Add custom remark...</option>
                        </select>

                        <!-- Custom remarks input -->
                        <div class="collapse" id="reject_customRemarksCollapse">
                            <div class="mt-2">
                                <label class="form-label">Custom Remark</label>
                                <textarea class="form-control" id="custom_remarks" rows="2" placeholder="Due too..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-danger" onclick="rejectInventory()">Reject Inventory</button>
            </div>
        </div>
    </div>
</div>