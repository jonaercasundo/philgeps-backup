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
$ref_column = "keystage_id";
if (!$keystage_id) {
    $ref_id = $lot_id;
    $ref_column = "lot_id";
}

// Whitelist the interpolated column name — never interpolate raw request data into SQL.
$allowed_ref_columns = ['keystage_id', 'lot_id'];
if (!in_array($ref_column, $allowed_ref_columns, true)) {
    die("Invalid reference column.");
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
                GROUP_CONCAT(i.item_name SEPARATOR '<br>') AS Content,
                GROUP_CONCAT(pc.qty SEPARATOR '<br>') AS qty,
                p.keystage_id,
                p.width,
                p.height,
                p.length,
                CONCAT(p.width,'x',p.height,'x',p.length) AS Dimension
            FROM package p
            LEFT JOIN package_content pc ON p.package_id = pc.package_id
            LEFT JOIN item i ON pc.item_id = i.item_id
            LEFT JOIN lot l ON p.lot_id = l.lot_id
            WHERE l.project_id = ?
            GROUP BY
                p.package_id,
                p.package_num,
                p.keystage_id,
                p.length,
                p.width,
                p.height
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

// preload items for dropdown
$itemsStmt = $pdo->query("SELECT item_id, item_name FROM item ORDER BY item_name");
$allItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Quick summary stats for the header bar
$totalPackages = count($packages);
$totalItemsQty = 0;
foreach ($packages as $pkg) {
    if (!empty($pkg['qty'])) {
        foreach (explode('<br>', $pkg['qty']) as $q) {
            $totalItemsQty += (int)$q;
        }
    }
}
?>
<?php include "partials/packages_modal.php"; ?>

<style>
  :root {
    --pkg-accent: #2563eb;
    --pkg-accent-soft: #eff6ff;
    --pkg-border: #e5e7eb;
  }

  .pkg-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
  }

  .pkg-title h2 {
    margin-bottom: 0.15rem;
    font-weight: 700;
  }

  .pkg-title .text-muted {
    font-size: 0.9rem;
  }

  .pkg-stats {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
  }

  .pkg-stat-card {
    background: #fff;
    border: 1px solid var(--pkg-border);
    border-radius: 0.6rem;
    padding: 0.75rem 1.1rem;
    min-width: 140px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }

  .pkg-stat-card .stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1.1;
    color: #111827;
  }

  .pkg-stat-card .stat-label {
    font-size: 0.78rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  .pkg-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .pkg-search {
    position: relative;
    max-width: 320px;
    flex: 1 1 220px;
  }

  .pkg-search input {
    padding-left: 2.25rem;
  }

  .pkg-search i {
    position: absolute;
    left: 0.7rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
  }

  .pkg-actions-right {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .table-responsive-wrapper {
    border: 1px solid var(--pkg-border);
    border-radius: 0.6rem;
    overflow: hidden;
  }

  #packagesTable thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    white-space: nowrap;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  #packagesTable tbody tr {
    transition: background-color 0.12s ease;
  }

  #packagesTable tbody tr:hover {
    background-color: var(--pkg-accent-soft);
  }

  #packagesTable td {
    vertical-align: middle;
  }

  .qty-badge {
    display: inline-block;
    background: #eef2ff;
    color: #3730a3;
    border-radius: 0.4rem;
    padding: 0.05rem 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 2px;
  }

  .dim-chip {
    font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 0.85rem;
    background: #f3f4f6;
    padding: 0.15rem 0.5rem;
    border-radius: 0.4rem;
    white-space: nowrap;
  }

  .row-actions {
    display: flex;
    gap: 0.35rem;
    flex-wrap: nowrap;
  }

  .row-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
  }

  .pkg-empty-state {
    text-align: center;
    padding: 3.5rem 1.5rem;
    color: #6b7280;
  }

  .pkg-empty-state i {
    font-size: 2.4rem;
    color: #d1d5db;
    margin-bottom: 0.75rem;
    display: block;
  }

  #toastStack {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1080;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .no-results-row td {
    text-align: center;
    color: #9ca3af;
    padding: 2rem !important;
  }

  @media (max-width: 576px) {
    .pkg-actions-right { width: 100%; }
    .pkg-actions-right .btn { flex: 1 1 auto; justify-content: center; }
    .row-actions { flex-wrap: wrap; }
  }
