<?php require "template/header.php"; ?>

<h4>Reports</h4>

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
      <th>Project</th><th>Agency</th><th>Amount</th><th>Status</th><th>Year</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Supply of Books</td><td>DepEd</td><td>₱1.2M</td><td>Completed</td><td>2025</td>
    </tr>
  </tbody>
</table>

<div class="mt-3">
  <button class="btn btn-danger">Export PDF</button>
  <button class="btn btn-success">Export Excel</button>
</div>

<?php require "template/footer.php"; ?>
