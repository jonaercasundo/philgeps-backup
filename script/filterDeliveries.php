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

// Lot
if (!empty($_POST['lot_id'])) {
    $where[] = "d.lot_id = :lot_id";
    $params[':lot_id'] = $_POST['lot_id'];
}

// Keystage
if (!empty($_POST['keystage_id'])) {    
    $where[] = "d.keystage_id = :keystage_id";
    $params[':keystage_id'] = $_POST['keystage_id'];
}

// Region
if (!empty($_POST['region'])) {
    $where[] = "s.region = :region";
    $params[':region'] = $_POST['region'];
}

// Division
if (!empty($_POST['division'])) {
    $where[] = "s.division = :division";
    $params[':division'] = $_POST['division'];
}

// Municipality
if (!empty($_POST['municipality'])) {    
    $where[] = "s.municipality = :municipality";
    $params[':municipality'] = $_POST['municipality'];
}


// Search
if (!empty($_POST['search'])) {
    $where[] = "(
        s.school_name LIKE :search
        OR s.school_id LIKE :search
        OR s.address LIKE :search
        OR d.dr_no LIKE :search
        OR s.region LIKE :search
        OR s.division LIKE :search
        OR s.municipality LIKE :search
    )";
    $params[':search'] = "%" . $_POST['search'] . "%";
}

// Date range filtering based on status
if (!empty($_POST['status']) && !empty($_POST['start_date']) && !empty($_POST['end_date'])) {
    $status = strtolower($_POST['status']);
    if ($status === 'accepted') {
        $where[] = "d.accepted_date BETWEEN :start_date AND :end_date";
    } elseif ($status === 'delivered') {
        $where[] = "d.delivered_date BETWEEN :start_date AND :end_date";
    }
    $params[':start_date'] = $_POST['start_date'];
    $params[':end_date'] = $_POST['end_date'];
}


// Pagination
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
$offset = ($page - 1) * $limit;

$sql = "
SELECT
    d.delivery_id,
    p.project_name,
    s.school_id,
    s.school_name,
    s.address,
    d.package_type,
    d.dr_no,
    d.delivery_date,
    d.status,
    d.accepted_date,
    d.delivered_date,
    k.keystage_num,
    k.description,
    l.lot_name,
    w.warehouse_id,
    w.warehouse_name,
    COALESCE(pkg_items.items_contents, '') AS items_contents
        FROM deliveries d
        LEFT JOIN keystage k ON k.keystage_id = d.keystage_id
        JOIN lot l ON l.lot_id = d.lot_id
        JOIN projects p ON d.project_id = p.project_id
        JOIN school s   ON d.school_id = s.school_id
        LEFT JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
        LEFT JOIN warehouse w ON ll.warehouse_id = w.warehouse_id

LEFT JOIN (
    SELECT
        x.delivery_id,
        GROUP_CONCAT(
            CONCAT(
                'Package ', x.rn, ' out of ', x.total_packages,
                ' — ', x.colored_pkg_status, '<br>',
                x.items
            )
            SEPARATOR '<br><br>'
        ) AS items_contents
    FROM (
        SELECT
            d.delivery_id,
            p.package_id,

            ROW_NUMBER() OVER (
                PARTITION BY d.delivery_id
                ORDER BY p.package_id
            ) AS rn,

            COUNT(*) OVER (
                PARTITION BY d.delivery_id
            ) AS total_packages,

            GROUP_CONCAT(
                CONCAT(
                    i.item_name,
                    ' (',
                    pc.qty * d.package_qty,
                    ')'
                )
                SEPARATOR '<br>'
            ) AS items,

            CASE
                WHEN COALESCE(MAX(dp.status), 'PENDING') = 'DELIVERED' THEN
                    '<span class=\"text-success font-weight-bold\">DELIVERED</span>'
                WHEN COALESCE(MAX(dp.status), 'PENDING') = 'ACCEPTED' THEN
                    '<span class=\"text-primary font-weight-bold\">ACCEPTED</span>'
                WHEN COALESCE(MAX(dp.status), 'PENDING') = 'WAREHOUSE' THEN
                    '<span class=\"text-info font-weight-bold\">WAREHOUSE</span>'
                ELSE
                    '<span class=\"text-warning font-weight-bold\">PENDING</span>'
            END AS colored_pkg_status

        FROM deliveries d

        LEFT JOIN package p
            ON (
                (d.keystage_id IS NOT NULL AND d.keystage_id = p.keystage_id)
                OR
                (d.keystage_id IS NULL AND d.lot_id = p.lot_id)
            )

        JOIN package_content pc ON pc.package_id = p.package_id
        JOIN item i ON pc.item_id = i.item_id

        LEFT JOIN package_status dp
            ON dp.delivery_id = d.delivery_id
           AND dp.package_id = p.package_id

        GROUP BY
            d.delivery_id,
            p.package_id,
            d.package_qty
    ) x
    GROUP BY x.delivery_id
) pkg_items ON pkg_items.delivery_id = d.delivery_id
        ";


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

foreach ($rows as &$row) {
    $stmt_check = $pdo->prepare("
        SELECT COUNT(dp.delivery_photo_id)
        FROM deliveries d
        JOIN package_status ps ON d.delivery_id = ps.delivery_id
        JOIN delivery_photo dp ON ps.package_status_id = dp.package_status_id
        WHERE d.dr_no = :dr_no AND dp.status IN ('accepted', 'delivered')
    ");
    $stmt_check->execute([':dr_no' => $row['dr_no']]);
    $row['has_photos'] = ($stmt_check->fetchColumn() > 0);
}

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

$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['dr_no']]['dr_no'] = $r['dr_no'];
    $grouped[$r['dr_no']]['project_name'] = $r['project_name'];
    $grouped[$r['dr_no']]['school_name'] = $r['school_name'];
    $grouped[$r['dr_no']]['status'] = $r['status'];
    $grouped[$r['dr_no']]['deliveries'][] = $r;
    $grouped[$r['dr_no']]['school_id'][] = $r['school_id'];
}
echo json_encode([
    'rows' => array_values($grouped), 
    'total_pages' => $total_pages,
    "current_page" => $page
]);