</style>

<div id="toastStack" aria-live="polite" aria-atomic="true"></div>

<div class="container mt-4">

  <div class="pkg-header">
    <div class="pkg-title">
      <h2>Package List</h2>
      <div class="text-muted">Track packages, contents, and dimensions for this shipment.</div>
    </div>
    <div class="pkg-actions-right">
      <button data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i>Add Package
      </button>
      <a href="script/generate_qr_per_package.php?project_id=<?= (int)$project_id ?>" target="_blank" class="btn btn-outline-primary">
        <i class="bi bi-qr-code me-1"></i>QR Codes
      </a>
      <a href="script/generate_barcode_per_package.php?project_id=<?= (int)$project_id ?>" target="_blank" class="btn btn-outline-secondary">
        <i class="bi bi-upc me-1"></i>Barcodes
      </a>
    </div>
  </div>

  <div class="pkg-stats">
    <div class="pkg-stat-card">
      <div class="stat-value" id="statTotalPackages"><?= (int)$totalPackages ?></div>
      <div class="stat-label">Packages</div>
    </div>
    <div class="pkg-stat-card">
      <div class="stat-value"><?= (int)$totalItemsQty ?></div>
      <div class="stat-label">Total Item Qty</div>
    </div>
  </div>

  <?php if (empty($packages)): ?>
      <div class="pkg-empty-state">
        <i class="bi bi-box-seam"></i>
        <h5 class="mb-1">No packages yet</h5>
        <p class="mb-3">Add your first package to start tracking contents and dimensions.</p>
        <button data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-success">
          <i class="bi bi-plus-lg me-1"></i>Add New Package
        </button>
      </div>
  <?php else: ?>

      <div class="pkg-toolbar">
        <div class="pkg-search">
          <i class="bi bi-search"></i>
          <input type="text" id="packageSearch" class="form-control" placeholder="Search package #, content, dimensions…" autocomplete="off">
        </div>
        <div class="text-muted small" id="resultCount"><?= (int)$totalPackages ?> package<?= $totalPackages === 1 ? '' : 's' ?></div>
      </div>

      <div class="table-responsive-wrapper">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="packagesTable">
              <thead class="table-dark">
                  <tr>
                      <th>Package #</th>
                      <th>Content</th>
                      <th>Quantity</th>
                      <th>Keystage ID</th>
                      <th>Dimension</th>
                      <th style="width: 1%;">Actions</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($packages as $package): ?>
                      <tr>
                          <td><strong><?= htmlspecialchars($package['package_num']) ?></strong></td>
                          <td><?= $package['Content'] ?: '<span class="text-muted">—</span>' ?></td>
                          <td>
                            <?php if ($package['qty']): ?>
                              <?php foreach (explode('<br>', $package['qty']) as $q): ?>
                                <span class="qty-badge"><?= htmlspecialchars($q) ?></span><br>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </td>
                          <td><?= htmlspecialchars($package['keystage_id'] ?? '—') ?></td>
                          <td><span class="dim-chip"><?= htmlspecialchars($package['Dimension']) ?></span></td>
                          <td>
                            <div class="row-actions">
                              <a href="items.php?id=<?= (int)$project_id ?>&package_id=<?= (int)$package['package_id'] ?>"
                                 class="btn btn-primary btn-sm" title="View package items">
                                 <i class='bi bi-eye'></i><span class="d-none d-lg-inline">Packages</span>
                              </a>
                              <a href="#"
                                  class="btn btn-warning btn-sm editBtn"
                                  data-bs-toggle="modal"
                                  data-bs-target="#editModal"
                                  data-id="<?= (int)$package['package_id'] ?>"
                                  data-num="<?= htmlspecialchars($package['package_num']) ?>"
                                  data-width="<?= htmlspecialchars($package['width']) ?>"
                                  data-length="<?= htmlspecialchars($package['length']) ?>"
                                  data-height="<?= htmlspecialchars($package['height']) ?>"
                                  title="Edit package">
                                  <i class="bi bi-pencil-square"></i>
                              </a>
                              <button data-bs-toggle="modal" data-bs-target="#deleteModal"
                                      onclick="document.getElementById('delete_packages').value = <?= (int)$package['package_id'] ?>;"
                                      class="btn btn-danger btn-sm" title="Delete package">
                                  <i class="bi bi-trash"></i>
                              </button>
                            </div>
                          </td>
                      </tr>
                  <?php endforeach; ?>
                  <tr class="no-results-row d-none">
                    <td colspan="6">No packages match your search.</td>
                  </tr>
              </tbody>
          </table>
        </div>
      </div>
  <?php endif; ?>
