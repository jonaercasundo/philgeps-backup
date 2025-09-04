<?php
require "../config/db.php"; 

$where = [];
$params = [];

// Year
if (!empty($_POST['year'])) {
    $where[] = "YEAR(d.created_at) = :year";
    $params[':year'] = $_POST['year'];
}

// Project
if (!empty($_POST['project_id'])) {
    $where[] = "d.project_id = :project_id";
    $params[':project_id'] = $_POST['project_id'];
}

// Status
if (!empty($_POST['status'])) {
    $where[] = "d.status = :status";
    $params[':status'] = $_POST['status'];
}

// Search
if (!empty($_POST['search'])) {
    $where[] = "(p.project_name LIKE :search OR d.school LIKE :search OR d.address LIKE :search OR d.remarks LIKE :search OR d.dr_no LIKE :search)";
    $params[':search'] = "%" . $_POST['search'] . "%";
}

// Pagination
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT d.*, p.project_name
        FROM deliveries d 
        JOIN projects p ON p.project_id = d.project_id";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY d.delivery_id ASC LIMIT :limit OFFSET :offset";
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
             FROM deliveries d 
             JOIN projects p ON p.project_id = d.project_id";
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
