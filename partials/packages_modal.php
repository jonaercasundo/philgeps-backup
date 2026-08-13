<?php
/*
|--------------------------------------------------------------------------
| Current Project
|--------------------------------------------------------------------------
*/

$currentProjectId = isset($project_id)
    ? (int) $project_id
    : (int) ($_GET['id'] ?? 0);

$currentLotId = isset($_GET['lot_id'])
    ? (int) $_GET['lot_id']
    : 0;

$currentKeystageId = isset($_GET['keystage_id'])
    ? (int) $_GET['keystage_id']
    : 0;
?>


<!-- ============================================================
     DELETE PACKAGE MODAL
============================================================ -->

<div
    class="modal fade"
    id="deleteModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-md">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Delete Package
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <form
                    id="deleteForm"
                    method="POST"
                    action="script/delete.php"
                >

                    <input
                        type="hidden"
                        name="source_page"
                        value="packages.php?id=<?= $currentProjectId ?>"
                    >

                    <input
                        type="hidden"
                        id="delete_packages"
                        name="id"
                    >

                    <input
                        type="hidden"
                        name="table"
                        value="package"
                    >

                    <input
                        type="hidden"
                        name="condition"
                        value="package_id"
                    >


                    <div class="mb-3">

                        <label class="form-label">
                            Input password to continue
                        </label>

                        <input
                            type="password"
                            name="deletePassword"
                            class="form-control"
                            required
                        >

                    </div>

                </form>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="document.getElementById('deleteForm').submit();"
                >
                    Delete
                </button>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     ADD PACKAGE MODAL
============================================================ -->

