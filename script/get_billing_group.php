<?php
require "../config/db.php";
header('Content-Type: application/json');

if (!isset($_GET['group_id'])) {
    echo json_encode(['success' => false, 'message' => 'Group ID is required']);
    exit;
}

$group_id = $_GET['group_id'];

try {
    // Get status ENUM values from the grouping table
    $enumStmt = $pdo->query("SHOW COLUMNS FROM grouping LIKE 'status'");
    $enumRow = $enumStmt->fetch(PDO::FETCH_ASSOC);
    $enumType = $enumRow['Type'];
    
    // Extract ENUM values
    preg_match("/^enum\(\'(.*)\'\)$/", $enumType, $matches);
    $statusOptions = explode("','", $matches[1]);
    
    // Get group details from grouping table
    $stmt = $pdo->prepare("
        SELECT 
            group_id,
            group_name,
            status,
            created_at
        FROM grouping
        WHERE group_id = :group_id
        LIMIT 1
    ");
    $stmt->bindParam(':group_id', $group_id);
    $stmt->execute();
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group) {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        exit;
    }
    
    // Get all DR numbers in this group from billing_grouped table
    $drStmt = $pdo->prepare("
        SELECT dr_no
        FROM billing_grouped
        WHERE group_id = :group_id
        ORDER BY dr_no
    ");
    $drStmt->bindParam(':group_id', $group_id);
    $drStmt->execute();
    $drNumbers = $drStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $group['dr_numbers'] = $drNumbers;
    $group['status_options'] = $statusOptions;
    
    echo json_encode([
        'success' => true,
        'group' => $group
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}