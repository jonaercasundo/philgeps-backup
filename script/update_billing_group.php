<?php
require "../config/db.php";
header('Content-Type: application/json');

// Check if required fields are present
if (!isset($_POST['edit_group_id']) || !isset($_POST['edit_group_name']) || !isset($_POST['edit_status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

$group_id = trim($_POST['edit_group_id']);
$group_name = trim($_POST['edit_group_name']);
$status = trim($_POST['edit_status']);

// Validate inputs
if (empty($group_name)) {
    echo json_encode([
        'success' => false,
        'message' => 'Group name cannot be empty'
    ]);
    exit;
}

// Validate status
$valid_statuses = ['for billing', 'billed', 'paid'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

try {
    // Check if group exists in grouping table
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM grouping WHERE group_id = :group_id");
    $checkStmt->bindParam(':group_id', $group_id);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Billing group not found'
        ]);
        exit;
    }
    
    // Check if new group name already exists (excluding current group)
    $nameCheckStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM grouping 
        WHERE group_name = :group_name 
        AND group_id != :group_id
    ");
    $nameCheckStmt->bindParam(':group_name', $group_name);
    $nameCheckStmt->bindParam(':group_id', $group_id);
    $nameCheckStmt->execute();
    
    if ($nameCheckStmt->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'A billing group with this name already exists'
        ]);
        exit;
    }
    
    // Update the grouping table
    $stmt = $pdo->prepare("
        UPDATE grouping 
        SET group_name = :group_name,
            status = :status
        WHERE group_id = :group_id
    ");
    
    $stmt->bindParam(':group_name', $group_name);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':group_id', $group_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Billing group updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update billing group'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}