<?php
require_once '../config/db.php';

if (isset($pdo) && $pdo !== null) {
    try {
        $logistics_id = isset($_GET['logistics']) ? intval($_GET['logistics']) : null;
        
        // Base query for logistics report
        $sql = "SELECT 
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
        
        $params = [];
        if ($logistics_id) {
            $sql .= " WHERE l.logistic_id = ?";
            $params[] = $logistics_id;
        }
        
        $sql .= " ORDER BY l.logistic_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logistics_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="logistics_report_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // CSV header only
        fputcsv($output, ['Logistics Provider', 'Region', 'Warehouse', 'Accepted Deliveries', 'Projects Served']);
        
        // Data rows only
        foreach($logistics_data as $row) {
            fputcsv($output, [
                $row['logistic_name'],
                $row['region'],
                $row['warehouse_name'],
                $row['accepted_deliveries'],
                $row['projects_served']
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