<?php
require_once '../config/db.php';

if (isset($pdo) && $pdo !== null) {
    try {
        $warehouse_id = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : null;
        $project_id = isset($_GET['project']) ? intval($_GET['project']) : null;
        $school_id = isset($_GET['school']) ? intval($_GET['school']) : null;
        $start_date = isset($_GET['startDate']) ? $_GET['startDate'] : null;
        $end_date = isset($_GET['endDate']) ? $_GET['endDate'] : null;
        
        if (!$warehouse_id) {
            die("Warehouse ID is required.");
        }
        
        // Get deliveries data
        $sql = "SELECT 
                    d.delivery_id,
                    p.project_name,
                    s.school_name,
                    d.dr_no,
                    d.delivery_date,
                    d.status,
                    d.package_type
                FROM deliveries d
                JOIN projects p ON d.project_id = p.project_id
                JOIN school s ON d.school_id = s.school_id
                JOIN logistics_location ll ON d.logistics_location_id = ll.logistics_location_id
                WHERE ll.warehouse_id = :warehouse_id 
                AND d.status IN ('warehouse')";
        
        $params = [':warehouse_id' => $warehouse_id];
        
        if ($project_id) {
            $sql .= " AND p.project_id = :project_id";
            $params[':project_id'] = $project_id;
        }
        
        if ($school_id) {
            $sql .= " AND s.school_id = :school_id";
            $params[':school_id'] = $school_id;
        }
        
        if ($start_date) {
            $sql .= " AND DATE(d.delivery_date) >= :start_date";
            $params[':start_date'] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND DATE(d.delivery_date) <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        $sql .= " ORDER BY d.delivery_id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="warehouse_active_deliveries_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // CSV header only
        fputcsv($output, ['Delivery ID', 'Project Name', 'School Name', 'DR Number', 'Delivery Date', 'Status', 'Package Type']);
        
        // Data rows only
        foreach($deliveries as $row) {
            fputcsv($output, [
                $row['delivery_id'],
                $row['project_name'],
                $row['school_name'],
                $row['dr_no'],
                $row['delivery_date'] ? date('m/d/Y', strtotime($row['delivery_date'])) : '-',
                ucfirst($row['status']),
                $row['package_type']
            ]);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        die("Error generating CSV: " . $e->getMessage());
    }
} else {
    die("Database connection not available.");
}