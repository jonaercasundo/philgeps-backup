<?php
session_start();
require "../config/db.php"; // include PDO

try {
    if (empty($_POST['project_id']) || empty($_POST['doc_type']) || empty($_FILES['file'])) {
        throw new Exception("Missing project_id, doc_type, or file");
    }

    $project_id = (int)$_POST['project_id'];
    $doc_type   = trim($_POST['doc_type']);
    $file       = $_FILES['file'];

    // Upload directory
    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validate upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload error: " . $file['error']);
    }

    $originalName = basename($file['name']);
    $newName      = uniqid() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $originalName);
    $filePath     = $uploadDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Failed to move uploaded file");
    }

    // Get project name
    $stmt = $pdo->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $projectName = $stmt->fetchColumn();

    // Insert or update document
    $stmt = $pdo->prepare("
        INSERT INTO documents (project_id, doc_type, file_name, file_path, uploaded_at)
        VALUES (:project_id, :doc_type, :file_name, :file_path, NOW())
        ON DUPLICATE KEY UPDATE 
            file_name = VALUES(file_name),
            file_path = VALUES(file_path),
            uploaded_at = NOW()
    ");
    $stmt->execute([
        ':project_id' => $project_id,
        ':doc_type'   => $doc_type,
        ':file_name'  => $newName,
        ':file_path'  => $filePath
    ]);

    // ✅ Fetch all uploaded document types for this project
    $stmt = $pdo->prepare("SELECT doc_type FROM documents WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $uploadedDocs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // ✅ Determine next project status
    $statusOrder = [
        'BAC Resolution'     => 'For Award',
        'Notice of Award'    => 'For Implementation',
        'Notice to Proceed'  => 'Ongoing',
        'Delivery Receipt'   => 'Delivered',
        'Inspection Report'  => 'Completed'
    ];

    $newStatus = 'Pending Evaluation'; // default
    foreach ($statusOrder as $requiredDoc => $statusValue) {
        if (in_array($requiredDoc, $uploadedDocs)) {
            $newStatus = $statusValue;
        } else {
            break;
        }
    }

    // ✅ Update project status
    $stmt = $pdo->prepare("UPDATE projects SET status = :status WHERE project_id = :project_id");
    $stmt->execute([
        ':status' => $newStatus,
        ':project_id' => $project_id
    ]);

    // Log user activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, created_at)
        VALUES (:user_id, :action, NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'] ?? null,
        ':action'  => ($_SESSION['name'] ?? 'System') . " uploaded $doc_type for project $projectName (status now: $newStatus)"
    ]);

    echo json_encode([
        "success" => true,
        "message" => "$doc_type uploaded successfully. Project status: $newStatus",
        "status" => $newStatus
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
