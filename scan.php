<?php 
$question = include "captcha.php"; 
require 'config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$logoPath = __DIR__ . "/assets/uploads/logo/logo.webp";
$logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

try {
    $stmt = $pdo->prepare("
    SELECT d.*, p.project_id, p.project_name, dp.keystage_id
    FROM deliveries d 
    JOIN projects p ON p.project_id = d.project_id 
    JOIN delivery_packages dp ON dp.delivery_id = d.delivery_id 
    WHERE d.delivery_id=$id;
    ");
    $stmt->execute();
    $deliveries = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT p.*, pc.item_id, pc.qty, i.item_name
    FROM package p
    JOIN package_content pc ON pc.package_id = p.package_id
    JOIN item i ON i.item_id = pc.item_id
    WHERE keystage_id = ?
    ");
    $stmt->execute([$deliveries['keystage_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
      <h6 class="text-muted"><?=ucfirst($deliveries['remarks'])?></h6>
      <p class="mb-0"><?=$deliveries['address']?></p>
    </div>

    <!-- Items Table -->
    <div class="items mb-4">
      <h5 class="mb-2">Packing List</h5>
      <table>
        <tr>
          <th>Item</th>
          <th>Quantity</th>
        </tr>
        <?php foreach ($items as $item){ ?>
          <tr>
            <td><?=$item['item_name']?></td>
            <td><?=$item['qty']?></td>
          </tr>
        <?php } ?>
      </table>
    </div>

    <!-- Form -->
    <form method="POST" action="check.php">
      <input type="hidden" value="<?=$id?>" name="id">
      <input type="hidden" value="<?=$deliveries['status'];?>" name="status">
      <div class="mb-3">
        <label for="phone" class="form-label">Phone Number</label>
        <input 
          type="tel" 
          class="form-control" 
          id="phone" 
          name="phone" 
          placeholder="09XXXXXXXXX"
          pattern="[0-9]{11}" 
          maxlength="11" 
          required 
          oninput="this.value = this.value.replace(/[^0-9]/g, '')"
        >
        <div class="form-text">Enter 11-digit phone number starting with 09</div>
      </div>
      
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input 
          type="email" 
          class="form-control" 
          id="email" 
          name="email" 
          placeholder="anyemail@gmail.com"
          required 
        >
        <div class="form-text">Enter a valid email address</div>
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

      <button type="submit" class="btn btn-primary w-100">Submit</button>
    </form>
  </div>
</div>
</body>
</html>
