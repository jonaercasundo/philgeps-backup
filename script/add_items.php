<?php

require "../config/db.php";

header('Content-Type: application/json');


// ============================================================
// HELPER
// ============================================================

function response($success, $message, $redirect = null)
{
    echo json_encode([
        'success'  => $success,
        'message'  => $message,
        'redirect' => $redirect
    ]);

    exit;
}


// ============================================================
// ONLY POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    response(
        false,
        'Invalid request method.'
    );

}


// ============================================================
// GET POST DATA
// ============================================================

$project_id = isset($_POST['project_id'])
    ? (int) $_POST['project_id']
    : 0;

$lot_id = isset($_POST['lot_id'])
    ? (int) $_POST['lot_id']
    : 0;

$keystage_id = isset($_POST['keystage_id'])
    ? (int) $_POST['keystage_id']
    : 0;

$items = $_POST['items'] ?? [];

$quantities = $_POST['quantities'] ?? [];


// ============================================================
// VALIDATE LOT
// ============================================================

if ($lot_id <= 0) {

    response(
        false,
        'Missing Lot ID.'
    );

}


// ============================================================
// VALIDATE KEYSTAGE
// ============================================================

if ($keystage_id <= 0) {

    response(
        false,
        'Missing Keystage ID.'
    );

}


// ============================================================
// VALIDATE ITEMS
// ============================================================

if (
    !is_array($items) ||
    empty($items)
) {

    response(
        false,
        'Please add at least one item.'
    );

}


// ============================================================
// VALIDATE QUANTITIES
// ============================================================

if (
    !is_array($quantities) ||
    empty($quantities)
) {

    response(
        false,
        'Please provide item quantities.'
    );

}


// ============================================================
// CHECK LOT
// ============================================================

try {

    $stmt = $pdo->prepare("
        SELECT
            lot_id,
            project_id,
            lot_name
        FROM lot
        WHERE lot_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $lot_id
    ]);

    $lot = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$lot) {

        response(
            false,
            'Lot not found.'
        );

    }


    // ========================================================
    // GET PROJECT FROM LOT
    // ========================================================

    $project_id = (int) $lot['project_id'];


    // ========================================================
    // CHECK KEYSTAGE
    // ========================================================

    $stmt = $pdo->prepare("
        SELECT
            keystage_id,
            keystage_num,
            description
        FROM keystage
        WHERE keystage_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $keystage_id
    ]);

    $keystage =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$keystage) {

        response(
            false,
            'Keystage not found.'
        );

    }


    // ========================================================
    // VALIDATE ITEMS BEFORE INSERT
    // ========================================================

    $validatedItems = [];


    foreach ($items as $index => $item_id) {

        $item_id = (int) $item_id;

        $qty = isset($quantities[$index])
            ? (int) $quantities[$index]
            : 0;


        if ($item_id <= 0) {
            continue;
        }


        if ($qty <= 0) {

            response(
                false,
                'Invalid quantity for item #' .
                ($index + 1)
            );

        }


        // ====================================================
        // CHECK ITEM EXISTS
        // ====================================================

        $stmt = $pdo->prepare("
            SELECT
                item_id,
                item_name
            FROM item
            WHERE item_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $item_id
        ]);

        $item =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$item) {

            response(
                false,
                'Item ID ' .
                $item_id .
                ' does not exist.'
            );

        }


        $validatedItems[] = [
            'item_id' => $item_id,
            'qty'     => $qty
        ];

    }


    // ========================================================
    // CHECK VALIDATED ITEMS
    // ========================================================

    if (empty($validatedItems)) {

        response(
            false,
            'No valid items were submitted.'
        );

    }


    // ========================================================
    // START TRANSACTION
    // ========================================================

    $pdo->beginTransaction();


    // ========================================================
    // GENERATE PACKAGE NUMBER
    // ========================================================
    //
    // IMPORTANT:
    //
    // Package number is generated within the LOT.
    //
    // Example:
    //
    // Lot 40:
    //
    // Package 1
    // Package 2
    // Package 3
    //
    // ========================================================

    $stmt = $pdo->prepare("
        SELECT
            MAX(
                CAST(package_num AS UNSIGNED)
            ) AS max_package_num
        FROM package
        WHERE lot_id = ?
    ");

    $stmt->execute([
        $lot_id
    ]);

    $result =
        $stmt->fetch(PDO::FETCH_ASSOC);


    $nextPackageNum =
        ((int) ($result['max_package_num'] ?? 0)) + 1;


    // ========================================================
    // INSERT PACKAGE
    // ========================================================

    $stmt = $pdo->prepare("
        INSERT INTO package
        (
            package_num,
            lot_id,
            keystage_id
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $nextPackageNum,
        $lot_id,
        $keystage_id
    ]);


    $package_id =
        (int) $pdo->lastInsertId();


    if ($package_id <= 0) {

        throw new Exception(
            'Failed to create package.'
        );

    }


    // ========================================================
    // INSERT PACKAGE CONTENT
    // ========================================================

    $contentStmt = $pdo->prepare("
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


    foreach ($validatedItems as $item) {

        $contentStmt->execute([
            $package_id,
            $item['item_id'],
            $item['qty']
        ]);

    }


    // ========================================================
    // COMMIT
    // ========================================================

    $pdo->commit();


    // ========================================================
    // REDIRECT
    // ========================================================

    $redirect =
        "../packages.php?id=" .
        urlencode($project_id) .
        "&lot_id=" .
        urlencode($lot_id) .
        "&keystage_id=" .
        urlencode($keystage_id);


    response(
        true,
        'Package #' .
        $nextPackageNum .
        ' created successfully.',
        $redirect
    );


} catch (Throwable $e) {


    // ========================================================
    // ROLLBACK
    // ========================================================

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    response(
        false,
        'DB Error: ' .
        $e->getMessage()
    );

}