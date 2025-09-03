<?php
require "../config/db.php"; // adjust

$page    = $_POST['page'] ?? '';
$columns = json_decode($_POST['columns'] ?? '[]', true);
$search  = $_POST['search'] ?? '';

if (!$page || empty($columns)) {
    echo json_encode([]);
    exit;
}

// 🔒 Security: allow only specific tables to prevent SQL injection
$allowedTables = ["school", "lot", "keystage","package"];
if (!in_array($page, $allowedTables)) {
    echo json_encode([]);
    exit;
}

// Build WHERE dynamically with placeholders
$where = [];
$params = [];
foreach ($columns as $col) {
    $where[] = "$col LIKE ?";
    $params[] = "%$search%";
}

$sql = "SELECT * FROM $page WHERE " . implode(" OR ", $where);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
