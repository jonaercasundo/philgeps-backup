<?php
require "../config/db.php"; // make sure $pdo is included

try {
    if (!isset($_POST['project_id']) || !isset($_FILES['file'])) {
        throw new Exception("Missing project_id or file");
    }

    $project_id = $_POST['project_id'];
    $doc_type   = $_POST['doc_type'];
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
    $newName      = uniqid() . "_" . $originalName;
    $filePath     = $uploadDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception("Failed to move uploaded file");
    }

    // Save to DB
    $stmt = $pdo->prepare("
        INSERT INTO documents (project_id, doc_type, file_name, file_path)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $project_id,
        $doc_type,
        $newName,
        $filePath
    ]);

    echo json_encode(["success" => true, "file" => $newName]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
