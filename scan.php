<?php 
$question = include "captcha.php"; 
require 'config/db.php';


$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$logoPath = __DIR__ . "/assets/uploads/logo/logo.webp";
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

try {
    $stmt = $pdo->prepare("
    SELECT d.*, p.project_id, p.project_name, d.keystage_id, s.school_name as school, s.address, ps.status AS package_status
    FROM package_status ps 
    JOIN deliveries d ON d.delivery_id = ps.delivery_id
    JOIN school s ON s.school_id = d.school_id 
    JOIN projects p ON p.project_id = d.project_id 
    WHERE package_status_id=$id;
    ");
    $stmt->execute();
    $deliveries = $stmt->fetch(PDO::FETCH_ASSOC);

// Extract numeric part from package_type
$multiplier = 1;
if (!empty($deliveries['package_type'])) {
    $numeric = preg_replace('/[^0-9]/', '', $deliveries['package_type']);
    $multiplier = $numeric !== '' ? (int)$numeric : 1;
}

    $stmt = $pdo->prepare("
    SELECT p.*, pc.item_id, pc.qty, i.item_name
    FROM package_status ps
    JOIN package p ON p.package_id = ps.package_id
    JOIN package_content pc ON pc.package_id = p.package_id
    JOIN item i ON i.item_id = pc.item_id
    WHERE package_status_id= $id;
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    function getRequiredQty($item, $deliveries, $multiplier)
    {
        $name = strtolower(trim($item['item_name']));

        if (str_contains($name, 'teacher')) {
            // Teacher's Manual
            return (int)$deliveries['qty_teachers_manual'];
        }

        if (str_contains($name, 'textbook')) {
            // Student Textbook
            return (int)$deliveries['package_qty'];
        }

        // Default for all other items
        return $item['qty'] * $multiplier;
    }
    // NEW: Fetch current inventory quantities for comparison
    $warehouse_id = $_SESSION['warehouse_id'] ?? null;
    $inventoryQuantities = [];
    $sufficientQuantities = true; 
    $insufficientItems = [];
    
  if ($warehouse_id && $deliveries['package_status'] === 'pending') {
      $stmt = $pdo->prepare("
          SELECT i.item_id, SUM(i.qty) as total_qty 
          FROM inventory i 
          WHERE i.warehouse_id = ? AND i.inventory_status = 'Approved'
          GROUP BY i.item_id
      ");
      $stmt->execute([$warehouse_id]);
      $inventoryResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Convert to associative array for easy lookup
      foreach ($inventoryResults as $inv) {
          $inventoryQuantities[$inv['item_id']] = $inv['total_qty'];
      }

      // Check if quantities are sufficient - ONLY FOR PENDING STATUS
      foreach ($items as $index => $item) {
          $requiredQty = getRequiredQty($item, $deliveries, $multiplier);
          $availableQty = $inventoryQuantities[$item['item_id']] ?? 0;
          
          if ($availableQty < $requiredQty) {
              $sufficientQuantities = false;
              $insufficientItems[] = [
                  'item_name' => $item['item_name'],
                  'required' => $requiredQty,
                  'available' => $availableQty
              ];
          }
      }
  } else {
      // For non-pending packages, always allow submission
      $sufficientQuantities = true;
  }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receive of Items</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }
    .card {
      max-width: 650px;
      margin: auto;
      border: 1px solid #ddd;
    }
    .items table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    .items th, .items td {
      border: 1px solid #dee2e6;
      padding: 8px 12px;
      text-align: left;
    }
    .items th {
      background-color: #f1f1f1;
      font-weight: 600;
    }
    .items td {
      vertical-align: top;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <div class="card p-4 shadow-lg rounded-3">
    
    <!-- Header -->
    <div class="text-center mb-3">
      <img src="<?=$logoBase64?>" alt="Logo" style="max-height:60px;">
      <h4 class="mt-3"><?=$deliveries['project_name']?></h4>
      <h6 class="text-muted"><?=$deliveries['school']?></h6>
      <h6 class="text-muted"><?=ucfirst($deliveries['dr_no'])?></h6>
      <p class="mb-0"><?=$deliveries['address']?></p>
    </div>

<!-- Items Table -->
<div class="items mb-4">
    <h5 class="mb-2">Packing List</h5>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>

                <?php if ($deliveries['package_status'] === 'pending'): ?>
                    <th>Available</th>
                <?php endif; ?>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($items as $index => $item): ?>

                <?php
                    // Compute required quantity first
                    $actualQty = getRequiredQty($item, $deliveries, $multiplier);

                    if ($deliveries['package_status'] === 'pending') {
                        $availableQty = $inventoryQuantities[$item['item_id']] ?? 0;
                        $isSufficient = $availableQty >= $actualQty;
                    } else {
                        $availableQty = null;
                        $isSufficient = true;
                    }
                ?>

                <tr class="<?= ($deliveries['package_status'] === 'pending' && !$isSufficient) ? 'insufficient-item' : '' ?>">
                    <td><?= htmlspecialchars($item['item_name']) ?></td>

                    <td><?= $actualQty ?></td>

                    <?php if ($deliveries['package_status'] === 'pending'): ?>
                        <td>
                            <?= $availableQty ?>

                            <?php if (!$isSufficient): ?>
                                <div class="quantity-warning">
                                    <?= $actualQty - $availableQty ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($deliveries['package_status'] === 'pending' && !$sufficientQuantities): ?>
        <div class="alert alert-warning mt-3">
            <strong>Warning:</strong>
            Some items do not have sufficient inventory. Please replenish the required quantities before submitting.
        </div>
    <?php endif; ?>
</div>

    <!-- Form -->
    <form method="POST" action="check.php" enctype="multipart/form-data">
      <input type="hidden" value="<?=$id?>" name="id">
      <input type="hidden" value="<?=$deliveries['status'];?>" name="status">
      <input type="hidden" name="delivery_id" value="<?=$_GET['delivery_id']?>">

      <div class="mb-3">
          <label for="photo_upload" class="form-label">Upload Photos (Optional)</label>
          <input
              type="file"
              class="form-control"
              id="photo_upload"
              name="photo_upload[]"
              accept="image/*"
              multiple
          >
      </div>

      <!-- Captcha -->
      <div class="mb-3">
        <label for="captcha_answer" class="form-label"><?=$question?></label>
        <input 
          type="text" 
          class="form-control" 
          id="captcha_answer" 
          name="captcha_answer" 
          placeholder="Enter your answer" 
          required
        >
      </div>

      <button type="submit" class="btn btn-primary w-100" id="submitBtn" 
        <?= ($deliveries['package_status'] === 'pending' && !$sufficientQuantities) ? 'disabled' : '' ?>>
        <?= ($deliveries['package_status'] === 'pending' && !$sufficientQuantities) ? 'Insufficient Inventory' : 'Submit' ?>
      </button>
      
      <?php if ($deliveries['package_status'] === 'pending' && !$sufficientQuantities): ?>
        <div class="alert alert-danger mt-2">
          <strong>Cannot Submit:</strong> The following items are insufficient:
          <ul class="mb-0">
            <?php foreach ($insufficientItems as $insufficient): ?>
              <li>
                <?=$insufficient['item_name']?>: 
                Required <?=$insufficient['required']?>, 
                Available <?=$insufficient['available']?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>
</body>
</html>
