<?php
require "../config/db.php";
session_start();

// Example: Replace with PhpSpreadsheet-parsed rows stored in session
$rows = $_SESSION['import_rows'] ?? [];

// Fetch all lots and keystages for dropdowns
$lots = $pdo->query("SELECT lot_id, lot_name FROM lots ORDER BY lot_name")->fetchAll(PDO::FETCH_ASSOC);
$keystages = $pdo->query("SELECT keystage_id, keystage_number, description FROM keystages ORDER BY keystage_number, description")->fetchAll(PDO::FETCH_ASSOC);

function extractKeystageNumber($str) {
    if (preg_match('/KS(\d+)/i', $str, $m)) {
        return $m[1];
    }
    return null;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Import</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        select { padding: 4px; }
        .ok { background: #c8e6c9; }  /* green */
        .err { background: #ffcdd2; } /* red */
    </style>
</head>
<body>
<h2>Verify Import Data</h2>

<form method="post" action="import_confirm.php">
<table>
    <thead>
        <tr>
            <th>Row</th>
            <th>Lot</th>
            <th>Keystage Raw</th>
            <th>Description</th>
            <th>Lot ID</th>
            <th>Keystage ID</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
<?php
$rowNum = 1;
foreach ($rows as $index => $row) {
    $lot = $row['lot'] ?? '';
    $keystageRaw = $row['keystage'] ?? '';
    $description = $row['keystage_desc'] ?? '';

    // Try to resolve lot
    $lotStmt = $pdo->prepare("SELECT lot_id FROM lots WHERE lot_name = ?");
    $lotStmt->execute([$lot]);
    $lot_id = $lotStmt->fetchColumn();

    // Extract KS number
    $ksNum = extractKeystageNumber($keystageRaw);

    // Try to resolve keystage
    $ksStmt = $pdo->prepare("SELECT keystage_id FROM keystages WHERE keystage_number = ? AND description = ?");
    $ksStmt->execute([$ksNum, $description]);
    $ks_id = $ksStmt->fetchColumn();

    $status = "OK";
    $class = "ok";

    if (!$lot_id || !$ks_id) {
        $status = "Needs correction";
        $class = "err";
    }
    ?>
    <tr class="<?= $class ?>">
        <td><?= $rowNum ?></td>
        <td><?= htmlspecialchars($lot) ?></td>
        <td><?= htmlspecialchars($keystageRaw) ?></td>
        <td><?= htmlspecialchars($description) ?></td>
        <td>
            <?php if ($lot_id): ?>
                <?= $lot_id ?>
                <input type="hidden" name="rows[<?= $index ?>][lot_id]" value="<?= $lot_id ?>">
            <?php else: ?>
                <select name="rows[<?= $index ?>][lot_id]" required>
                    <option value="">-- Select Lot --</option>
                    <?php foreach ($lots as $l): ?>
                        <option value="<?= $l['lot_id'] ?>"><?= htmlspecialchars($l['lot_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($ks_id): ?>
                <?= $ks_id ?>
                <input type="hidden" name="rows[<?= $index ?>][keystage_id]" value="<?= $ks_id ?>">
            <?php else: ?>
                <select name="rows[<?= $index ?>][keystage_id]" required>
                    <option value="">-- Select Keystage --</option>
                    <?php foreach ($keystages as $ks): ?>
                        <option value="<?= $ks['keystage_id'] ?>">
                            KS<?= $ks['keystage_number'] ?> - <?= htmlspecialchars($ks['description']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </td>
        <td><?= $status ?></td>
    </tr>
    <?php
    $rowNum++;
}
?>
    </tbody>
</table>

<br>
<button type="submit">Confirm & Import</button>
</form>
</body>
</html>
