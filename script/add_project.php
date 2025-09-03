<?php
header('Content-Type: application/json');
require "../config/db.php"; // adjust

try {
    $stmt = $pdo->prepare("INSERT INTO projects 
        (ref_no, agency, project_name, contract_amount, start_date, end_date) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['ref_no'],
        $_POST['agency'],
        $_POST['project_name'],
        $_POST['contract_amount'],
        $_POST['start_date'],
        $_POST['end_date']
    ]);
    $project_id = $pdo->lastInsertId();

    // Handle multiple file uploads
$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$files = normalizeFilesArray($_FILES['documents']);

foreach ($files as $file) {
    $tmp = $file['tmp_name'];
    $error = $file['error'];
    $originalName = $file['name'];

    if ($error === UPLOAD_ERR_OK) {
        $newName = uniqid() . "_" . basename($originalName);
        $filePath = $uploadDir . $newName;

        if (move_uploaded_file($tmp, $filePath)) {
            $doc_type = "Bidding Document"; // Or get from frontend

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
        } else {
            error_log("Failed to move uploaded file: $originalName");
        }
    } else {
        error_log("Upload error for file '$originalName': code $error");
    }
}




    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

function normalizeFilesArray($filePost) {
    $fileArray = [];
    $fileCount = is_array($filePost['name']) ? count($filePost['name']) : 0;

    if ($fileCount > 0) {
        for ($i = 0; $i < $fileCount; $i++) {
            $fileArray[] = [
                'name'     => $filePost['name'][$i],
                'type'     => $filePost['type'][$i],
                'tmp_name' => $filePost['tmp_name'][$i],
                'error'    => $filePost['error'][$i],
                'size'     => $filePost['size'][$i]
            ];
        }
    } else {
        // Handle single file as array
        $fileArray[] = $filePost;
    }

    return $fileArray;
}
