<?php
require "../config/db.php";

$project_id = $_GET['project_id'] ?? '';
$status = $_GET['status'] ?? '';
$from = (int)($_GET['from'] ?? 0);
$to   = (int)($_GET['to'] ?? 0);

if (!$project_id || !$status || !$from || !$to) {
  echo json_encode([]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT DISTINCT(dr_no)
  FROM deliveries
  WHERE project_id = ?
    AND LOWER(status) = LOWER(?)
    AND CAST(dr_no AS UNSIGNED) BETWEEN ? AND ?
  ORDER BY CAST(dr_no AS UNSIGNED)
");
$stmt->execute([$project_id, $status, $from, $to]);
$dr_numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($dr_numbers);
?>
