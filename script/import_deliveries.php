<?php
require "../config/db.php"; // PDO $pdo

$project_id = $_POST['project'] ?? null;
$rows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $fileTmp = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($fileTmp, "r")) !== false) {
        $header = fgetcsv($handle); // skip header

        while (($data = fgetcsv($handle)) !== false) {
            $school_id = $data[0];

            // Start from column 1 (deliveries) and process in pairs
            for ($i = 1; $i < count($data); $i += 3) {
                $cell = trim($data[$i]);       // delivery info
                $dr_no = $data[$i + 1] ?? '';  // dr_number
                $delivery_date = $data[$i + 2] ?? ''; // Delivery date (new column)

                // Normalize delivery date
                $delivery_date = trim($delivery_date);
                if ($delivery_date === '' || $delivery_date === '00-00-0000') {
                    $delivery_date = '0000-00-00';
                } else {
                    // Try to convert formats like 01/02/2025 → 2025-02-01
                    $rawDate = $data[$i + 2] ?? ''; // assuming your CSV layout now has delivery date in the column after dr_no
                    $delivery_date = parseDateToYmd($rawDate);
                }

                if ($cell === "") continue;

                // Split multiple deliveries separated by "&"
                $deliveries = preg_split("/\s*&\s*/", $cell);
                foreach ($deliveries as $d) {
                    preg_match("/(C\d+)/", $d, $pkg);
                    preg_match("/LOT\s*(\d+)/i", $d, $lot);
                    preg_match("/KS(\d+)/i", $d, $ks);
                    // Capture everything after KS<num> until "&" or end of string
                    preg_match("/KS\s*\d+\s*([^\&]*)/i", $d, $desc);
                    $description = isset($desc[1]) ? preg_replace('/\s+/', '', $desc[1]) : '';

                    $rows[] = [
                        'school_id'     => $school_id,
                        'dr_no'         => $dr_no,
                        'delivery_date' => $delivery_date,
                        'package_type'  => $pkg[1] ?? '',
                        'lot_name'      => $lot[1] ?? '',
                        'keystage_num'  => $ks[1] ?? '',
                        'description'   => $description ?? '',
                    ];
                }
            }
        }
        fclose($handle);
    }

    // Fetch existing Lots & Keystages
    $stmt = $pdo->prepare("SELECT lot_id, lot_name FROM lot WHERE project_id =  :project_id");
    $stmt->execute(['project_id' => $project_id]);
    $lots = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->prepare("
        SELECT k.keystage_id, CONCAT('KS', k.keystage_num, ' ', k.description) AS label
        FROM keystage k
        JOIN lot l ON k.lot_id = l.lot_id
        WHERE l.project_id = :project_id
    ");
    $stmt->execute(['project_id' => $project_id]);
    $keystages = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Import Deliveries</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
  <h3>Import Deliveries</h3>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="project" value="<?= htmlspecialchars($project_id ?? '') ?>">
    <input type="file" name="csv_file" accept=".csv" required>
    <button class="btn btn-primary btn-sm" type="submit">Upload</button>
    <button type="button" class="btn btn-warning mb-2" onclick="toggleNeedsAction()">Show Only Rows Needing Dropdown</button>
  </form>

  <?php if (!empty($rows)): ?>
  <form method="POST" action="save_deliveries.php">
    <input type="hidden" name="project" value="<?= htmlspecialchars($project_id) ?>">
    <table class="table table-bordered mt-3">
      <thead>
        <tr>
          <th>School ID</th>
          <th>DR No</th>
          <th>Package Type</th>
          <th>Lot</th>
          <th>Keystage</th>
          <th>Date</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $idx => $r): ?>
  <?php
    $needsLot = !in_array($r['lot_name'], $lots);
    $foundKs = null;
        $stmt = $pdo->prepare("
        SELECT keystage_id 
        FROM keystage k
        JOIN lot l ON k.lot_id = l.lot_id
        WHERE l.project_id = :project_id
        AND k.keystage_num = :num
        AND k.description = :desc
    ");
    $stmt->execute([
        'project_id' => $project_id,
        'num'        => $r['keystage_num'],
        'desc'       => $r['description'],
    ]);
    $foundKs = $stmt->fetchColumn();

    $needsKeystage = !$foundKs;
    $needsAction = $needsLot || $needsKeystage;
  ?>
  <tr class="<?= $needsAction ? 'needs-action' : '' ?>">
    <td><?= htmlspecialchars($r['school_id']) ?>
      <input type="hidden" name="rows[<?= $idx ?>][school_id]" value="<?= $r['school_id'] ?>">
    </td>
    <td><?= htmlspecialchars($r['dr_no']) ?>
      <input type="hidden" name="rows[<?= $idx ?>][dr_no]" value="<?= $r['dr_no'] ?>">
    </td>
    <td><?= htmlspecialchars($r['package_type']) ?>
      <input type="hidden" name="rows[<?= $idx ?>][package_type]" value="<?= $r['package_type'] ?>">
    </td>

    <!-- Lot -->
    <td>
      <?php if (!$needsLot): ?>
        <?= $r['lot_name'] ?>
        <input type="hidden" name="rows[<?= $idx ?>][lot_id]" value="<?= array_search($r['lot_name'], $lots) ?>">
      <?php else: ?>
        <select name="rows[<?= $idx ?>][lot_id]" class="form-select">
          <option value="">-- Select Lot --</option>
          <?php foreach ($lots as $id => $name): ?>
            <option value="<?= $id ?>"><?= $name ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </td>

    <!-- Keystage -->
    <td>
      <?php if (!$needsKeystage): ?>
        <?= $keystages[$foundKs] ?>
        <input type="hidden" name="rows[<?= $idx ?>][keystage_id]" value="<?= $foundKs ?>">
      <?php else: ?>
        <select name="rows[<?= $idx ?>][keystage_id]" class="form-select">
          <option value="">-- Select Keystage --</option>
          <?php foreach ($keystages as $id => $label): ?>
            <option value="<?= $id ?>"><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </td>

<!-- Delivery Date -->
<td>
    <?php
        $valueForInput = ($r['delivery_date'] !== '0000-00-00') ? $r['delivery_date'] : '';
        echo '<input type="date" name="rows['.$idx.'][delivery_date]" class="form-control" value="'.htmlspecialchars($valueForInput).'">';
    ?>
</td>


    <td><?= htmlspecialchars($r['description']) ?></td>
  </tr>
<?php endforeach; ?>
      </tbody>
    </table>
    <button class="btn btn-success" type="submit">Confirm Import</button>
  </form>
  <?php endif; ?>
</body>
</html>

<script>
let showingOnly = false;
function toggleNeedsAction() {
  showingOnly = !showingOnly;
  document.querySelectorAll("tbody tr").forEach(tr => {
    if (showingOnly) {
      tr.style.display = tr.classList.contains("needs-action") ? "" : "none";
    } else {
      tr.style.display = "";
    }
  });
}
</script>
<?php

/**
 * Parse various date inputs into 'Y-m-d' or return '0000-00-00' if not parseable.
 *
 * Accepts: "2025-02-01", "01-02-2025", "02/01/2025", "1/2/25", Excel serial (e.g. 44500),
 * or returns '0000-00-00' for blanks / invalid values.
 */
function parseDateToYmd($raw) {
    $s = trim((string)$raw);

    if ($s === '' || strtoupper($s) === '00-00-0000') {
        return '0000-00-00';
    }

    // Common formats to try (add/subtract as needed)
    $formats = [
        'Y-m-d', 'Y/m/d', 'Y.m.d',
        'd-m-Y', 'd/m/Y', 'd.m.Y',
        'm/d/Y', 'n/j/Y', 'j/n/Y',
        'd-M-Y', 'M d, Y'
    ];

    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt && $dt->format($fmt) === $dt->format($fmt)) { // basic sanity
            return $dt->format('Y-m-d');
        }
    }

    // Try strtotime() (handles many human formats)
    $ts = strtotime($s);
    if ($ts !== false && $ts !== -1) {
        return date('Y-m-d', $ts);
    }

    // If numeric, it might be an Excel serial date (days since 1899-12-31 with Excel leap bug)
    // Convert Excel serial to Unix timestamp.
    if (is_numeric($s)) {
        $serial = floatval($s);

        // Excel stores dates as serials where 25569 = 1970-01-01
        // Use GMT to avoid timezone issues
        $unix = ($serial - 25569) * 86400;

        // Round and convert
        if (is_finite($unix)) {
            return gmdate('Y-m-d', (int) round($unix));
        }
    }

    // Fallback - not parseable
    return '0000-00-00';
}


?>