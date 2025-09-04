<?php
require "../config/db.php"; 

$where = [];
$params = [];

// Year
if (!empty($_POST['region'])) {
    $where[] = "region = :region";
    $params[':region'] = $_POST['region'];
}

// Project
if (!empty($_POST['division'])) {
    $where[] = "division = :division";
    $params[':division'] = $_POST['division'];
}

// Status
if (!empty($_POST['municipality'])) {
    $where[] = "municipality = :municipality";
    $params[':municipality'] = $_POST['municipality'];
}

// Search
if (!empty($_POST['search'])) {
    $where[] = "(school_name LIKE :search OR school_id LIKE :search)";
    $params[':search'] = "%" . $_POST['search'] . "%";
}

// Pagination
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT *
        FROM school";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY school_id ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count for pagination
$countSql = "SELECT COUNT(*) 
             FROM school";
if ($where) {
    $countSql .= " WHERE " . implode(" AND ", $where);
}
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$total_rows = $countStmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

echo json_encode([
    "rows" => $rows,
    "total_pages" => $total_pages,
    "current_page" => $page
]);
