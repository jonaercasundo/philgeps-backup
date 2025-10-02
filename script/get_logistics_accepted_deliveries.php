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
$logistics_location_id = isset($_GET['logistics_location_id']) ? intval($_GET['logistics_location_id']) : null;
$project_id = isset($_GET['project']) ? intval($_GET['project']) : null;
$school_id = isset($_GET['school']) ? intval($_GET['school']) : null;
$start_date = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$end_date = isset($_GET['endDate']) ? $_GET['endDate'] : null;

$response = [
    "draw" => $draw,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => []
];

if (isset($pdo) && $pdo !== null && $logistics_location_id) {
    try {
        // Get total records count for this logistics location
        $totalQuery = "SELECT COUNT(*) as total 
                      FROM deliveries d
                      WHERE d.logistics_location_id = ? AND d.status IN ('accepted')";
        $totalStmt = $pdo->prepare($totalQuery);
        $totalStmt->execute([$logistics_location_id]);
        $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Base query
        $sql = "SELECT 
                    d.delivery_id,
                    p.project_name,
                    s.school_name,
                    d.dr_no,
                    d.delivery_date,
                    d.status,
                    d.package_type,
                    d.created_at
                FROM deliveries d
                JOIN projects p ON d.project_id = p.project_id
                JOIN school s ON d.school_id = s.school_id
                WHERE d.logistics_location_id = :logistics_location_id 
                AND d.status IN ('accepted')";
        
        // Add where clauses for filters and search
        $whereClauses = [];
        $params = [':logistics_location_id' => $logistics_location_id];
        
        // Search filter
        if (!empty($searchValue)) {
            $whereClauses[] = "(p.project_name LIKE :search OR s.school_name LIKE :search OR d.dr_no LIKE :search OR d.package_type LIKE :search)";
            $params[':search'] = "%{$searchValue}%";
        }
        
        // Custom filters
        if ($project_id) {
            $whereClauses[] = "p.project_id = :project_id";
            $params[':project_id'] = $project_id;
        }
        
        if ($school_id) {
            $whereClauses[] = "s.school_id = :school_id";
            $params[':school_id'] = $school_id;
        }
        
        if ($start_date) {
            $whereClauses[] = "DATE(d.delivery_date) >= :start_date";
            $params[':start_date'] = $start_date;
        }
        
        if ($end_date) {
            $whereClauses[] = "DATE(d.delivery_date) <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        if (!empty($whereClauses)) {
            $sql .= " AND " . implode(" AND ", $whereClauses);
        }
        
        // Get filtered count
        $filteredQuery = "SELECT COUNT(*) as total 
                         FROM deliveries d
                         JOIN projects p ON d.project_id = p.project_id
                         JOIN school s ON d.school_id = s.school_id
                         WHERE d.logistics_location_id = :logistics_location_id 
                         AND d.status IN ('accepted')";
        
        if (!empty($whereClauses)) {
            $filteredQuery .= " AND " . implode(" AND ", $whereClauses);
        }
        
        $filteredStmt = $pdo->prepare($filteredQuery);
        foreach ($params as $key => $value) {
            $filteredStmt->bindValue($key, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Handle ordering
        $orderColumns = [
            0 => 'd.delivery_id',
            1 => 'p.project_name',
            2 => 's.school_name',
            3 => 'd.dr_no',
            4 => 'd.delivery_date',
            5 => 'd.status',
            6 => 'd.package_type'
        ];
        
        if (isset($orderColumns[$orderColumnIndex])) {
            $sql .= " ORDER BY " . $orderColumns[$orderColumnIndex] . " " . ($orderDirection === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY d.delivery_id DESC";
        }
        
        // Add pagination
        $sql .= " LIMIT :start, :length";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':start', $start, PDO::PARAM_INT);
        $stmt->bindValue(':length', $length, PDO::PARAM_INT);
        
        $stmt->execute();
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response["recordsTotal"] = $totalRecords;
        $response["recordsFiltered"] = $filteredRecords;
        $response["data"] = $deliveries;
        
    } catch (Exception $e) {
        $response["error"] = "DB Query Error: " . $e->getMessage();
    }
} else {
    $response["error"] = "Database connection not available or logistics location ID missing.";
}

header('Content-Type: application/json');
echo json_encode($response);