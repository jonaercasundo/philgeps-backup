<?php
session_start();
require "../config/db.php";
require "../script/role_auth.php";

// Only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Check role authorization (adjust roles as needed for adding items)
$allowed_roles = ['Super Admin', 'Office Admin', 'Office Coordinator'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$itemName = trim($_POST['itemName'] ?? '');
$unit = trim($_POST['unit'] ?? '');
$projectId = (int)($_POST['project_id'] ?? 0); // Ensure project_id is an integer

if (empty($itemName)) {
    echo json_encode(['success' => false, 'message' => 'Item name cannot be empty.']);
    exit;
}

if ($projectId === 0) {
    echo json_encode(['success' => false, 'message' => 'Project ID is missing or invalid.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert the new item
    $stmt = $pdo->prepare("INSERT INTO item (item_name, unit, project_id) VALUES (?, ?, ?)");
    $stmt->execute([$itemName, $unit, $projectId]);
    $newItemId = $pdo->lastInsertId();

    // Log the activity
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['name'] . " added item '{$itemName}' (ID: {$newItemId}) to project ID: {$projectId}."
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Item added successfully!',
        'redirect' => "/philgeps/items.php?id={$projectId}&toast=Item%20added%20successfully&type=success"
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error adding item: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error adding item: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}

exit;
