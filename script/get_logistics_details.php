<?php
require_once "../config/db.php";

// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Sorting parameters
$orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$orderDirection = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

$response = [
    "draw" => $draw,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => []
];

if (isset($pdo) && $pdo !== null) {
    try {
        // Get total records count
        $totalQuery = "SELECT COUNT(*) as total FROM logistics";
        $totalStmt = $pdo->prepare($totalQuery);
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Base query with JOIN to get regions and warehouse info from logistics_location
        $sql = "SELECT 
                    l.logistic_id, 
                    l.logistic_name,
                    GROUP_CONCAT(DISTINCT ll.region SEPARATOR ', ') as regions,
                    ll.warehouse_id,
                    w.warehouse_name
                FROM logistics l
                LEFT JOIN logistics_location ll ON l.logistic_id = ll.logistics_id
                LEFT JOIN warehouse w ON ll.warehouse_id = w.warehouse_id
                GROUP BY l.logistic_id, l.logistic_name, ll.warehouse_id, w.warehouse_name";
        
        // Add search filter if search value exists
        $whereClauses = [];
        $params = [];
        
        if (!empty($searchValue)) {
            $whereClauses[] = "(l.logistic_name LIKE :search OR ll.region LIKE :search OR w.warehouse_name LIKE :search)";
            $params[':search'] = "%{$searchValue}%";
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Get filtered count (need to use the same JOIN and GROUP BY)
        $filteredQuery = "SELECT COUNT(*) as total FROM (
                            SELECT l.logistic_id
                            FROM logistics l
                            LEFT JOIN logistics_location ll ON l.logistic_id = ll.logistics_id
                            LEFT JOIN warehouse w ON ll.warehouse_id = w.warehouse_id" . 
                            (!empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "") . "
                            GROUP BY l.logistic_id
                         ) as filtered";
        
        $filteredStmt = $pdo->prepare($filteredQuery);
        foreach ($params as $key => $value) {
            $filteredStmt->bindValue($key, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Handle ordering based on DataTables parameters
        $orderColumns = [
            0 => 'l.logistic_id',
            1 => 'l.logistic_name',
            2 => 'regions',
            3 => 'w.warehouse_name'
        ];
        
        if (isset($orderColumns[$orderColumnIndex])) {
            $sql .= " ORDER BY " . $orderColumns[$orderColumnIndex] . " " . ($orderDirection === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY l.logistic_name ASC"; // Default ordering
        }
        
        // Add pagination
        $sql .= " LIMIT :start, :length";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind search parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind pagination parameters
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':length', $length, PDO::PARAM_INT);
        
        $stmt->execute();
        $logistics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response["recordsTotal"] = $totalRecords;
        $response["recordsFiltered"] = $filteredRecords;
        $response["data"] = $logistics;
        
    } catch (Exception $e) {
        $response["error"] = "DB Query Error: " . $e->getMessage();
    }
} else {
    $response["error"] = "Database connection not available.";
}

header('Content-Type: application/json');
echo json_encode($response);
?>