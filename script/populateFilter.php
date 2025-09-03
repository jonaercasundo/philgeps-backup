<?php
require "../config/db.php"; // adjust

$query    = $_POST['query'] ?? '';

if (!$query) {
    echo json_encode([]);
    exit;
}



$sql = "$query";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
