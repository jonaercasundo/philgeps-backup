<?php
require "../config/db.php"; 

// --- Input Validation and Pagination ---
$limit = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$offset = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;

// --- Search Parameter ---
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// --- Sorting Parameters ---
$columnIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
$columnDir = isset($_GET['order'][0]['dir']) && $_GET['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';

// Map column index to actual database column names
$columns = [
    0 => 'd.delivery_id',
    1 => 'p.project_name',
    2 => 's.school_name',
    3 => 'd.dr_no',
    4 => 'd.delivery_date',
    5 => 'd.package_type',
    6 => 'items_contents' // This column is not orderable in your DataTable config
];

// Get the column to sort by (default to delivery_date if invalid)
$orderColumn = isset($columns[$columnIndex]) ? $columns[$columnIndex] : 'd.delivery_date';

// --- Build WHERE clause for search ---
$whereClause = "d.status = 'warehouse'";
$searchConditions = [];

if (!empty($searchValue)) {
    $searchConditions[] = "d.delivery_id LIKE :search";
    $searchConditions[] = "p.project_name LIKE :search";
    $searchConditions[] = "s.school_name LIKE :search";
    $searchConditions[] = "d.dr_no LIKE :search";
    $searchConditions[] = "d.delivery_date LIKE :search";
    $searchConditions[] = "d.package_type LIKE :search";
    
    $whereClause .= " AND (" . implode(" OR ", $searchConditions) . ")";
}

// --- SQL Query to Fetch Warehouse Deliveries ---
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
            COALESCE(pkg_items.items_contents, '') AS items_contents
        FROM deliveries d
        JOIN projects p 
            ON d.project_id = p.project_id
        JOIN school s 
            ON d.school_id = s.school_id
        LEFT JOIN (
            SELECT 
                x.delivery_id,
                GROUP_CONCAT(
                    CONCAT(
                        'Package ', x.rn, ' out of ', x.total_packages, 
                        ' — ', x.colored_pkg_status, '<br>', 
                        x.items
                    ) SEPARATOR '<br><br>'
                ) AS items_contents
            FROM (
                SELECT 
                    d.delivery_id,
                    p.package_id,
                    ROW_NUMBER() OVER (PARTITION BY d.delivery_id ORDER BY p.package_id) AS rn,
                    COUNT(*) OVER (PARTITION BY d.delivery_id) AS total_packages,
                    GROUP_CONCAT(CONCAT(i.item_name, ' (', pc.qty, ')') SEPARATOR '<br>') AS items,
                    CASE 
                        WHEN COALESCE(MAX(dp.status), 'PENDING') = 'DELIVERED' THEN 
                            CONCAT('<span class=\"text-success font-weight-bold\">DELIVERED</span>') 
                        WHEN COALESCE(MAX(dp.status), 'PENDING') = 'ACCEPTED' THEN 
                            CONCAT('<span class=\"text-primary font-weight-bold\">ACCEPTED</span>') 
                        WHEN COALESCE(MAX(dp.status), 'PENDING') = 'WAREHOUSE' THEN 
                            CONCAT('<span class=\"text-info font-weight-bold\">WAREHOUSE</span>') 
                        ELSE 
                            CONCAT('<span class=\"text-warning font-weight-bold\">PENDING</span>') 
                    END AS colored_pkg_status
                FROM deliveries d
                LEFT JOIN package p 
                    ON (
                        (d.keystage_id IS NOT NULL AND d.keystage_id = p.keystage_id)
                        OR (d.keystage_id IS NULL AND d.lot_id = p.lot_id)
                    )
                JOIN package_content pc 
                    ON pc.package_id = p.package_id
                JOIN item i 
                    ON pc.item_id = i.item_id
                LEFT JOIN package_status dp 
                    ON dp.delivery_id = d.delivery_id
                AND dp.package_id = p.package_id
                GROUP BY d.delivery_id, p.package_id
            ) x
            GROUP BY x.delivery_id
        ) pkg_items 
            ON pkg_items.delivery_id = d.delivery_id
        WHERE $whereClause
        ORDER BY $orderColumn $columnDir
        LIMIT :limit OFFSET :offset
        ";

// --- Count Total Records (without search filter) ---
$count_total_sql = "SELECT COUNT(delivery_id) FROM deliveries WHERE status = 'warehouse'";

// --- Count Filtered Records (with search filter) ---
$count_filtered_sql = "
    SELECT COUNT(DISTINCT d.delivery_id) 
    FROM deliveries d
    JOIN projects p ON d.project_id = p.project_id
    JOIN school s ON d.school_id = s.school_id
    WHERE $whereClause
";

try {
    // 1. Get the total number of records (without filter)
    $stmt_total = $pdo->prepare($count_total_sql);
    $stmt_total->execute();
    $totalRecords = $stmt_total->fetchColumn();

    // 2. Get the filtered number of records (with search filter)
    $stmt_filtered = $pdo->prepare($count_filtered_sql);
    if (!empty($searchValue)) {
        $stmt_filtered->bindValue(':search', "%$searchValue%", PDO::PARAM_STR);
    }
    $stmt_filtered->execute();
    $filteredRecords = $stmt_filtered->fetchColumn();

    // 3. Prepare and execute the main data query
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    if (!empty($searchValue)) {
        $stmt->bindValue(':search', "%$searchValue%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Format output for DataTables
    $output = [
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $filteredRecords, // This changes when searching
        "data" => $data
    ];

    header('Content-Type: application/json');
    echo json_encode($output);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}

exit;
?>