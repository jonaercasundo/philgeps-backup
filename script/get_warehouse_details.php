<?php
require_once "../config/db.php";

// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Sorting parameters - ADD THESE
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
        $totalQuery = "SELECT COUNT(*) as total FROM warehouse";
        $totalStmt = $pdo->prepare($totalQuery);
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Base query
        $sql = "SELECT warehouse_id, warehouse_name, warehouse_address, contact_info FROM warehouse";
        
        // Add search filter if search value exists
        $whereClauses = [];
        $params = [];
        
        if (!empty($searchValue)) {
            $whereClauses[] = "(warehouse_name LIKE :search OR warehouse_address LIKE :search OR contact_info LIKE :search)";
            $params[':search'] = "%{$searchValue}%";
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Get filtered count
        $filteredStmt = $pdo->prepare("SELECT COUNT(*) as total FROM warehouse" . 
            (!empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : ""));
        foreach ($params as $key => $value) {
            $filteredStmt->bindValue($key, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // FIX: Handle ordering based on DataTables parameters
        $orderColumns = [
            0 => 'warehouse_id',
            1 => 'warehouse_name', 
            2 => 'warehouse_address',
            3 => 'contact_info'
        ];
        
        if (isset($orderColumns[$orderColumnIndex])) {
            $sql .= " ORDER BY " . $orderColumns[$orderColumnIndex] . " " . ($orderDirection === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY warehouse_name ASC"; // Default ordering
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
        $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response["recordsTotal"] = $totalRecords;
        $response["recordsFiltered"] = $filteredRecords;
        $response["data"] = $warehouses;
        
    } catch (Exception $e) {
        $response["error"] = "DB Query Error: " . $e->getMessage();
    }
} else {
    $response["error"] = "Database connection not available.";
}

header('Content-Type: application/json');
echo json_encode($response);
?>