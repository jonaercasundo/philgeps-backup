<?php
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_id   = $_POST['package_id'] ?? null;
    $package_num  = $_POST['package_num'] ?? null;
    $width        = $_POST['width'] ?? null;
    $height       = $_POST['height'] ?? null;
    $length       = $_POST['length'] ?? null;
    $items        = $_POST['items'] ?? [];
    $qtys         = $_POST['qty'] ?? [];

    if (!$package_id) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Update package
        $stmt = $pdo->prepare("UPDATE package 
                               SET package_num = :package_num,
                                   width = :width,
                                   height = :height,
                                   length = :length
                               WHERE package_id = :id");
        $stmt->execute([
            ':package_num' => $package_num,
            ':width' => $width,
            ':height' => $height,
            ':length' => $length,
            ':id' => $package_id
        ]);

        // Clear old contents
        $pdo->prepare("DELETE FROM package_content WHERE package_id = ?")
            ->execute([$package_id]);

        // Insert new contents
        $stmt = $pdo->prepare("INSERT INTO package_content (package_id, item_id, qty) VALUES (?, ?, ?)");
        foreach ($items as $i => $item_id) {
            $qty = $qtys[$i] ?? 0;
            if ($item_id && $qty > 0) {
                $stmt->execute([$package_id, $item_id, $qty]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
