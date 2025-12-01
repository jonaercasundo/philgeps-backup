<?php
// script/get_school_id_range.php

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors directly

require "../config/db.php";

$project_id = $_GET['project_id'] ?? '';
$from       = (int)($_GET['from'] ?? 1);
$to         = (int)($_GET['to'] ?? 1);

// Validation
if (empty($project_id) || $from < 1 || $to < $from) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Calculate limit and offset from page numbers
$limit  = $to - $from + 1;
$offset = $from - 1;

// Maximum 10,000 schools
if ($limit > 10000) {
    echo json_encode(['error' => 'Maximum 10,000 schools allowed']);
    exit;
}

try {
    $stmt = $pdo->prepare("
       SELECT sp.school_id
        FROM schools_project sp
        INNER JOIN school s ON s.school_id = sp.school_id
        WHERE sp.project_id = :project_id
          AND sp.batch_id BETWEEN :batch_from AND :batch_to
        ORDER BY sp.batch_id ASC, sp.id ASC
    ");

    // Bind parameters with proper types
    $stmt->bindValue(':project_id', $project_id, PDO::PARAM_STR);
   $stmt->bindValue(':batch_from', $from, PDO::PARAM_INT);
    $stmt->bindValue(':batch_to', $to, PDO::PARAM_INT);
    
    $stmt->execute();
    $school_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($school_ids);

} catch (Exception $e) {
    http_response_code(500);
    error_log("get_school_id_range error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}

exit;
