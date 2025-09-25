<?php
require "template/header.php";
require "config/db.php"; // your PDO connection
// Get params
$keystage_id = isset($_GET['keystage_id']) ? (int)$_GET['keystage_id'] : null;
$lot_id = isset($_GET['lot_id']) ? (int)$_GET['lot_id'] : null;
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$ref_id = $keystage_id;
$ref_column="keystage_id";
if (!$keystage_id) {
    $ref_id = $lot_id;
    $ref_column="lot_id";
}

try {
    if ($ref_id) {
        $stmt = $pdo->prepare("
            SELECT 
                p.package_id,
                p.package_num,
                GROUP_CONCAT(CONCAT(i.item_name) SEPARATOR '<br>') AS Content,
                GROUP_CONCAT(CONCAT(pc.qty) SEPARATOR '<br>') AS qty,
                p.keystage_id ,
                p.width,
                p.height,
                p.length,
                CONCAT(p.width,'x',p.height,'x',p.length) AS Dimension
            FROM package p
            LEFT JOIN package_content pc ON p.package_id = pc.package_id
            LEFT JOIN item i ON pc.item_id = i.item_id
            WHERE p.$ref_column = ?
            GROUP BY p.package_id, p.package_num, p.$ref_column, p.length, p.width, p.height
            ORDER BY p.package_num ASC
        ");
        $stmt->execute([$ref_id]);

    } elseif ($project_id) {
        $stmt = $pdo->prepare("
            SELECT 
                p.package_id,
                p.package_num,
                GROUP_CONCAT(CONCAT(i.item_name) SEPARATOR '<br>') AS Content,
                GROUP_CONCAT(CONCAT(pc.qty) SEPARATOR '<br>') AS qty,
                p.keystage_id,
                p.width,
                p.height,
                p.length,
                CONCAT(p.width,'x',p.height,'x',p.length) AS Dimension
            FROM package p
            LEFT JOIN package_content pc ON p.package_id = pc.package_id
            LEFT JOIN item i ON pc.item_id = i.item_id
            INNER JOIN keystage k ON p.keystage_id = k.keystage_id
            INNER JOIN lot l ON k.lot_id = l.lot_id
            WHERE l.project_id = ?
            GROUP BY p.package_id, p.package_num, p.keystage_id, p.length, p.width, p.height
            ORDER BY p.package_num ASC
        ");
        $stmt->execute([$project_id]);
    } else {
        die("Missing keystage_id or project_id");
    }

    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<?php include "partials/packages_modal.php"; ?>
<!-- Add Item Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Add Items</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

      <form id="addItemForm" method="POST" action="script/add_items.php">
        <div class="modal-body">
        <?php if (isset($_GET['keystage_id'])): ?>
    <input type="hidden" name="keystage_id" value="<?= htmlspecialchars($_GET['keystage_id']) ?>">
    <input type="hidden" name="lot_id" value="<?= htmlspecialchars($_GET['lot_id']) ?>">
      <?php else: ?>
          <!-- Select Lot + Keystage -->
          <div class="mb-3 d-flex">
              <div class="w-50 me-2">
                  <label for="lot_id" class="form-label">Lot</label>
                  <select class="form-select" id="lot_id" name="lot_id" onchange="populateKeystage()" required>
                      <option value="">-- Select Lot --</option>
                      <?php
                      $stmt = $pdo->prepare("SELECT DISTINCT lot_id, lot_name FROM lot WHERE project_id = :pid");
                      $stmt->execute([':pid' => $project_id]);
                      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $lot) {
                          echo "<option value='" . htmlspecialchars($lot['lot_id']) . "'>"
                              . htmlspecialchars($lot['lot_name'])
                              . "</option>";
                      }
                      ?>
                  </select>
              </div>
              <div class="w-100 me-2">
                  <label for="keystage_id" class="form-label">Keystage</label>
                  <select class="form-select" id="keystage_id" name="keystage_id" required disabled>
                      <option value="">-- Select Keystage --</option>
                  </select>
              </div>
          </div>
      <?php endif; ?>
          </div>
          <table class="table table-bordered table-hover table-striped align-middle" id="myTable">
            <thead class="table-dark">
              <tr>
                <th>Paste the table below</th>
              </tr>
            </thead>
            <tbody>
              <tr id="pasteHere">
                <td contenteditable="true">Here!</td>
              </tr>
            </tbody>
          </table><br><br>
          <!-- Dynamic Items -->
          <div id="itemsContainer">
            <div class="d-flex mb-2">
                <select class="form-select" name="items[]">
                  <option value="">-- Select Item --</option>
                  <?php
                  $stmt = $pdo->query("SELECT item_id, item_name FROM item ORDER BY item_name ASC");
                  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
                      echo "<option value='" . htmlspecialchars($item['item_id']) . "'>"
                          . htmlspecialchars($item['item_name'])
                          . "</option>";
                  }
                  ?>
                </select>
                <input type="number" class="form-control" name="quantities[]" min="1" required>
                <button type="button" class="btn btn-danger btn-sm removeItemBtn">x</button>
            </div>
          </div>

          <!-- Add More Items Button -->
          <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addMoreItem">
            + Add Another Item
          </button>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Items</button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- Edit Package Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5>Edit Package</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
      <div class="modal-body">
        <form id="editForm">
          <input type="hidden" name="package_id" id="edit_package_id">

          <div class="mb-3 d-flex">
            <div class="w-50 me-2">
                <label>Package Num</label>
            <input type="text" class="form-control" name="package_num"readonly id="edit_package_num">
            </div>
            <div class="w-50 me-2">
                <label>Lot Number</label>
            <input type="text" class="form-control" readonly id="edit_lot_num">
            </div>
            <div class="w-100 me-2">
                <label>Keystage</label>
            <input type="text" class="form-control" readonly id="edit_key_num">
            </div>
          </div>
                  
          <div class="mb-3 d-flex">
            <div class="w-100 me-2">
              <label>Width</label>
              <input type="decimal" class="form-control" name="width" id="edit_width">
            </div>
            <div class="w-100 me-2">
              <label>Height</label>
              <input type="decimal" class="form-control" name="height" id="edit_height">
            </div>
            <div class="w-100 me-2">
              <label>Length</label>
              <input type="decimal" class="form-control" name="length" id="edit_length">
            </div>
          </div>
          <div class="mb-3">
            <label>Items in Package</label>
            <div id="edit_items"></div>
            <button type="button" class="btn btn-sm btn-success mt-2" id="addItemBtn">+ Add Item</button>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="saveEditBtn">Save changes</button>
      </div>
    </div>
  </div>
</div>




  <div class="container mt-4">
    <h2 class="mb-3">Package List</h2>
<div class="d-flex mb-3 justify-content-between">
  <div class="d-flex mb-3">
    <button data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success mb-3">+ Add New Package</button>
  </div>
</div>


    <?php if (empty($packages)): ?>
        <p>No Packages found.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Package Num</th>
                    <th>Content</th>
                    <th>Quantity</th>
                    <th>Keystage ID</th>
                    <th>Dimension</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?= htmlspecialchars($package['package_num']) ?></td>
                        <td><?= $package['Content']?></td>
                        <td><?= $package['qty']?></td>
                        <td><?= htmlspecialchars($package['keystage_id']) ?></td>
                        <td><?= htmlspecialchars($package['Dimension'])?></td>
                        <td>
                        <a href="items.php?id=<?=$project_id?>&package_id=<?= $package['package_id'] ?>" class="btn btn-primary d-inline-flex align-items-center mb-1"><i class='bi bi-eye fs-4 me-1'></i>Packages</a>
                        <a href="#" 
                            class="btn btn-warning editBtn mb-1"
                            data-bs-toggle="modal" 
                            data-bs-target="#editModal"
                            data-id="<?= $package['package_id'] ?>"
                            data-num="<?= htmlspecialchars($package['package_num']) ?>"
                            data-width="<?= htmlspecialchars($package['width']) ?>"
                            data-length="<?= htmlspecialchars($package['length']) ?>"
                            data-height="<?= htmlspecialchars($package['height']) ?>">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </a>
                        <button data-bs-toggle="modal" data-bs-target="#deleteModal" onclick="document.getElementById('delete_packages').value = <?= htmlspecialchars($package['package_id']) ?>;" class="btn btn-danger mb-1"><i class="bi bi-trash fs-4"></i></button>
                    </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>

        document.getElementById("addItemForm").addEventListener("submit", function(e) {
            e.preventDefault(); // stop normal form submit
            
            let formData = new FormData(this);

            fetch("script/add_items.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // show toast and refresh items if needed
                    alert("✅ Package added!");
                    location.reload(); // or close modal
                } else {
                    alert("❌ Error: " + data.message);
                }
            })
            .catch(err => alert("Server error: " + err));
        });


           // Add More Items Button (for Add Modal)
            document.getElementById("addMoreItem").addEventListener("click", function () {
                let container = document.getElementById("itemsContainer");

                let options = allItems.map(i =>
                    `<option value="${i.item_id}">${i.item_name}</option>`
                ).join("");

                let newRow = document.createElement("div");
                newRow.classList.add("row", "g-2", "align-items-center", "item-row", "mb-2");

                newRow.innerHTML = `
                    <div class="d-flex mb-2 itemRow">
                        <select class="form-select" name="items[]">
                            <option value="">-- Select Item --</option>
                            ${options}
                        </select>
                        <input type="number" class="form-control" name="quantities[]" min="1" required>
                        <button type="button" class="btn btn-danger btn-sm removeItemBtn">x</button>
                    </div>
                `;

                container.appendChild(newRow);
            });


        </script>
    <?php endif; ?>
    <?php
