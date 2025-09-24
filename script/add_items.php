<?php
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lot_id       = $_POST['lot_id'] ?? null;
    $keystage_id  = $_POST['keystage_id'] ?? null;
    $items        = $_POST['items'] ?? [];
    $quantities   = $_POST['quantities'] ?? [];
    $width   = $_POST['addwidth'] ?? 0;
    $height   = $_POST['addheight'] ?? 0;
    $length   = $_POST['addlength'] ?? 0;
    if (!$lot_id || !$keystage_id) {
        echo json_encode(['success' => false, 'message' => 'Missing Lot or Keystage']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Generate next package number for this keystage
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(package_num), 0) + 1 AS next_num 
                               FROM package 
                               WHERE keystage_id = ?");
        $stmt->execute([$keystage_id]);
        $nextNum = $stmt->fetchColumn();

        // Insert package
        $stmt = $pdo->prepare("INSERT INTO package (package_num, lot_id, keystage_id, width, height, length) 
                               VALUES (:package_num, :lot_id, :keystage_id, :width, :height, :length)");
        $stmt->execute([
            ':package_num'  => $nextNum,
            ':lot_id'       => $lot_id,
            ':keystage_id'  => $keystage_id,
            ':width'        => $width,
            ':height'       => $height,
            ':length'       => $length
        ]);

        $package_id = $pdo->lastInsertId();

        // Insert package contents
        $stmt = $pdo->prepare("INSERT INTO package_content (package_id, item_id, qty) VALUES (?, ?, ?)");
        foreach ($items as $i => $item_id) {
            $qty = $quantities[$i] ?? 0;
            if ($item_id && $qty > 0) {
                $stmt->execute([$package_id, $item_id, $qty]);
            }
        }
    $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Package added successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