<div
    class="modal fade"
    id="addModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">


            <!-- ====================================================
                 HEADER
            ===================================================== -->

            <div class="modal-header">

                <h5 class="modal-title">
                    Add New Package
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <!-- ====================================================
                 FORM
            ===================================================== -->

            <form
                id="addItemForm"
                method="POST"
                action="script/add_items.php"
            >

                <div class="modal-body">


                    <!-- =================================================
                         PROJECT
                    ================================================== -->

                    <input
                        type="hidden"
                        name="project_id"
                        value="<?= $currentProjectId ?>"
                    >


                    <!-- =================================================
                         LOT / KEYSTAGE
                    ================================================== -->

                    <?php if ($currentKeystageId > 0): ?>

                        <!-- Existing LOT + KEYSTAGE context -->

                        <input
                            type="hidden"
                            name="lot_id"
                            value="<?= $currentLotId ?>"
                        >

                        <input
                            type="hidden"
                            name="keystage_id"
                            value="<?= $currentKeystageId ?>"
                        >


                        <div class="row mb-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Lot
                                </label>

                                <?php

                                $lotStmt = $pdo->prepare("
                                    SELECT
                                        lot_id,
                                        lot_name
                                    FROM lot
                                    WHERE lot_id = ?
                                    LIMIT 1
                                ");

                                $lotStmt->execute([
                                    $currentLotId
                                ]);

                                $currentLot =
                                    $lotStmt->fetch(PDO::FETCH_ASSOC);

                                ?>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $currentLot['lot_name'] ?? 'N/A'
                                    ) ?>"
                                    readonly
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Keystage
                                </label>

                                <?php

                                $ksStmt = $pdo->prepare("
                                    SELECT
                                        keystage_id,
                                        keystage_num,
                                        description
                                    FROM keystage
                                    WHERE keystage_id = ?
                                    LIMIT 1
                                ");

                                $ksStmt->execute([
                                    $currentKeystageId
                                ]);

                                $currentKeystage =
                                    $ksStmt->fetch(PDO::FETCH_ASSOC);

                                ?>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        trim(
                                            'Keystage ' .
                                            ($currentKeystage['keystage_num'] ?? '') .
                                            ' ' .
                                            ($currentKeystage['description'] ?? '')
                                        )
                                    ) ?>"
                                    readonly
                                >

                            </div>

                        </div>


                    <?php else: ?>


                        <!-- =========================================
                             SELECT LOT
                        ========================================== -->

                        <div class="row mb-3">

                            <div class="col-md-6">

                                <label
                                    for="lot_id"
                                    class="form-label"
                                >
                                    Lot
                                </label>

                                <select
                                    class="form-select"
                                    id="lot_id"
                                    name="lot_id"
                                    onchange="populateKeystage()"
                                    required
                                >

                                    <option value="">
                                        -- Select Lot --
                                    </option>

                                    <?php

                                    $lotStmt = $pdo->prepare("
                                        SELECT
                                            lot_id,
                                            lot_name
                                        FROM lot
                                        WHERE project_id = ?
                                        ORDER BY lot_name ASC
                                    ");

                                    $lotStmt->execute([
                                        $currentProjectId
                                    ]);

                                    $lots =
                                        $lotStmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($lots as $lot):

                                    ?>

                                        <option
                                            value="<?= (int) $lot['lot_id'] ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $lot['lot_name']
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =========================================
                                 KEYSTAGE
                            ========================================== -->

                            <div class="col-md-6">

                                <label
                                    for="keystage_id"
                                    class="form-label"
                                >
                                    Keystage
                                </label>

                                <select
                                    class="form-select"
                                    id="keystage_id"
                                    name="keystage_id"
                                    required
                                    disabled
                                >

                                    <option value="">
                                        -- Select Keystage --
                                    </option>

                                </select>

                            </div>

                        </div>


                    <?php endif; ?>


                    <!-- =================================================
                         PASTE ITEMS
                    ================================================== -->

                    <div class="mb-3">

                        <label class="form-label">
                            Paste Items
                        </label>

                        <table
                            class="table table-bordered table-hover table-striped align-middle"
                            id="myTable"
                        >

                            <thead class="table-dark">

                                <tr>

                                    <th>
                                        Paste the table below
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr id="pasteHere">

                                    <td contenteditable="true">

                                        Here!

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <!-- =================================================
                         ITEMS
                    ================================================== -->

                    <div class="mb-3">

                        <label class="form-label">
                            Package Items
                        </label>


                        <div id="itemsContainer">

                            <div class="d-flex mb-2 itemRow">


                                <select
                                    class="form-select me-2"
                                    name="items[]"
                                    required
                                >

                                    <option value="">
                                        -- Select Item --
                                    </option>

                                    <?php

                                    $itemStmt = $pdo->query("
                                        SELECT
                                            item_id,
                                            item_name
                                        FROM item
                                        ORDER BY item_name ASC
                                    ");

                                    $items =
                                        $itemStmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($items as $item):

                                    ?>

                                        <option
                                            value="<?= (int) $item['item_id'] ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $item['item_name']
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <input
                                    type="number"
                                    class="form-control me-2"
                                    name="quantities[]"
                                    min="1"
                                    required
                                    placeholder="Qty"
                                >


                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm removeItemBtn"
                                >
                                    ×
                                </button>

                            </div>

                        </div>


                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary mt-2"
                            id="addMoreItem"
                        >
                            + Add Another Item
                        </button>

                    </div>

                </div>


                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        Save Package
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- ============================================================
     EDIT PACKAGE MODAL
============================================================ -->

<div
    class="modal fade"
    id="editModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Edit Package
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <form id="editForm">

                    <input
                        type="hidden"
                        name="package_id"
                        id="edit_package_id"
                    >


                    <!-- PACKAGE INFORMATION -->

                    <div class="row mb-3">

                        <div class="col-md-4">

                            <label class="form-label">
                                Package Num
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="package_num"
                                id="edit_package_num"
                                readonly
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Lot Number
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="edit_lot_num"
                                readonly
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Keystage
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="edit_key_num"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- DIMENSIONS -->

                    <div class="row mb-3">

                        <div class="col-md-4">

                            <label class="form-label">
                                Width
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                class="form-control"
                                name="width"
                                id="edit_width"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Height
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                class="form-control"
                                name="height"
                                id="edit_height"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Length
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                class="form-control"
                                name="length"
                                id="edit_length"
                            >

                        </div>

                    </div>


                    <!-- ITEMS -->

                    <div class="mb-3">

                        <label class="form-label">
                            Items in Package
                        </label>

                        <div id="edit_items"></div>

                        <button
                            type="button"
                            class="btn btn-sm btn-success mt-2"
                            id="addItemBtn"
                        >
                            + Add Item
                        </button>

                    </div>

                </form>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="saveEditBtn"
                >
                    Save Changes
                </button>

            </div>

        </div>

    </div>

</div>