// preload items for dropdown
$itemsStmt = $pdo->query("SELECT item_id, item_name FROM item ORDER BY item_name");
$allItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
const allItems = <?= json_encode($allItems) ?>;

function renderItemRow(item_id = "", qty = "") {
  let options = allItems.map(i =>
    `<option value="${i.item_id}" ${i.item_id == item_id ? "selected" : ""}>${i.item_name}</option>`
  ).join("");

  return `
    <div class="d-flex mb-2 itemRow">
      <select name="items[]" class="form-control me-2">${options}</select>
      <input type="number" name="qty[]" value="${qty}" class="form-control me-2" placeholder="Qty">
      <button type="button" class="btn btn-danger btn-sm removeItemBtn">x</button>
    </div>
  `;
}

document.addEventListener("DOMContentLoaded", function() {
  const editButtons = document.querySelectorAll(".editBtn");
  const editItemsDiv = document.getElementById("edit_items");

  editButtons.forEach(btn => {
    btn.addEventListener("click", function() {
      let package_id = this.dataset.id;

      fetch("script/get_package.php?package_id=" + package_id)
        .then(res => res.json())
        .then(resp => {
          if (!resp.success) {
            alert(resp.message);
            return;
          }

          // Fill form fields
          document.getElementById("edit_package_id").value = resp.package.package_id;
          document.getElementById("edit_package_num").value = resp.package.package_num;
          document.getElementById("edit_lot_num").value = "Lot "+resp.package.lot_name;
          document.getElementById("edit_key_num").value = "Keystage "+resp.package.keystage_name+" "+resp.package.description;
          document.getElementById("edit_width").value = resp.package.width;
          document.getElementById("edit_height").value = resp.package.height;
          document.getElementById("edit_length").value = resp.package.length;

          // Fill items
          editItemsDiv.innerHTML = "";
          resp.items.forEach(it => {
            editItemsDiv.innerHTML += renderItemRow(it.item_id, it.qty);
          });
        });
    });
  });

  // Add new item row
  document.getElementById("addItemBtn").addEventListener("click", function() {
    editItemsDiv.innerHTML += renderItemRow();
  });

  // Remove item row
  document.addEventListener("click", function(e) {
    if (e.target.classList.contains("removeItemBtn")) {
      e.target.closest(".itemRow").remove();
    }
  });

  // Save
  document.getElementById("saveEditBtn").addEventListener("click", function() {
    let formData = new FormData(document.getElementById("editForm"));

    fetch("script/update_package.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(resp => {
      if (resp.success) {
        location.reload();
      } else {
        alert("Update failed: " + resp.message);
      }
    });
  });
});

