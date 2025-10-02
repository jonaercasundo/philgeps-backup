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

// Filter parameters
$logistics_id = isset($_GET['logistics']) ? intval($_GET['logistics']) : null;

$response = [
    "draw" => $draw,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => []
];

if (isset($pdo) && $pdo !== null) {
    try {
        // Get total records count - count logistics_location instead of logistics
        $totalQuery = "SELECT COUNT(*) as total FROM logistics_location";
        $totalStmt = $pdo->prepare($totalQuery);
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Base query with subqueries to avoid duplicates
        $sql = "SELECT 
                    ll.logistics_location_id,
                    l.logistic_name,
                    ll.region,
                    w.warehouse_name,
                    COALESCE((
                        SELECT COUNT(DISTINCT d.delivery_id)
                        FROM deliveries d
                        WHERE d.logistics_location_id = ll.logistics_location_id 
                        AND d.status IN ('accepted')
                    ), 0) as accepted_deliveries,
                    COALESCE((
                        SELECT COUNT(DISTINCT d.project_id)
                        FROM deliveries d
                        WHERE d.logistics_location_id = ll.logistics_location_id
                    ), 0) as projects_served
                FROM logistics_location ll
                JOIN logistics l ON ll.logistics_id = l.logistic_id
                JOIN warehouse w ON ll.warehouse_id = w.warehouse_id";
        
        // Add where clauses for filters and search
        $whereClauses = [];
        $params = [];
        
        // Search filter
        if (!empty($searchValue)) {
            $whereClauses[] = "(l.logistic_name LIKE :search OR ll.region LIKE :search OR w.warehouse_name LIKE :search)";
            $params[':search'] = "%{$searchValue}%";
        }
        
        // Custom filters
        if ($logistics_id) {
            $whereClauses[] = "l.logistic_id = :logistics_id";
            $params[':logistics_id'] = $logistics_id;
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Get filtered count (simpler approach)
        $filteredQuery = "SELECT COUNT(*) as total 
                         FROM logistics_location ll
                         JOIN logistics l ON ll.logistics_id = l.logistic_id
                         JOIN warehouse w ON ll.warehouse_id = w.warehouse_id";
        if (!empty($whereClauses)) {
            $filteredQuery .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        $filteredStmt = $pdo->prepare($filteredQuery);
        foreach ($params as $key => $value) {
            $filteredStmt->bindValue($key, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Handle ordering based on DataTables parameters
        $orderColumns = [
            0 => 'l.logistic_name',       // Logistics Provider
            1 => 'll.region',             // Region
            2 => 'w.warehouse_name',      // Warehouse Name
            3 => 'accepted_deliveries',   // Accepted Deliveries
            4 => 'projects_served'        // Projects Served
        ];
        
        if (isset($orderColumns[$orderColumnIndex])) {
            $sql .= " ORDER BY " . $orderColumns[$orderColumnIndex] . " " . ($orderDirection === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY l.logistic_name ASC"; // Default ordering
        }
        
        // Add pagination
        $sql .= " LIMIT :start, :length";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind search and filter parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind pagination parameters
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':length', $length, PDO::PARAM_INT);
        
        $stmt->execute();
        $logistics_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data properly
        $formattedData = [];
        foreach ($logistics_locations as $location) {
            $formattedData[] = [
                'logistic_name' => $location['logistic_name'],
                'region' => $location['region'],
                'warehouse_name' => $location['warehouse_name'],
                'accepted_deliveries' => $location['accepted_deliveries'],
                'projects_served' => $location['projects_served'],
                'logistics_location_id' => $location['logistics_location_id']
            ];
        }
        
        $response["recordsTotal"] = $totalRecords;
        $response["recordsFiltered"] = $filteredRecords;
        $response["data"] = $formattedData;
        
    } catch (Exception $e) {
        $response["error"] = "DB Query Error: " . $e->getMessage();
    }
} else {
    $response["error"] = "Database connection not available.";
}

header('Content-Type: application/json');
echo json_encode($response);
?>