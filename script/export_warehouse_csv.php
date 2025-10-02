<?php
require_once '../config/db.php';

if (isset($pdo) && $pdo !== null) {
    try {
        $warehouse_id = isset($_GET['warehouse']) ? intval($_GET['warehouse']) : null;
        
        // Base query for warehouse report
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
        
        $params = [];
        if ($warehouse_id) {
            $sql .= " WHERE w.warehouse_id = ?";
            $params[] = $warehouse_id;
        }
        
        $sql .= " ORDER BY w.warehouse_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $warehouse_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="warehouse_report_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // CSV header only - no other information
        fputcsv($output, ['Warehouse Name', 'Location/Region', 'Contact Info', 'Active Deliveries', 'Projects Served']);
        
        // Data rows only
        foreach($warehouse_data as $row) {
            fputcsv($output, [
                $row['warehouse_name'],
                $row['location_region'],
                $row['contact_info'],
                $row['active_deliveries'],
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