

<?php
session_start();
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_id  = $_POST['package_id'] ?? null;
    $package_num = $_POST['package_num'] ?? null;
    $width       = $_POST['width'] ?? null;
    $height      = $_POST['height'] ?? null;
    $length      = $_POST['length'] ?? null;
    $items       = $_POST['items'] ?? [];
    $qtys        = $_POST['qty'] ?? [];

    if (!$package_id) {
        echo json_encode(['success' => false, 'message' => 'Missing package ID']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 🧱 Update package
        $stmt = $pdo->prepare("
            UPDATE package 
            SET package_num = :package_num,
                width = :width,
                height = :height,
                length = :length
            WHERE package_id = :id
        ");
        $stmt->execute([
            ':package_num' => $package_num,
            ':width'       => $width,
            ':height'      => $height,
            ':length'      => $length,
            ':id'          => $package_id
        ]);

        // 🧹 Clear existing contents
        $pdo->prepare("DELETE FROM package_content WHERE package_id = ?")
            ->execute([$package_id]);

        // 📦 Insert new contents
        $stmt = $pdo->prepare("
            INSERT INTO package_content (package_id, item_id, qty) 
            VALUES (?, ?, ?)
        ");
        foreach ($items as $i => $item_id) {
            $qty = $qtys[$i] ?? 0;
            if ($item_id && $qty > 0) {
                $stmt->execute([$package_id, $item_id, $qty]);
            }
        }

        // 🔍 Fetch project and lot details
        $stmt = $pdo->prepare("
            SELECT 
                p.project_name, 
                p.project_id, 
                pck.keystage_id, 
                pck.lot_id, 
                k.keystage_num, 
                l.lot_name 
            FROM package pck
            LEFT JOIN keystage k ON k.keystage_id = pck.keystage_id
            JOIN lot l ON pck.lot_id = l.lot_id
            JOIN projects p ON p.project_id = l.project_id
            WHERE pck.package_id = ?
        ");
        $stmt->execute([$package_id]);
        $projectInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // 🪵 Log activity
        $lotName = $projectInfo['lot_name'] ?? 'Unknown Lot';
        $keystageNum = $projectInfo['keystage_num'] ?? 'N/A';
        $projectName = $projectInfo['project_name'] ?? 'Unknown Project';

        $action = substr(sprintf(
            "%s edited package #%s in Lot %s (Keystage %s) on project %s",
            $_SESSION['name'],
            $package_num,
            $lotName,
            $keystageNum,
            $projectName
        ), 0, 255);

        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $action]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Edited Package',
            'redirect' => '/philgeps/packages.php?id=' . $projectInfo['project_id'] . '&toast=Edited Package&type=success'
        ]);
        exit;


    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;

    }
}
?>
