<?php
require_once "../config/db.php";
session_start();

$warehouse_id = $_SESSION['warehouse_id'] ?? null;
// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

// Sorting parameters - ADD THESE
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
        // Get total records count - FILTER BY WAREHOUSE_ID
        $totalQuery = "SELECT COUNT(*) as total FROM inventory";
        $totalParams = [];
        
        if ($warehouse_id) {
            $totalQuery .= " WHERE warehouse_id = :warehouse_id";
            $totalParams[':warehouse_id'] = $warehouse_id;
        }
        
        $totalStmt = $pdo->prepare($totalQuery);
        foreach ($totalParams as $key => $value) {
            $totalStmt->bindValue($key, $value);
        }
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Base query with joins
        $sql = "SELECT 
                    i.inventory_id,
                    i.qty,
                    i.inventory_status,
                    it.item_name,
                    w.warehouse_name,
                    it.item_id,
                    w.warehouse_id
                FROM inventory i
                JOIN item it ON i.item_id = it.item_id
                JOIN warehouse w ON i.warehouse_id = w.warehouse_id";
                // WHERE i.inventory_status = 'For Approval'";
                
        
        // Add search filter if search value exists
        $whereClauses = [];
        $params = [];
        
        if ($warehouse_id) {
            $whereClauses[] = "i.warehouse_id = :warehouse_id";
            $params[':warehouse_id'] = $warehouse_id;
        }

        if (!empty($searchValue)) {
            $whereClauses[] = "(it.item_name LIKE :search OR w.warehouse_name LIKE :search OR i.qty LIKE :search OR i.inventory_id LIKE :search OR i.inventory_status LIKE :search)";
            $params[':search'] = "%{$searchValue}%";
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Get filtered count
        $filteredQuery = "SELECT COUNT(*) as total 
                        FROM inventory i
                        JOIN item it ON i.item_id = it.item_id
                        JOIN warehouse w ON i.warehouse_id = w.warehouse_id";
        
        if (!empty($whereClauses)) {
            $filteredQuery .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        $filteredStmt = $pdo->prepare($filteredQuery);
        foreach ($params as $key => $value) {
            $filteredStmt->bindValue($key, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // FIX: Handle ordering based on DataTables parameters
        $orderColumns = [
            0 => 'i.inventory_id',      // Inventory ID
            1 => 'w.warehouse_name',    // Warehouse
            2 => 'it.item_name',        // Item
            3 => 'i.qty',               // Quantity
            4 => 'i.inventory_status'
        ];
        
        if (isset($orderColumns[$orderColumnIndex])) {
            $sql .= " ORDER BY " . $orderColumns[$orderColumnIndex] . " " . ($orderDirection === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY i.inventory_id DESC"; // Default ordering
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
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response["recordsTotal"] = $totalRecords;
        $response["recordsFiltered"] = $filteredRecords;
        $response["data"] = $inventory;
        
    } catch (Exception $e) {
        $response["error"] = "DB Query Error: " . $e->getMessage();
    }
} else {
    $response["error"] = "Database connection not available.";
}

header('Content-Type: application/json');
echo json_encode($response);
