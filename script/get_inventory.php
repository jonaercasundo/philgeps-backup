<?php
require_once "../config/db.php";
session_start();

$warehouse_id = $_SESSION['warehouse_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

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
        
        if ($warehouse_id && $user_role !== 'Warehouse Admin') {
            $totalQuery .= " WHERE warehouse_id = :warehouse_id";
            $totalParams[':warehouse_id'] = $warehouse_id;
        }
        
        $totalStmt = $pdo->prepare($totalQuery);
        foreach ($totalParams as $key => $value) {
            $totalStmt->bindValue($key, $value);
        }
        $totalStmt->execute();
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Base query with joins - including expected quantity calculation
        $sql = "SELECT
                    i.inventory_id,
                    i.qty,
                    i.inventory_status,
                    it.item_name,
                    w.warehouse_name,
                    it.item_id,
                    w.warehouse_id,
                    COALESCE(expected.expected_qty, 0) AS expected_qty
                FROM inventory i
                JOIN item it ON i.item_id = it.item_id
                JOIN warehouse w ON i.warehouse_id = w.warehouse_id
                LEFT JOIN (
                    -- Calculate expected quantities based on pending deliveries
                    SELECT
                        w2.warehouse_id,
                        pc.item_id,
                        SUM(pc.qty) AS expected_qty
                    FROM (
                        SELECT DISTINCT d.delivery_id, d.school_id, d.project_id, ps.package_id
                        FROM deliveries d
                        JOIN package_status ps ON d.delivery_id = ps.delivery_id
                        WHERE d.status = 'pending'
                          AND ps.status IN ('pending','for approval')
                    ) d_packages
                    JOIN school s ON d_packages.school_id = s.school_id
                    JOIN package_content pc ON d_packages.package_id = pc.package_id
                    JOIN warehouse w2 ON (
                        (w2.warehouse_address = 'Pampanga' AND s.region IN ('Region I', 'Region II', 'Region III', 'Region IV-A', 'Region IV-B', 'MIMAROPA', 'Region V', 'CAR', 'NCR')) OR
                        (w2.warehouse_address = 'Cebu' AND s.region IN ('Region VI', 'Region VII', 'Region VIII')) OR
                        (w2.warehouse_address = 'Davao' AND s.region IN ('Region IX', 'Region X', 'Region XI', 'Region XII', 'Region XIII', 'CARAGA', 'BARMM'))
                    )
                    GROUP BY w2.warehouse_id, pc.item_id
                ) expected ON expected.warehouse_id = i.warehouse_id AND expected.item_id = i.item_id";
                // WHERE i.inventory_status = 'For Approval'";
                
        
        // Add search filter if search value exists
        $whereClauses = [];
        $params = [];
        
        if ($warehouse_id && $user_role !== 'Warehouse Admin') {
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
