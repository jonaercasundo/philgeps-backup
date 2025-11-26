<?php
require "../config/db.php";

$project_id = $_GET['project_id'] ?? '';
$from = (int)($_GET['from'] ?? 0);
$to   = (int)($_GET['to'] ?? 0);

if (!$project_id || !$from || !$to) {
  echo json_encode([]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT DISTINCT(school_id)
  FROM deliveries
  WHERE project_id = ?
    AND CAST(school_id AS UNSIGNED) BETWEEN ? AND ?
  ORDER BY CAST(school_id AS UNSIGNED)
");
$stmt->execute([$project_id, $from, $to]);
$school_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($school_ids);
?>