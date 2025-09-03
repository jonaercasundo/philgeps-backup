<?php require "template/header.php"; 
      require "config/db.ph";
      try {
            $stmt = $pdo->prepare("SELECT project_id, project_name FROM projects WHERE status = 'Ongoing' OR status = 'Pending' ");
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                die("DB Error: " . $e->getMessage());
            }
?>

<div class="d-flex justify-content-between mb-3">
  <h4>Procurement</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProcurementModal">Add Procurement</button>
</div>

<table class="table table-bordered shadow-sm">
  <thead class="table-dark">
    <tr>
      <th>Project</th><th>Supplier</th><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Supply of Books</td><td>ABC Supplier</td><td>Math Books</td>
      <td>500</td><td>₱100</td><td>₱50,000</td>
    </tr>
  </tbody>
</table>

<!-- Add Procurement Modal -->
<div class="modal fade" id="addProcurementModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5>Add Procurement</h5></div>
      <div class="modal-body">
        <form>
          <div class="mb-3"><label>Project</label><select class="form-select"><option>Select Project</option></select></div>
          <div class="mb-3"><label>Supplier</label><input type="text" class="form-control"></div>
          <div class="row">
            <div class="col"><label>Item</label><input type="text" class="form-control"></div>
            <div class="col"><label>Quantity</label><input type="number" class="form-control"></div>
            <div class="col"><label>Unit Price</label><input type="number" class="form-control"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<?php require "template/footer.php"; ?>
