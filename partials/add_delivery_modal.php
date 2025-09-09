<div class="modal fade" id="addDeliveryModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h5>Add Delivery</h5></div>
      <div class="modal-body">
        <form method="POST" id="addDelivery" action="script/save_deliveries.php" enctype="multipart/form-data">
          <div class="mb-3"><label>Project</label>
          <select name="project" class="form-select" id="project" onchange ="checkAgency(this, 'deped')" required>
            <option value="#">Select Project</option>
            <?php 
            foreach($projects as $project){
              $project_id = $project['project_id'];
              $project_name = mb_strimwidth($project['project_name'], 0, 50, '...');
              if($project['agency'] == 'Deped'){
              echo "
              <option data-extra='Deped' value='$project_id'>$project_name</option>
              ";
              }else{
                echo "
              <option value='$project_id'>$project_name</option>
              ";
              };
            }
            ?>
          </select></div>
          <div class="mb-3 visually-hidden deped">
            <label for="schoolSearch" class="form-label">Search School</label>
            <input name="school" type="text" id="schoolSearch" class="form-control" placeholder="Type school name...">
            <ul id="schoolResults" class="list-group position-absolute w-100" style="z-index:1000;"></ul>
          </div>
            <input type="hidden" id="address" name="address" value="">
          <div class="mb-3 visually-hidden deped"><label>Lot</label>
          <select name="lot" type="text" class="form-control" id="lotSelect" onchange="getKeystage(this.value, 'keystageSelect')">
            <option value="#">Select Keystage</option>
          </select>
          </div>
          <div class="mb-3 visually-hidden deped"><label>Keystage</label>
          <select name="keystage" type="text" class="form-control" id="keystageSelect">
            
          </select>
          </div>
          <div class="mb-3 visually-hidden deped"><label>Package Type</label>
          <select name="package_type" type="text" class="form-control">
            <option value="c1">C1</option>
            <option value="c2">C2</option>
            <option value="c3">C3</option>
            <option value="c4">C4</option>
            <option value="c5">C5</option>
            <option value="c6">C6</option>
          </select>
          </div>
          <div class="mb-3"><label>DR Number</label><input name="DRN" type="text" class="form-control"></div>
          <div class="mb-3"><label>Date</label><input type="date" name="dateDeliver" class="form-control" required></div>
          </form>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" onclick="document.getElementById('addDelivery').submit();">Save</button>
      </div>
      </div>
    </div>
  </div>
</div>