<?php
// script/get_billing_grouped_summary.php - SIMPLE VERSION
function getBillingGroupSummary($pdo) {
    try {
        // Simple test query - get all groups regardless of status
        $stmt = $pdo->query("
            SELECT 
                g.group_id,
                g.group_name,
                g.status,
                g.created_at,
                COUNT(bg.dr_no) as dr_count
            FROM grouping g
            LEFT JOIN billing_grouped bg ON g.group_id = bg.group_id
            GROUP BY g.group_id, g.group_name, g.status, g.created_at
            ORDER BY g.created_at DESC
        ");
        
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get DR numbers separately
        $grouped_summary = [];
        foreach ($groups as $group) {
            $dr_stmt = $pdo->prepare("
                SELECT dr_no 
                FROM billing_grouped 
                WHERE group_id = ? 
                ORDER BY created_at
            ");
            $dr_stmt->execute([$group['group_id']]);
            $dr_numbers = $dr_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $grouped_summary[] = [
                'group_id' => $group['group_id'],
                'group_name' => $group['group_name'],
                'status' => $group['status'],
                'created_at' => $group['created_at'],
                'dr_count' => $group['dr_count'],
                'dr_numbers' => $dr_numbers
            ];
        }
        
        return $grouped_summary;
        
    } catch (PDOException $e) {
        error_log("Billing Group Summary Error: " . $e->getMessage());
        return [];
    }
}
?>