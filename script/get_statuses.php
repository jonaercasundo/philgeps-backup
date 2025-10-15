<?php
require "../config/db.php";

$project_id = $_GET['project_id'] ?? '';

if (!$project_id) {
  echo json_encode([]);
  exit;
}

$stmt = $pdo->prepare("SELECT DISTINCT(status) FROM deliveries WHERE project_id = ?");
$stmt->execute([$project_id]);
$statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($statuses);
?>
