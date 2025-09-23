<?php
require "../config/db.php"; 

$where = [];
$params = [];

// Year
if (!empty($_POST['year'])) {
    $where[] = "YEAR(d.delivery_date) = :year";
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
    $where[] = "(p.project_name LIKE :search OR s.school_name LIKE :search OR s.address LIKE :search OR d.dr_no LIKE :search)";
    $params[':search'] = "%" . $_POST['search'] . "%";
}

// Pagination
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT 
            d.delivery_id,
            p.project_name,
            s.school_id,
            s.school_name,
            s.address,
            d.package_type,
            d.dr_no,
            d.delivery_date,
            d.status,
            COALESCE(pkg_items.items_contents, '') AS items_contents
        FROM deliveries d
        JOIN projects p ON d.project_id = p.project_id
        JOIN school s   ON d.school_id = s.school_id

        LEFT JOIN (
            SELECT 
                x.delivery_id,
                GROUP_CONCAT(
                    CONCAT(
                        'Package ', x.rn, ' out of ', x.total_packages, '<br>',
                        x.items
                    )
                    SEPARATOR '<br><br>'
                ) AS items_contents
            FROM (
                SELECT 
                    d.delivery_id,
                    p.package_id,
                    ROW_NUMBER() OVER (PARTITION BY d.delivery_id ORDER BY p.package_id) AS rn,
                    COUNT(*) OVER (PARTITION BY d.delivery_id) AS total_packages,
                    GROUP_CONCAT(CONCAT(i.item_id, ' - ', i.item_name, ' (', pc.qty, ')') SEPARATOR '<br>') AS items
                FROM deliveries d
                LEFT JOIN package p 
                    ON ( (d.keystage_id IS NOT NULL AND d.keystage_id = p.keystage_id)
                        OR (d.lot_id IS NOT NULL AND d.lot_id = p.lot_id) )
                JOIN package_content pc ON pc.package_id = p.package_id
                JOIN item i ON pc.item_id = i.item_id
                GROUP BY d.delivery_id, p.package_id
            ) x
            GROUP BY x.delivery_id
        ) pkg_items ON pkg_items.delivery_id = d.delivery_id";


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
             JOIN projects p ON p.project_id = d.project_id
             LEFT JOIN school s ON d.school_id = s.school_id";
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
