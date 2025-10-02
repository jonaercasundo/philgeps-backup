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
$warehouse_id = isset($_GET['warehouse']) ? intval($_GET['warehouse']) : null;

$response = [
    "draw" => $draw,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => []
];

if (isset($pdo) && $pdo !== null) {
    try {
        // Get total records count
        $totalQuery = "SELECT COUNT(*) as total FROM warehouse";
        $totalStmt = $pdo->prepare($totalQuery);
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Base query with subqueries to avoid duplicates
        $sql = "SELECT 
                    w.warehouse_id,
                    w.warehouse_name,
                    w.warehouse_address as location_region,
                    w.contact_info,
                    COALESCE((
                        SELECT COUNT(DISTINCT d.delivery_id)
                        FROM deliveries d
                        JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
                        WHERE ll.warehouse_id = w.warehouse_id 
                        AND d.status IN ('warehouse')
                    ), 0) as active_deliveries,
                    COALESCE((
                        SELECT COUNT(DISTINCT d.project_id)
                        FROM deliveries d
                        JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
                        WHERE ll.warehouse_id = w.warehouse_id
                    ), 0) as projects_served
                FROM warehouse w";
        
        // Add where clauses for filters and search
        $whereClauses = [];
        $params = [];
        
        // Search filter
        if (!empty($searchValue)) {
            $whereClauses[] = "(w.warehouse_name LIKE :search OR w.warehouse_address LIKE :search OR w.contact_info LIKE :search)";
            $params[':search'] = "%{$searchValue}%";
        }
        
        // Custom filters
        if ($warehouse_id) {
            $whereClauses[] = "w.warehouse_id = :warehouse_id";
            $params[':warehouse_id'] = $warehouse_id;
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Get filtered count (simpler approach)
        $filteredQuery = "SELECT COUNT(*) as total FROM warehouse w";
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
            0 => 'w.warehouse_name',      // Warehouse Name
            1 => 'w.warehouse_address',   // Location/Region
            2 => 'w.contact_info',        // Contact Info
            3 => 'active_deliveries',     // Active Deliveries
            4 => 'projects_served'        // Projects Served
        ];
        
        if (isset($orderColumns[$orderColumnIndex])) {
            $sql .= " ORDER BY " . $orderColumns[$orderColumnIndex] . " " . ($orderDirection === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY w.warehouse_name ASC"; // Default ordering
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
        $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data properly
        $formattedData = [];
        foreach ($warehouses as $warehouse) {
            $formattedData[] = [
                'warehouse_name' => $warehouse['warehouse_name'],
                'location_region' => $warehouse['location_region'],
                'contact_info' => $warehouse['contact_info'],
                'active_deliveries' => $warehouse['active_deliveries'],
                'projects_served' => $warehouse['projects_served'],
                'warehouse_id' => $warehouse['warehouse_id']
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