</div>

<script>
const allItems = <?= json_encode($allItems) ?>;

/* ---------- Lightweight toast helper (replaces alert()) ---------- */
function showToast(message, type = "success") {
  const stack = document.getElementById("toastStack");
  const icon = type === "success" ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill";
  const bg = type === "success" ? "text-bg-success" : "text-bg-danger";

  const toastEl = document.createElement("div");
  toastEl.className = `toast align-items-center ${bg} border-0`;
  toastEl.setAttribute("role", "alert");
  toastEl.innerHTML = `
    <div class="d-flex">
      <div class="toast-body"><i class="bi ${icon} me-2"></i>${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  stack.appendChild(toastEl);

  if (window.bootstrap && bootstrap.Toast) {
    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
    toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
  } else {
    // Fallback if Bootstrap JS toast isn't loaded
    setTimeout(() => toastEl.remove(), 3500);
  }
}

/* ---------- Button loading-state helper ---------- */
function setButtonLoading(btn, loading, loadingText = "Saving…") {
  if (!btn) return;
  if (loading) {
    btn.dataset.originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${loadingText}`;
  } else {
    btn.disabled = false;
    if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
  }
}

/* ---------- Live search / filter ---------- */
(function initSearch() {
  const searchInput = document.getElementById("packageSearch");
  if (!searchInput) return;

  const table = document.getElementById("packagesTable");
  const rows = Array.from(table.querySelectorAll("tbody tr")).filter(r => !r.classList.contains("no-results-row"));
  const noResultsRow = table.querySelector(".no-results-row");
  const resultCount = document.getElementById("resultCount");

  searchInput.addEventListener("input", function () {
    const term = this.value.trim().toLowerCase();
    let visible = 0;

    rows.forEach(row => {
      const matches = row.innerText.toLowerCase().includes(term);
      row.classList.toggle("d-none", !matches);
      if (matches) visible++;
    });

    noResultsRow.classList.toggle("d-none", visible !== 0);
    resultCount.textContent = `${visible} package${visible === 1 ? "" : "s"}`;
  });
})();

/* ---------- Add Package form ---------- */
const addItemForm = document.getElementById("addItemForm");
if (addItemForm) {
  addItemForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const submitBtn = this.querySelector('[type="submit"]');
    setButtonLoading(submitBtn, true, "Adding…");

    let formData = new FormData(this);

    fetch("script/add_items.php", { method: "POST", body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast("Package added successfully.");
          setTimeout(() => window.location.href = data.redirect, 500);
        } else {
          setButtonLoading(submitBtn, false);
          showToast("Error: " + data.message, "error");
        }
      })
      .catch(err => {
        setButtonLoading(submitBtn, false);
        showToast("Server error: " + err, "error");
      });
  });
}

