<?php
session_start();
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lot_id       = $_POST['lot_id'] ?? null;
    $keystage_id  = $_POST['keystage_id'] ?? null;

    if (empty($keystage_id)) {
        $keystage_id = null;
    }

    $items      = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $dimensions = $_POST['dimention'] ?? [];

    if (!$lot_id && !$keystage_id) {
        echo json_encode(['success' => false, 'message' => 'Missing Lot or Keystage']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $packages = [];
        $lastDim = "0x0x0";

        // fetch project_id from lot_id
        $stmt = $pdo->prepare("SELECT project_id FROM lot WHERE lot_id = ?");
        $stmt->execute([$lot_id]);
        $project_id = $stmt->fetchColumn();

        // fix query alias
        $stmt = $pdo->prepare("
            SELECT p.project_name 
            FROM lot l 
            JOIN projects p ON l.project_id = p.project_id 
            WHERE l.lot_id = ?
        ");
        $stmt->execute([$lot_id]);
        $projectName = $stmt->fetchColumn();

        foreach ($items as $i => $item_id) {
            $qty = $quantities[$i] ?? 0;
            $dimStr = trim($dimensions[$i] ?? "");

            if ($dimStr === "" && $lastDim !== null) {
                $dimStr = $lastDim;
            }

            $dimStr = strtolower($dimStr);
            $dimStr = preg_replace('/\s*[x×]\s*/i', 'x', $dimStr);
            $dimStr = preg_replace('/\s+/', '', $dimStr);

            if (preg_match('/([\d\.]+)x([\d\.]+)x([\d\.]+)/', $dimStr, $m)) {
                $h = (float)$m[1];
                $w = (float)$m[2];
                $l = (float)$m[3];
                $dimKey = "{$h}x{$w}x{$l}";
            } else {
                $h = $w = $l = 0;
                $dimKey = "0x0x0";
            }

            $lastDim = $dimKey;

            if (!isset($packages[$dimKey])) {
                $packages[$dimKey] = [
                    'width' => $w,
                    'height' => $h,
                    'length' => $l,
                    'items' => []
                ];
            }

            if ($item_id && $qty > 0) {
                $packages[$dimKey]['items'][] = ['id' => $item_id, 'qty' => $qty];
            }
        }

        // count how many packages will be inserted
        $numberofPackages = count($packages);

        // Insert grouped packages
        foreach ($packages as $pkg) {
            if ($keystage_id === null) {
                // next package number per lot
                $stmt = $pdo->prepare("SELECT COALESCE(MAX(package_num), 0) + 1 
                                       FROM package WHERE lot_id = ?");
                $stmt->execute([$lot_id]);
                $nextNum = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO package 
                    (package_num, lot_id, width, height, length) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $nextNum, $lot_id,
                    $pkg['width'], $pkg['height'], $pkg['length']
                ]);
            } else {
                // next package number per keystage
                $stmt = $pdo->prepare("SELECT COALESCE(MAX(package_num), 0) + 1 
                                       FROM package WHERE keystage_id = ?");
                $stmt->execute([$keystage_id]);
                $nextNum = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO package 
                    (package_num, lot_id, keystage_id, width, height, length) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $nextNum, $lot_id, $keystage_id,
                    $pkg['width'], $pkg['height'], $pkg['length']
                ]);
            }

            $package_id = $pdo->lastInsertId();

            // insert contents
            $stmt = $pdo->prepare("INSERT INTO package_content (package_id, item_id, qty) VALUES (?, ?, ?)");
            foreach ($pkg['items'] as $it) {
                $stmt->execute([$package_id, $it['id'], $it['qty']]);
            }
        }

        // Log the activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['name'] . " added {$numberofPackages} package(s) to project {$projectName}"
        ]);

        $pdo->commit();
        header("Location: ../packages.php?id=" . $project_id . "&toast=Packages%20added%20successfully&type=success");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: ../packages.php?id=" . $project_id . "&toast=" . urlencode($e->getMessage()) . "&type=danger");
        exit;
    }
}
