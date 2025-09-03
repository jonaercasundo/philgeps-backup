<?php require "template/header.php"; 
      require "config/db.php";

      try {
        // use prepared statements for security
        $stmt = $pdo->prepare("SELECT project_id, project_name FROM projects");
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
        SELECT i.*, p.project_id, p.project_name
        FROM invoices i JOIN projects p ON p.project_id = i.project_id;
        ");
        $stmt->execute();
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
          die("DB Error: " . $e->getMessage());
      }?>

<div class="d-flex justify-content-between mb-3">
  <h4>Billing</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBillingModal">Add Billing</button>
</div>
<div class="row mb-3">
  <div class="col-md-3">
    <label>Year</label>
    <select class="form-select"><option>2025</option><option>2024</option></select>
  </div>
  <div class="col-md-3">
    <label>Agency</label>
    <select class="form-select"><option>All</option><option>DepEd</option></select>
  </div>
  <div class="col-md-3">
    <label>Status</label>
    <select class="form-select"><option>All</option><option>Active</option><option>Completed</option></select>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <button class="btn btn-primary w-100">Generate Report</button>
  </div>
</div>
<table class="table table-bordered shadow-sm">
  <thead class="table-dark">
    <tr>
      <th>Project</th><th>Invoice No</th><th>Amount</th>
      <th>Status</th><th>Date Issued</th><th>Date Paid</th><th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php 
foreach($invoices as $invoice){
  $project_name = $invoice['project_name'];
  $invoice_no = $invoice['invoice_no'];
  $amount = $invoice['amount'];
  $invoice_id = $invoice['invoice_id'];
  $invoice_date = $invoice['invoice_date'];
  $payment_date = $invoice['payment_date'];
  $status = $invoice['status'];

  echo "<tr>
        <td>$project_name</td><td>$invoice_no</td><td>$amount</td><td>$status</td><td>$invoice_date</td><td>$payment_date</td>
        <td><button class='btn btn-sm btn-success' onclick=upadateStatus('$invoice_id')>Change Status</button></td>
      </tr>";
}
?>
  </tbody>
</table>

<!-- Add Billing Modal -->
<div class="modal fade" id="addBillingModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add Billing</h5></div>
      <div class="modal-body">
        <form>
          <div class="mb-3"><label>Project</label><select class="form-select">
            <?php 
              foreach($projects as $project){
                $project_name = mb_strimwidth($project['project_name'], 0, 50, '...');
                $project_id = $project['project_id'];
                echo "<option value='$project_id'>$project_name</option>";
              }
              ?>
          </select>
        </div>

          <div class="mb-3"><label>Invoice No</label><input type="text" class="form-control"></div>
          <div class="mb-3"><label>Amount</label><input type="number" class="form-control"></div>
          <div class="mb-3"><label>Date Issued</label><input type="date" class="form-control"></div>
          <div class="mb-3"><label>Upload Invoice</label><input type="file" class="form-control"></div>
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