// Add More Items Button (for Add Modal)
const addMoreItemBtn = document.getElementById("addMoreItem");
if (addMoreItemBtn) {
  addMoreItemBtn.addEventListener("click", function () {
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
}

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

document.addEventListener("DOMContentLoaded", function () {
  const editButtons = document.querySelectorAll(".editBtn");
  const editItemsDiv = document.getElementById("edit_items");

  editButtons.forEach(btn => {
    btn.addEventListener("click", function () {
      let package_id = this.dataset.id;
      if (editItemsDiv) {
        editItemsDiv.innerHTML = `<div class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Loading items…</div>`;
      }

      fetch("script/get_package.php?package_id=" + package_id)
        .then(res => res.json())
        .then(resp => {
          if (!resp.success) {
            showToast(resp.message, "error");
            return;
          }

          document.getElementById("edit_package_id").value = resp.package.package_id;
          document.getElementById("edit_package_num").value = resp.package.package_num;
          document.getElementById("edit_lot_num").value = resp.package.lot_name ? "Lot " + resp.package.lot_name : "N/A";
          document.getElementById("edit_key_num").value = resp.package.keystage_name ? "Keystage " + resp.package.keystage_name + " " + resp.package.description : "No Keystage Assigned";
          document.getElementById("edit_width").value = resp.package.width;
          document.getElementById("edit_height").value = resp.package.height;
          document.getElementById("edit_length").value = resp.package.length;

          editItemsDiv.innerHTML = "";
          resp.items.forEach(it => {
            editItemsDiv.innerHTML += renderItemRow(it.item_id, it.qty);
          });
        })
        .catch(err => showToast("Failed to load package: " + err, "error"));
    });
  });

  // Add new item row
  const addItemBtn = document.getElementById("addItemBtn");
  if (addItemBtn) {
    addItemBtn.addEventListener("click", function () {
      editItemsDiv.innerHTML += renderItemRow();
    });
  }

  // Remove item row
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("removeItemBtn")) {
      e.target.closest(".itemRow").remove();
    }
  });

  const saveEditBtn = document.getElementById("saveEditBtn");
  if (saveEditBtn) {
    saveEditBtn.addEventListener("click", function () {
      setButtonLoading(saveEditBtn, true);
      let formData = new FormData(document.getElementById("editForm"));

      fetch("script/update_package.php", { method: "POST", body: formData })
        .then(res => res.json())
        .then(resp => {
          if (resp.success) {
            showToast("Package updated successfully.");
            setTimeout(() => window.location.href = resp.redirect, 500);
          } else {
            setButtonLoading(saveEditBtn, false);
            showToast("Error: " + resp.message, "error");
          }
        })
        .catch(err => {
          setButtonLoading(saveEditBtn, false);
          showToast("Server error: " + err, "error");
        });
    });
  }
});

function populateKeystage() {
  lot_id = document.getElementById('lot_id').value;
  keystage_id = document.getElementById('keystage_id');

  keystage_id.innerHTML = '';

  fetch("script/get_keystage.php?lotid=" + lot_id, { method: "GET" })
    .then(res => res.json())
    .then(data => {
      data.keystages.forEach(keystage => {
        let option = document.createElement("option");
        option.value = keystage.id;
        option.textContent = keystage.name;
        keystage_id.appendChild(option);
        keystage_id.disabled = false;
      });
    })
    .catch(err => {
      console.error("Error:", err);
      showToast("Could not load keystages.", "error");
    })
    .finally(() => {
      hideLoading();
    });
}

// Trigger whenever table changes (typing or pasting) — for the paste-in-items table, if present
const myTableEl = document.getElementById("myTable");
if (myTableEl) {
  myTableEl.addEventListener("input", syncTableToForm);
}

function syncTableToForm() {
  let rows = document.querySelectorAll("#myTable tr");
  let container = document.getElementById("itemsContainer");
  container.innerHTML = "";

  rows.forEach((row, index) => {
    if (index === 1) return; // skip the header row
    let cells = row.querySelectorAll("td");
    if (cells.length < 1) return;

    let itemText = (cells[0]?.innerText || "").trim();
    let qtyText = (cells[1]?.innerText || "").trim();
    let dimText = (cells[2]?.innerText || "").trim();

    let normalizedDim = "";
    if (dimText) {
      normalizedDim = dimText.replace(/\s*/g, "").replace(/[X×]/gi, "x");
    }

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

function normalize(str) {
  return str
    .toLowerCase()
    .replace(/[^\w\s]/gi, "")
    .trim();
}
</script>

<script src="assets/js/project_details.js"></script>
<?php require "template/footer.php"; ?>