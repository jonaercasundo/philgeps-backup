<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly, we'll return them as JSON

require "../config/db.php";

// Prevent any output before JSON
ob_start();

try {
    $project_id = $_GET['project_id'] ?? '';
    $from = (int)($_GET['from'] ?? 0);
    $to   = (int)($_GET['to'] ?? 0);

    if (!$project_id) {
        throw new Exception('Project ID is required');
    }

    if (!$from || !$to) {
        throw new Exception('From and To values are required');
    }

    if ($from < 1 || $to < 1) {
        throw new Exception('Record numbers must be 1 or greater');
    }

    if ($to < $from) {
        throw new Exception('To value must be greater than or equal to From value');
    }

    // Calculate LIMIT and OFFSET from the range
    $limit = $to - $from + 1;
    $offset = $from - 1;

    // First check if project exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE project_id = ?");
    $checkStmt->execute([$project_id]);
    if ($checkStmt->fetchColumn() == 0) {
        throw new Exception('Project not found');
    }

    // Check if there are any deliveries for this project
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM deliveries WHERE project_id = ?");
    $countStmt->execute([$project_id]);
    $totalDeliveries = $countStmt->fetchColumn();
    
    if ($totalDeliveries == 0) {
        // Clear buffer and return empty array (not an error)
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }

    if ($offset >= $totalDeliveries) {
        throw new Exception("From value ($from) exceeds total deliveries ($totalDeliveries) for this project");
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT school_id
        FROM (
            SELECT school_id
            FROM deliveries
            WHERE project_id = ?
            ORDER BY school_id
            LIMIT ? OFFSET ?
        ) AS delivery_range
        ORDER BY school_id
    ");
    
    // Bind parameters with correct types
    $stmt->bindValue(1, $project_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $school_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Clear any buffered output
    ob_end_clean();
    
    // Set proper JSON header
    header('Content-Type: application/json');
    echo json_encode($school_ids);

} catch (PDOException $e) {
    // Clear any buffered output
    ob_end_clean();
    
    // Return database error as JSON
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => [
            'project_id' => $project_id ?? null,
            'from' => $from ?? null,
            'to' => $to ?? null
        ]
    ]);
} catch (Exception $e) {
    // Clear any buffered output
    ob_end_clean();
    
    // Return error as JSON
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => [
            'project_id' => $project_id ?? null,
            'from' => $from ?? null,
            'to' => $to ?? null
        ]
    ]);
}
?>