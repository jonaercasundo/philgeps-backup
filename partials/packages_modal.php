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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">

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
                        value="packages.php?id=<?= htmlspecialchars($project_id) ?>"
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
                    onclick="
                        document
                            .getElementById('deleteForm')
                            .submit();
                    "
                >
                    Delete
                </button>

            </div>

        </div>

    </div>

</div>


<!-- ============================================================
     ADD PACKAGE / ITEMS MODAL
============================================================ -->

<div
    class="modal fade"
    id="addModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">


            <!-- ==================================================
                 HEADER
            ================================================== -->

            <div class="modal-header">

                <h5 class="modal-title">
                    Add New Package
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <!-- ==================================================
                 FORM
            ================================================== -->

            <form
                id="addItemForm"
                method="POST"
                action="script/add_items.php"
            >

                <div class="modal-body">


                    <!-- ==========================================
                         PROJECT ID
                    =========================================== -->

                    <input
                        type="hidden"
                        name="project_id"
                        value="<?= (int) $project_id ?>"
                    >


                    <!-- ==========================================
                         LOT + KEYSTAGE
                    =========================================== -->

                    <?php if (isset($_GET['keystage_id'])): ?>

                        <input
                            type="hidden"
                            name="keystage_id"
                            value="<?= (int) $_GET['keystage_id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="lot_id"
                            value="<?= (int) ($_GET['lot_id'] ?? 0) ?>"
                        >


                        <div class="mb-3">

                            <label class="form-label">
                                Lot / Keystage
                            </label>

                            <div class="alert alert-info mb-0">

                                Lot:
                                <strong>
                                    <?= htmlspecialchars(
                                        $_GET['lot_id'] ?? 'N/A'
                                    ) ?>
                                </strong>

                                &nbsp; | &nbsp;

                                Keystage:
                                <strong>
                                    <?= htmlspecialchars(
                                        $_GET['keystage_id']
                                    ) ?>
                                </strong>

                            </div>

                        </div>


                    <?php else: ?>


                        <div class="mb-3 d-flex">

                            <!-- LOT -->

                            <div class="w-50 me-2">

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

                                    $stmt = $pdo->prepare("
                                        SELECT
                                            lot_id,
                                            lot_name
                                        FROM lot
                                        WHERE project_id = :pid
                                        ORDER BY lot_name ASC
                                    ");

                                    $stmt->execute([
                                        ':pid' => $project_id
                                    ]);

                                    foreach (
                                        $stmt->fetchAll(PDO::FETCH_ASSOC)
                                        as $lot
                                    ):

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


                            <!-- KEYSTAGE -->

                            <div class="w-50">

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


                    <!-- ==========================================
                         PACKAGE DIMENSIONS
                    =========================================== -->

                    <div class="mb-3">

                        <label class="form-label">
                            Package Dimensions
                        </label>

                        <div class="row g-2">


                            <!-- WIDTH -->

                            <div class="col-md-4">

                                <label
                                    for="package_width"
                                    class="form-label"
                                >
                                    Width
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    id="package_width"
                                    name="width"
                                    required
                                >

                            </div>


                            <!-- HEIGHT -->

                            <div class="col-md-4">

                                <label
                                    for="package_height"
                                    class="form-label"
                                >
                                    Height
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    id="package_height"
                                    name="height"
                                    required
                                >

                            </div>


                            <!-- LENGTH -->

                            <div class="col-md-4">

                                <label
                                    for="package_length"
                                    class="form-label"
                                >
                                    Length
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    id="package_length"
                                    name="length"
                                    required
                                >

                            </div>

                        </div>

                    </div>


                    <!-- ==========================================
                         PASTE TABLE
                    =========================================== -->

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


                    <!-- ==========================================
                         DYNAMIC ITEMS
                    =========================================== -->

                    <div class="mb-2">

                        <label class="form-label">
                            Package Items
                        </label>

                    </div>


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

                                $stmt = $pdo->query("
                                    SELECT
                                        item_id,
                                        item_name
                                    FROM item
                                    ORDER BY item_name ASC
                                ");

                                foreach (
                                    $stmt->fetchAll(PDO::FETCH_ASSOC)
                                    as $item
                                ):

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
                                value="1"
                                placeholder="Qty"
                                required
                            >


                            <button
                                type="button"
                                class="btn btn-danger btn-sm removeItemBtn"
                            >
                                ×
                            </button>

                        </div>

                    </div>


                    <!-- ==========================================
                         ADD MORE
                    =========================================== -->

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary mt-2"
                        id="addMoreItem"
                    >
                        + Add Another Item
                    </button>


                </div>


                <!-- ==============================================
                     FOOTER
                =============================================== -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Close
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


                    <div class="mb-3 d-flex">

                        <div class="w-50 me-2">

                            <label class="form-label">
                                Package Num
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="package_num"
                                readonly
                                id="edit_package_num"
                            >

                        </div>


                        <div class="w-50 me-2">

                            <label class="form-label">
                                Lot Number
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                readonly
                                id="edit_lot_num"
                            >

                        </div>


                        <div class="w-100">

                            <label class="form-label">
                                Keystage
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                readonly
                                id="edit_key_num"
                            >

                        </div>

                    </div>


                    <!-- DIMENSIONS -->

                    <div class="mb-3 d-flex">

                        <div class="w-100 me-2">

                            <label class="form-label">
                                Width
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                class="form-control"
                                name="width"
                                id="edit_width"
                                required
                            >

                        </div>


                        <div class="w-100 me-2">

                            <label class="form-label">
                                Height
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                class="form-control"
                                name="height"
                                id="edit_height"
                                required
                            >

                        </div>


                        <div class="w-100">

                            <label class="form-label">
                                Length
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                class="form-control"
                                name="length"
                                id="edit_length"
                                required
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