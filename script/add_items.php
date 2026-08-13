<?php

session_start();

require "../config/db.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lot_id      = $_POST['lot_id'] ?? null;
    $keystage_id = $_POST['keystage_id'] ?? null;
    $delivery_id = $_POST['delivery_id'] ?? null;

    if (empty($keystage_id)) {
        $keystage_id = null;
    }

    $items      = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $dimensions = $_POST['dimention'] ?? [];

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (!$delivery_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing Delivery ID'
        ]);
        exit;
    }

    if (!$lot_id && !$keystage_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing Lot or Keystage'
        ]);
        exit;
    }


    try {

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | 1. GET DELIVERY INFORMATION
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                d.delivery_id,
                d.project_id,
                d.lot_id,
                d.school_id,
                d.package_qty,
                p.project_name
            FROM deliveries d
            LEFT JOIN projects p
                ON d.project_id = p.project_id
            WHERE d.delivery_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $delivery_id
        ]);

        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$delivery) {
            throw new Exception(
                'Delivery not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 2. USE DELIVERY LOT IF LOT WAS NOT PROVIDED
        |--------------------------------------------------------------------------
        */

        if (!$lot_id) {

            $lot_id = $delivery['lot_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. BUILD PACKAGES BY DIMENSION
        |--------------------------------------------------------------------------
        */

        $packages = [];

        $lastDim = "0x0x0";


        foreach ($items as $i => $item_id) {

            $qty = (int) (
                $quantities[$i] ?? 0
            );

            $dimStr = trim(
                $dimensions[$i] ?? ""
            );


            /*
            |--------------------------------------------------------------------------
            | USE PREVIOUS DIMENSION
            |--------------------------------------------------------------------------
            */

            if (
                $dimStr === "" &&
                $lastDim !== null
            ) {

                $dimStr = $lastDim;
            }


            /*
            |--------------------------------------------------------------------------
            | NORMALIZE DIMENSION
            |--------------------------------------------------------------------------
            */

            $dimStr = strtolower(
                $dimStr
            );

            $dimStr = preg_replace(
                '/\s*(x|×|\*|by)\s*/i',
                'x',
                $dimStr
            );

            $dimStr = preg_replace(
                '/\s+/',
                '',
                $dimStr
            );


            /*
            |--------------------------------------------------------------------------
            | PARSE 3D
            |--------------------------------------------------------------------------
            */

            if (
                preg_match(
                    '/^([\d\.]+)x([\d\.]+)x([\d\.]+)$/',
                    $dimStr,
                    $m
                )
            ) {

                $l = (float) $m[1];
                $w = (float) $m[2];
                $h = (float) $m[3];

                $dimKey =
                    "{$l}x{$w}x{$h}";

            }

            /*
            |--------------------------------------------------------------------------
            | PARSE 2D
            |--------------------------------------------------------------------------
            */

            elseif (
                preg_match(
                    '/^([\d\.]+)x([\d\.]+)$/',
                    $dimStr,
                    $m
                )
            ) {

                $l = (float) $m[1];
                $w = (float) $m[2];
                $h = 0;

                $dimKey =
                    "{$l}x{$w}x{$h}";

            }

            /*
            |--------------------------------------------------------------------------
            | INVALID
            |--------------------------------------------------------------------------
            */

            else {

                $l = 0;
                $w = 0;
                $h = 0;

                $dimKey = "0x0x0";
            }


            $lastDim = $dimKey;


            /*
            |--------------------------------------------------------------------------
            | CREATE PACKAGE GROUP
            |--------------------------------------------------------------------------
            */

            if (!isset($packages[$dimKey])) {

                $packages[$dimKey] = [
                    'width'  => $w,
                    'height' => $h,
                    'length' => $l,
                    'items'  => []
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | ADD ITEM
            |--------------------------------------------------------------------------
            */

            if (
                !empty($item_id) &&
                $qty > 0
            ) {

                $packages[$dimKey]['items'][] = [
                    'id'  => $item_id,
                    'qty' => $qty
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 4. COUNT PACKAGES
        |--------------------------------------------------------------------------
        */

        $numberofPackages = count(
            $packages
        );


        /*
        |--------------------------------------------------------------------------
        | 5. CREATE PACKAGES
        |--------------------------------------------------------------------------
        */

        foreach ($packages as $pkg) {


            /*
            |--------------------------------------------------------------------------
            | GET NEXT PACKAGE NUMBER
            |--------------------------------------------------------------------------
            */

            if ($keystage_id === null) {

                $stmt = $pdo->prepare("
                    SELECT COALESCE(
                        MAX(package_num),
                        0
                    ) + 1

                    FROM package

                    WHERE lot_id = ?
                ");

                $stmt->execute([
                    $lot_id
                ]);

            } else {

                $stmt = $pdo->prepare("
                    SELECT COALESCE(
                        MAX(package_num),
                        0
                    ) + 1

                    FROM package

                    WHERE keystage_id = ?
                ");

                $stmt->execute([
                    $keystage_id
                ]);
            }


            $nextNum = $stmt->fetchColumn();


            /*
            |--------------------------------------------------------------------------
            | INSERT PACKAGE
            |--------------------------------------------------------------------------
            */

            if ($keystage_id === null) {

                $stmt = $pdo->prepare("
                    INSERT INTO package
                    (
                        package_num,
                        lot_id,
                        width,
                        height,
                        length
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $nextNum,
                    $lot_id,
                    $pkg['width'],
                    $pkg['height'],
                    $pkg['length']
                ]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO package
                    (
                        package_num,
                        lot_id,
                        keystage_id,
                        width,
                        height,
                        length
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $nextNum,
                    $lot_id,
                    $keystage_id,
                    $pkg['width'],
                    $pkg['height'],
                    $pkg['length']
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | GET PACKAGE ID
            |--------------------------------------------------------------------------
            */

            $package_id = $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | 6. INSERT PACKAGE CONTENT
            |--------------------------------------------------------------------------
            */

            $stmtContent = $pdo->prepare("
                INSERT INTO package_content
                (
                    package_id,
                    item_id,
                    qty
                )

                VALUES
                (
                    ?,
                    ?,
                    ?
                )
            ");


            foreach ($pkg['items'] as $it) {

                $stmtContent->execute([
                    $package_id,
                    $it['id'],
                    $it['qty']
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 7. CREATE PACKAGE STATUS
            |--------------------------------------------------------------------------
            |
            | THIS IS THE IMPORTANT PART.
            |
            | This connects:
            |
            | DELIVERY
            |      ↓
            | PACKAGE_STATUS
            |      ↓
            | PACKAGE
            |
            */

            $stmtStatus = $pdo->prepare("
                INSERT INTO package_status
                (
                    delivery_id,
                    package_id,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    'pending'
                )
            ");

            $stmtStatus->execute([
                $delivery_id,
                $package_id
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | 8. ACTIVITY LOG
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs
            (
                user_id,
                action
            )

            VALUES
            (
                ?,
                ?
            )
        ");

        $stmt->execute([
            $_SESSION['user_id'] ?? null,

            ($_SESSION['name'] ?? 'User') .
            " added {$numberofPackages} package(s) " .
            "to delivery {$delivery_id}"
        ]);


        /*
        |--------------------------------------------------------------------------
        | 9. COMMIT
        |--------------------------------------------------------------------------
        */

        $pdo->commit();


        /*
        |--------------------------------------------------------------------------
        | 10. SUCCESS
        |--------------------------------------------------------------------------
        */

        echo json_encode([
            'success' => true,

            'redirect' =>
                "packages.php?id=" .
                $delivery['project_id'] .
                "&toast=Packages%20added%20successfully&type=success"
        ]);

        exit;


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

        exit;
    }
}