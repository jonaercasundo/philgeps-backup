<?php
require_once "../config/db.php";

// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Sorting parameters
$orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$orderDirection = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

$response = [
    "draw" => $draw,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => []
];

if (isset($pdo) && $pdo !== null) {
    try {
        // Get total records count
        $totalQuery = "SELECT COUNT(*) as total FROM logistics_location";
        $totalStmt = $pdo->prepare($totalQuery);
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Base query with joins
        $sql = "SELECT 
                    ll.logistics_location_id,
                    ll.region,
                    l.logistic_name,
                    w.warehouse_name,
                    l.logistic_id,
                    w.warehouse_id
                FROM logistics_location ll
                JOIN logistics l ON ll.logistics_id = l.logistic_id
                JOIN warehouse w ON ll.warehouse_id = w.warehouse_id";
        
        // Add search filter if search value exists
        $whereClauses = [];
        $params = [];
        
        if (!empty($searchValue)) {
            $whereClauses[] = "(l.logistic_name LIKE :search OR w.warehouse_name LIKE :search OR ll.region LIKE :search OR ll.logistics_location_id LIKE :search)";
            $params[':search'] = "%{$searchValue}%";
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Get filtered count
        $filteredQuery = "SELECT COUNT(*) as total 
                         FROM logistics_location ll
                         JOIN logistics l ON ll.logistics_id = l.logistic_id
                         JOIN warehouse w ON ll.warehouse_id = w.warehouse_id" . 
                        (!empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "");
        
        $filteredStmt = $pdo->prepare($filteredQuery);
        foreach ($params as $key => $value) {
            $filteredStmt->bindValue($key, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Handle ordering based on DataTables parameters
        $orderColumns = [
            0 => 'll.logistics_location_id',  // Location ID
            1 => 'l.logistic_name',           // Logistics Name
            2 => 'w.warehouse_name',          // Warehouse Name
            3 => 'll.region'                  // Region
        ];
        
        if (isset($orderColumns[$orderColumnIndex])) {
            $sql .= " ORDER BY " . $orderColumns[$orderColumnIndex] . " " . ($orderDirection === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY ll.logistics_location_id DESC"; // Default ordering
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
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response["recordsTotal"] = $totalRecords;
        $response["recordsFiltered"] = $filteredRecords;
        $response["data"] = $locations;
        
    } catch (Exception $e) {
        $response["error"] = "DB Query Error: " . $e->getMessage();
    }
} else {
    $response["error"] = "Database connection not available.";
}

header('Content-Type: application/json');
echo json_encode($response);
