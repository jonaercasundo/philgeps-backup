<?php
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lot_id       = $_POST['lot_id'] ?? null;
    $keystage_id  = $_POST['keystage_id'] ?? null;
    $items        = $_POST['items'] ?? [];
    $quantities   = $_POST['quantities'] ?? [];
    $dimensions   = $_POST['dimention'] ?? []; // from syncTableToForm

    if (!$lot_id || !$keystage_id) {
        echo json_encode(['success' => false, 'message' => 'Missing Lot or Keystage']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $packages = [];
        $lastDim = "0x0x0"; // fallback dimension
        // fetch project_id from lot_id
        $stmt = $pdo->prepare("SELECT project_id FROM lot WHERE lot_id = ?");
        $stmt->execute([$lot_id]);
        $project_id = $stmt->fetchColumn();
        foreach ($items as $i => $item_id) {
            $qty = $quantities[$i] ?? 0;
            $dimStr = trim($dimensions[$i] ?? "");

            // ✅ If dimension is blank (merged cells), reuse last dimension
            if ($dimStr === "" && $lastDim !== null) {
                $dimStr = $lastDim;
            }

            // ✅ Normalize dimension format: remove spaces, unify "x"
            $dimStr = strtolower($dimStr);
            $dimStr = preg_replace('/\s*[x×]\s*/i', 'x', $dimStr); // normalize separators
            $dimStr = preg_replace('/\s+/', '', $dimStr);          // remove stray spaces

            // ✅ Extract numbers
            if (preg_match('/([\d\.]+)x([\d\.]+)x([\d\.]+)/', $dimStr, $m)) {
                $h = (float)$m[1];
                $w = (float)$m[2];
                $l = (float)$m[3];
                $dimKey = "{$h}x{$w}x{$l}";
            } else {
                $h = $w = $l = 0;
                $dimKey = "0x0x0";
            }

            $lastDim = $dimKey; // save for next row

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

        // ✅ Insert each grouped package
        foreach ($packages as $pkg) {
            // next package number
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(package_num), 0) + 1 
                                   FROM package WHERE keystage_id = ?");
            $stmt->execute([$keystage_id]);
            $nextNum = $stmt->fetchColumn();

            // insert package
            $stmt = $pdo->prepare("INSERT INTO package 
                (package_num, lot_id, keystage_id, width, height, length) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nextNum, $lot_id, $keystage_id,
                $pkg['width'], $pkg['height'], $pkg['length']
            ]);
            $package_id = $pdo->lastInsertId();

            // insert contents
            $stmt = $pdo->prepare("INSERT INTO package_content (package_id, item_id, qty) VALUES (?, ?, ?)");
            foreach ($pkg['items'] as $it) {
                $stmt->execute([$package_id, $it['id'], $it['qty']]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Packages added successfully']);
        header("Location: ../packages.php?id={$project_id}&keystage_id={$keystage_id}&lot_id={$lot_id}&toast=Succesfully Added Packages&type=success");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                header("Location: ../packages.php?id={$project_id}&keystage_id={$keystage_id}&lot_id={$lot_id}&toast=".$e->getMessage()."&type=danger");
    }
}
