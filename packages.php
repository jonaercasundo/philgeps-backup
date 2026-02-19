<?php
require "template/header.php";
require "config/db.php"; // your PDO connection
require "script/role_auth.php";
// roles allowed to access this page
$allowed_roles = ['Super Admin', 'Admin', 'Office Coordinator', 'Office Admin'];

// redirect
redirectIfNotAuthorized($allowed_roles, 'index.php');
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
		LEFT JOIN keystage k ON p.keystage_id = k.keystage_id
		LEFT JOIN lot l ON k.lot_id = l.lot_id            
            WHERE l.project_id = ?
            OR p.keystage_id IS NULL
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

  <div class="container mt-4">
    <h2 class="mb-3">Package List</h2>
    <div class="d-flex mb-3 justify-content-between">
      <div class="d-flex mb-3">
        <button data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success mb-3">+ Add New Package</button>
      </div>
      <div class="d-flex mb-3">
        <a href="script/generate_qr_per_package.php?project_id=<?=$project_id?>" target="_blank" class="btn btn-primary mb-3">Generate QR</a>
        <a href="script/generate_barcode_per_package.php?project_id=<?=$project_id?>" target="_blank" class="btn btn-info mb-3 ms-2">Generate Barcode</a>
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
                    window.location.href = data.redirect;
                } else {
                    alert("❌ Error: " + data.message);
                }
            })
            .catch(err => console.log("Server error: " + err));
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
          document.getElementById("edit_lot_num").value = resp.package.lot_name ? "Lot "+resp.package.lot_name : "N/A";
          document.getElementById("edit_key_num").value = resp.package.keystage_name ? "Keystage "+resp.package.keystage_name+" "+resp.package.description : "No Keystage Assigned";
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

  document.getElementById("saveEditBtn").addEventListener("click", function() {
    let formData = new FormData(document.getElementById("editForm"));

    fetch("script/update_package.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(resp => {
      if (resp.success) {
        window.location.href = resp.redirect;
      } else {
        alert("❌ Error: " + resp.message);
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