function populateKeystage(){
    lot_id = document.getElementById('lot_id').value;
    keystage_id = document.getElementById('keystage_id');

     // clear existing options
    keystage_id.innerHTML = '';

    fetch("script/get_keystage.php?lotid="+lot_id, {
        method: "GET",
    })
    .then(res => res.json())
    .then(data => {
            data.keystages.forEach(keystage => {
                let option = document.createElement("option");
                option.value = keystage.id;        // set value
                option.textContent = keystage.name; // set text
                keystage_id.appendChild(option);
                keystage_id.disabled = false;
            });
    })
     .catch(err => {
        console.error("Error:", err)
    })
    .finally(() => {
        hideLoading();
    });
   
}


// Trigger whenever table changes (typing or pasting)
document.getElementById("myTable").addEventListener("input", syncTableToForm);

function syncTableToForm() {
  let rows = document.querySelectorAll("#myTable tr"); 
  let container = document.getElementById("itemsContainer");
  container.innerHTML = ""; // reset

  rows.forEach((row, index) => {
    
     if (index === 1) return; // skip the header row
     console.log(index)
    let cells = row.querySelectorAll("td");
    if (cells.length < 1) return;

    let itemText = (cells[0]?.innerText || "").trim();
    let qtyText  = (cells[1]?.innerText || "").trim();
    let dimText  = (cells[2]?.innerText || "").trim();

    // Normalize dimension -> remove whitespace and force "x"
    let normalizedDim = "";
    if (dimText) {
      normalizedDim = dimText.replace(/\s*/g, "").replace(/[X×]/gi, "x");
    }

    // Match item by exact name
    let selectedItem = allItems.find(i => normalize(i.item_name) === normalize(itemText));

    let options = allItems.map(i =>
      `<option value="${i.item_id}" ${selectedItem && i.item_id === selectedItem.item_id ? "selected" : ""}>
        ${i.item_name}
      </option>`
    ).join("");

    let newRow = document.createElement("div");
    newRow.classList.add("row", "g-2", "align-items-center", "item-row", "mb-2");

    newRow.innerHTML = `
      <div class="d-flex mb-2 itemRow">
        <select class="form-select" name="items[]">
          <option value="">-- Select Item --</option>
          ${options}
        </select>
        <input type="number" class="form-control" name="quantities[]" value="${qtyText || 1}" min="1" required>
        <input type="hidden" name="dimention[]" value="${normalizedDim}">
        <button type="button" class="btn btn-danger btn-sm removeItemBtn">x</button>
      </div>
    `;

    container.appendChild(newRow);
  });
}


// Helper function to normalize strings (remove symbols, trim, lowercase)
function normalize(str) {
  return str
    .toLowerCase()
    .replace(/[^\w\s]/gi, "") // remove non-alphanumeric/symbols (keeps letters, numbers, spaces, _)
    .trim();
}
</script>

<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>