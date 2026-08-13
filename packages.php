<?php

require "template/header.php";
require "config/db.php";
require "script/role_auth.php";

// ============================================================
// ROLE AUTHORIZATION
// ============================================================

$allowed_roles = [
    'Super Admin',
    'Admin',
    'Office Coordinator',
    'Office Admin'
];

redirectIfNotAuthorized($allowed_roles, 'index.php');


// ============================================================
// GET PARAMETERS
// ============================================================

$keystage_id = isset($_GET['keystage_id'])
    ? (int) $_GET['keystage_id']
    : null;

$lot_id = isset($_GET['lot_id'])
    ? (int) $_GET['lot_id']
    : null;

$project_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : null;


// ============================================================
// DETERMINE FILTER
// ============================================================

$ref_id = $keystage_id;
$ref_column = "keystage_id";

if (!$keystage_id && $lot_id) {

    $ref_id = $lot_id;
    $ref_column = "lot_id";

}


// ============================================================
// GET PACKAGES
// ============================================================

try {

    /*
    |--------------------------------------------------------------------------
    | FILTER BY KEYSTAGE OR LOT
    |--------------------------------------------------------------------------
    */

    if ($ref_id) {

        $stmt = $pdo->prepare("
            SELECT

                p.package_id,
                p.package_num,

                /* ==========================
                   LOT
                ========================== */

                p.lot_id,
                l.lot_name AS lot_no,

                /* ==========================
                   KEYSTAGE
                ========================== */

                p.keystage_id,
                ks.keystage_num AS keystage_no,
                ks.description AS keystage_description,

                /* ==========================
                   PACKAGE CONTENT
                ========================== */

                GROUP_CONCAT(
                    i.item_name
                    SEPARATOR '<br>'
                ) AS Content,

                /* ==========================
                   QUANTITY
                ========================== */

                GROUP_CONCAT(
                    pc.qty
                    SEPARATOR '<br>'
                ) AS qty,

                /* ==========================
                   DIMENSIONS
                ========================== */

                p.width,
                p.height,
                p.length,

                CONCAT(
                    p.width,
                    'x',
                    p.height,
                    'x',
                    p.length
                ) AS Dimension

            FROM package p

            LEFT JOIN package_content pc
                ON p.package_id = pc.package_id

            LEFT JOIN item i
                ON pc.item_id = i.item_id

            LEFT JOIN lot l
                ON p.lot_id = l.lot_id

            LEFT JOIN keystage ks
                ON p.keystage_id = ks.keystage_id

            WHERE p.$ref_column = ?

            GROUP BY

                p.package_id,
                p.package_num,

                p.lot_id,
                l.lot_name,

                p.keystage_id,
                ks.keystage_num,
                ks.description,

                p.width,
                p.height,
                p.length

            ORDER BY p.package_num ASC
        ");

        $stmt->execute([
            $ref_id
        ]);


    /*
    |--------------------------------------------------------------------------
    | FILTER BY PROJECT
    |--------------------------------------------------------------------------
    */

    } elseif ($project_id) {

        $stmt = $pdo->prepare("
            SELECT

                p.package_id,
                p.package_num,

                /* ==========================
                   LOT
                ========================== */

                p.lot_id,
                l.lot_name AS lot_no,

                /* ==========================
                   KEYSTAGE
                ========================== */

                p.keystage_id,
                ks.keystage_num AS keystage_no,
                ks.description AS keystage_description,

                /* ==========================
                   PACKAGE CONTENT
                ========================== */

                GROUP_CONCAT(
                    i.item_name
                    SEPARATOR '<br>'
                ) AS Content,

                /* ==========================
                   QUANTITY
                ========================== */

                GROUP_CONCAT(
                    pc.qty
                    SEPARATOR '<br>'
                ) AS qty,

                /* ==========================
                   DIMENSIONS
                ========================== */

                p.width,
                p.height,
                p.length,

                CONCAT(
                    p.width,
                    'x',
                    p.height,
                    'x',
                    p.length
                ) AS Dimension

            FROM package p

            LEFT JOIN package_content pc
                ON p.package_id = pc.package_id

            LEFT JOIN item i
                ON pc.item_id = i.item_id

            LEFT JOIN lot l
                ON p.lot_id = l.lot_id

            LEFT JOIN keystage ks
                ON p.keystage_id = ks.keystage_id

            WHERE l.project_id = ?

            GROUP BY

                p.package_id,
                p.package_num,

                p.lot_id,
                l.lot_name,

                p.keystage_id,
                ks.keystage_num,
                ks.description,

                p.width,
                p.height,
                p.length

            ORDER BY p.package_num ASC
        ");

        $stmt->execute([
            $project_id
        ]);


    } else {

        die(
            "Missing keystage_id, lot_id, or project_id"
        );

    }


    $packages = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


} catch (PDOException $e) {

    die(
        "DB Error: " .
        $e->getMessage()
    );

}

?>


<?php include "partials/packages_modal.php"; ?>


<!-- ============================================================
     PACKAGE LIST
============================================================ -->

<div class="container mt-4">

    <h2 class="mb-3">
        Package List
    </h2>


    <!-- ========================================================
         ACTION BUTTONS
    ========================================================= -->

    <div class="d-flex mb-3 justify-content-between">


        <!-- LEFT SIDE -->

        <div class="d-flex mb-3">

            <button
                data-bs-toggle="modal"
                data-bs-target="#addModal"
                class="btn btn-success mb-3"
            >
                + Add New Package
            </button>

        </div>


        <!-- RIGHT SIDE -->

        <div class="d-flex mb-3">

            <a
                href="script/generate_qr_per_package.php?project_id=<?= (int) $project_id ?>"
                target="_blank"
                class="btn btn-primary mb-3"
            >
                Generate QR
            </a>


            <a
                href="script/generate_barcode_per_package.php?project_id=<?= (int) $project_id ?>"
                target="_blank"
                class="btn btn-info mb-3 ms-2"
            >
                Generate Barcode
            </a>

        </div>

    </div>


    <!-- ========================================================
         PACKAGE TABLE
    ========================================================= -->

    <?php if (empty($packages)): ?>

        <div class="alert alert-info">
            No Packages found.
        </div>

    <?php else: ?>


        <div class="table-responsive">

            <table
                class="table table-bordered table-striped align-middle"
            >

                <thead class="table-dark">

                    <tr>

                        <th>
                            Package No.
                        </th>

                        <th>
                            Lot No.
                        </th>

                        <th>
                            Keystage No.
                        </th>

                        <th>
                            Content
                        </th>

                        <th>
                            Quantity
                        </th>

                        <th>
                            Dimension
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach ($packages as $package): ?>

                        <tr>


                            <!-- ==================================
                                 PACKAGE NUMBER
                            =================================== -->

                            <td>

                                <?= htmlspecialchars(
                                    $package['package_num'] ?? ''
                                ) ?>

                            </td>


                            <!-- ==================================
                                 LOT NUMBER
                            =================================== -->

                            <td>

                                <?php if (
                                    isset($package['lot_no']) &&
                                    $package['lot_no'] !== null &&
                                    $package['lot_no'] !== ''
                                ): ?>

                                    <?= htmlspecialchars(
                                        $package['lot_no']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        N/A
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ==================================
                                 KEYSTAGE NUMBER
                            =================================== -->

                            <td>

                                <?php if (
                                    isset($package['keystage_no']) &&
                                    $package['keystage_no'] !== null
                                ): ?>

                                    <?= htmlspecialchars(
                                        $package['keystage_no']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        N/A
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ==================================
                                 CONTENT
                            =================================== -->

                            <td>

                                <?= $package['Content'] ?? '' ?>

                            </td>


                            <!-- ==================================
                                 QUANTITY
                            =================================== -->

                            <td>

                                <?= $package['qty'] ?? '' ?>

                            </td>


                            <!-- ==================================
                                 DIMENSION
                            =================================== -->

                            <td>

                                <?= htmlspecialchars(
                                    $package['Dimension'] ?? ''
                                ) ?>

                            </td>


                            <!-- ==================================
                                 ACTIONS
                            =================================== -->

                            <td>

                                <!-- VIEW -->

                                <a
                                    href="items.php?id=<?= (int) $project_id ?>&package_id=<?= (int) $package['package_id'] ?>"
                                    class="btn btn-primary d-inline-flex align-items-center mb-1"
                                >

                                    <i class="bi bi-eye fs-4 me-1"></i>

                                    Packages

                                </a>


                                <!-- EDIT -->

                                <a
                                    href="#"
                                    class="btn btn-warning editBtn mb-1"

                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"

                                    data-id="<?= (int) $package['package_id'] ?>"

                                    data-num="<?= htmlspecialchars(
                                        $package['package_num'] ?? ''
                                    ) ?>"

                                    data-width="<?= htmlspecialchars(
                                        $package['width'] ?? ''
                                    ) ?>"

                                    data-length="<?= htmlspecialchars(
                                        $package['length'] ?? ''
                                    ) ?>"

                                    data-height="<?= htmlspecialchars(
                                        $package['height'] ?? ''
                                    ) ?>"
                                >

                                    <i class="bi bi-pencil-square fs-4"></i>

                                </a>


                                <!-- DELETE -->

                                <button
                                    type="button"

                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteModal"

                                    onclick="
                                        document.getElementById(
                                            'delete_packages'
                                        ).value =
                                        <?= (int) $package['package_id'] ?>;
                                    "

                                    class="btn btn-danger mb-1"
                                >

                                    <i class="bi bi-trash fs-4"></i>

                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php endif; ?>

</div>


<!-- ============================================================
     PRELOAD ITEMS
============================================================ -->

<?php

$itemsStmt = $pdo->query("
    SELECT
        item_id,
        item_name
    FROM item
    ORDER BY item_name
");

$allItems = $itemsStmt->fetchAll(
    PDO::FETCH_ASSOC
);

?>


<script>

// ============================================================
// ALL ITEMS
// ============================================================

const allItems =
    <?= json_encode(
        $allItems,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;


// ============================================================
// ADD ITEM FORM
// ============================================================

document
    .getElementById("addItemForm")
    ?.addEventListener(
        "submit",
        function(e) {

            e.preventDefault();


            let formData =
                new FormData(this);


            fetch(
                "script/add_items.php",
                {
                    method: "POST",
                    body: formData
                }
            )

            .then(res => res.json())

            .then(data => {

                if (data.success) {

                    window.location.href =
                        data.redirect;

                } else {

                    alert(
                        "❌ Error: " +
                        data.message
                    );

                }

            })

            .catch(err => {

                console.error(
                    "Server error:",
                    err
                );

                alert(
                    "Server error while adding items."
                );

            });

        }
    );


// ============================================================
// ADD MORE ITEM
// ============================================================

document
    .getElementById("addMoreItem")
    ?.addEventListener(
        "click",
        function() {

            let container =
                document.getElementById(
                    "itemsContainer"
                );


            if (!container) {
                return;
            }


            let options =
                allItems.map(
                    i => `

                        <option
                            value="${i.item_id}"
                        >
                            ${i.item_name}
                        </option>

                    `
                ).join("");


            let newRow =
                document.createElement(
                    "div"
                );


            newRow.classList.add(
                "row",
                "g-2",
                "align-items-center",
                "item-row",
                "mb-2"
            );


            newRow.innerHTML = `

                <div class="d-flex mb-2 itemRow">

                    <select
                        class="form-select"
                        name="items[]"
                    >

                        <option value="">
                            -- Select Item --
                        </option>

                        ${options}

                    </select>


                    <input
                        type="number"
                        class="form-control"
                        name="quantities[]"
                        min="1"
                        required
                    >


                    <button
                        type="button"
                        class="btn btn-danger btn-sm removeItemBtn"
                    >
                        x
                    </button>

                </div>

            `;


            container.appendChild(
                newRow
            );

        }
    );


// ============================================================
// RENDER EDIT ITEM ROW
// ============================================================

function renderItemRow(
    item_id = "",
    qty = ""
) {

    let options =
        allItems.map(
            i => `

                <option
                    value="${i.item_id}"
                    ${
                        i.item_id == item_id
                            ? "selected"
                            : ""
                    }
                >

                    ${i.item_name}

                </option>

            `
        ).join("");


    return `

        <div class="d-flex mb-2 itemRow">

            <select
                name="items[]"
                class="form-control me-2"
            >

                <option value="">
                    -- Select Item --
                </option>

                ${options}

            </select>


            <input
                type="number"
                name="qty[]"
                value="${qty}"
                class="form-control me-2"
                placeholder="Qty"
            >


            <button
                type="button"
                class="btn btn-danger btn-sm removeItemBtn"
            >
                x
            </button>

        </div>

    `;

}


// ============================================================
// DOM READY
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function() {


        // ====================================================
        // EDIT BUTTONS
        // ====================================================

        const editButtons =
            document.querySelectorAll(
                ".editBtn"
            );


        const editItemsDiv =
            document.getElementById(
                "edit_items"
            );


        editButtons.forEach(
            btn => {

                btn.addEventListener(
                    "click",
                    function() {


                        let package_id =
                            this.dataset.id;


                        fetch(
                            "script/get_package.php?package_id=" +
                            package_id
                        )

                        .then(
                            res => res.json()
                        )

                        .then(
                            resp => {


                                if (
                                    !resp.success
                                ) {

                                    alert(
                                        resp.message
                                    );

                                    return;

                                }


                                // ==================================
                                // PACKAGE ID
                                // ==================================

                                document
                                    .getElementById(
                                        "edit_package_id"
                                    )
                                    .value =
                                    resp.package.package_id;


                                // ==================================
                                // PACKAGE NUMBER
                                // ==================================

                                document
                                    .getElementById(
                                        "edit_package_num"
                                    )
                                    .value =
                                    resp.package.package_num;


                                // ==================================
                                // LOT
                                // ==================================

                                const lotInput =
                                    document.getElementById(
                                        "edit_lot_num"
                                    );


                                if (lotInput) {

                                    lotInput.value =
                                        resp.package.lot_name
                                            ? "Lot " +
                                              resp.package.lot_name
                                            : "N/A";

                                }


                                // ==================================
                                // KEYSTAGE
                                // ==================================

                                const keyInput =
                                    document.getElementById(
                                        "edit_key_num"
                                    );


                                if (keyInput) {

                                    keyInput.value =
                                        resp.package.keystage_name
                                            ? "Keystage " +
                                              resp.package.keystage_name +
                                              " " +
                                              (
                                                  resp.package.description ??
                                                  ""
                                              )
                                            : "No Keystage Assigned";

                                }


                                // ==================================
                                // DIMENSIONS
                                // ==================================

                                document
                                    .getElementById(
                                        "edit_width"
                                    )
                                    .value =
                                    resp.package.width;


                                document
                                    .getElementById(
                                        "edit_height"
                                    )
                                    .value =
                                    resp.package.height;


                                document
                                    .getElementById(
                                        "edit_length"
                                    )
                                    .value =
                                    resp.package.length;


                                // ==================================
                                // ITEMS
                                // ==================================

                                if (editItemsDiv) {

                                    editItemsDiv.innerHTML =
                                        "";


                                    resp.items.forEach(
                                        it => {

                                            editItemsDiv.innerHTML +=
                                                renderItemRow(
                                                    it.item_id,
                                                    it.qty
                                                );

                                        }
                                    );

                                }

                            }
                        )

                        .catch(
                            err => {

                                console.error(
                                    "Error loading package:",
                                    err
                                );

                                alert(
                                    "Unable to load package information."
                                );

                            }
                        );

                    }
                );

            }
        );


        // ====================================================
        // ADD EDIT ITEM
        // ====================================================

        document
            .getElementById("addItemBtn")
            ?.addEventListener(
                "click",
                function() {

                    if (editItemsDiv) {

                        editItemsDiv.innerHTML +=
                            renderItemRow();

                    }

                }
            );


        // ====================================================
        // REMOVE ITEM
        // ====================================================

        document.addEventListener(
            "click",
            function(e) {

                if (
                    e.target.classList.contains(
                        "removeItemBtn"
                    )
                ) {

                    const row =
                        e.target.closest(
                            ".itemRow"
                        );


                    if (row) {

                        row.remove();

                    }

                }

            }
        );


        // ====================================================
        // SAVE EDIT
        // ====================================================

        document
            .getElementById("saveEditBtn")
            ?.addEventListener(
                "click",
                function() {


                    const form =
                        document.getElementById(
                            "editForm"
                        );


                    if (!form) {

                        alert(
                            "Edit form not found."
                        );

                        return;

                    }


                    const formData =
                        new FormData(form);


                    fetch(
                        "script/update_package.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    )

                    .then(
                        res => res.json()
                    )

                    .then(
                        resp => {


                            if (
                                resp.success
                            ) {

                                window.location.href =
                                    resp.redirect;

                            } else {

                                alert(
                                    "❌ Error: " +
                                    resp.message
                                );

                            }

                        }
                    )

                    .catch(
                        err => {

                            console.error(
                                "Update error:",
                                err
                            );

                            alert(
                                "Server error while updating package."
                            );

                        }
                    );

                }
            );

    }
);


// ============================================================
// POPULATE KEYSTAGE
// ============================================================

function populateKeystage() {


    const lotElement =
        document.getElementById(
            "lot_id"
        );


    const keystageElement =
        document.getElementById(
            "keystage_id"
        );


    if (
        !lotElement ||
        !keystageElement
    ) {

        return;

    }


    const lot_id =
        lotElement.value;


    // Clear existing options

    keystageElement.innerHTML = "";


    fetch(
        "script/get_keystage.php?lotid=" +
        encodeURIComponent(lot_id),
        {
            method: "GET"
        }
    )

    .then(
        res => res.json()
    )

    .then(
        data => {


            if (
                !data.keystages ||
                !Array.isArray(
                    data.keystages
                )
            ) {

                return;

            }


            data.keystages.forEach(
                keystage => {


                    let option =
                        document.createElement(
                            "option"
                        );


                    option.value =
                        keystage.id;


                    option.textContent =
                        keystage.name;


                    keystageElement.appendChild(
                        option
                    );


                }
            );


            keystageElement.disabled =
                data.keystages.length === 0;

        }
    )

    .catch(
        err => {

            console.error(
                "Error loading keystages:",
                err
            );

        }
    )

    .finally(
        () => {

            if (
                typeof hideLoading ===
                "function"
            ) {

                hideLoading();

            }

        }
    );

}


// ============================================================
// SYNC TABLE TO FORM
// ============================================================

const myTable =
    document.getElementById(
        "myTable"
    );


if (myTable) {

    myTable.addEventListener(
        "input",
        syncTableToForm
    );

}


function syncTableToForm() {


    const rows =
        document.querySelectorAll(
            "#myTable tr"
        );


    const container =
        document.getElementById(
            "itemsContainer"
        );


    if (!container) {

        return;

    }


    container.innerHTML = "";


    rows.forEach(
        (row, index) => {


            // Skip header

            if (index === 0) {

                return;

            }


            const cells =
                row.querySelectorAll(
                    "td"
                );


            if (
                cells.length < 1
            ) {

                return;

            }


            const itemText =
                (
                    cells[0]?.innerText ||
                    ""
                ).trim();


            const qtyText =
                (
                    cells[1]?.innerText ||
                    ""
                ).trim();


            const dimText =
                (
                    cells[2]?.innerText ||
                    ""
                ).trim();


            // ================================================
            // NORMALIZE DIMENSION
            // ================================================

            let normalizedDim = "";


            if (dimText) {

                normalizedDim =
                    dimText
                        .replace(
                            /\s*/g,
                            ""
                        )
                        .replace(
                            /[X×]/gi,
                            "x"
                        );

            }


            // ================================================
            // FIND ITEM
            // ================================================

            const selectedItem =
                allItems.find(
                    i =>
                        normalize(
                            i.item_name
                        ) ===
                        normalize(
                            itemText
                        )
                );


            // ================================================
            // ITEM OPTIONS
            // ================================================

            const options =
                allItems.map(
                    i => `

                        <option
                            value="${i.item_id}"
                            ${
                                selectedItem &&
                                i.item_id ===
                                selectedItem.item_id
                                    ? "selected"
                                    : ""
                            }
                        >

                            ${i.item_name}

                        </option>

                    `
                ).join("");


            // ================================================
            // CREATE ROW
            // ================================================

            const newRow =
                document.createElement(
                    "div"
                );


            newRow.classList.add(
                "row",
                "g-2",
                "align-items-center",
                "item-row",
                "mb-2"
            );


            newRow.innerHTML = `

                <div class="d-flex mb-2 itemRow">

                    <select
                        class="form-select"
                        name="items[]"
                    >

                        <option value="">
                            -- Select Item --
                        </option>

                        ${options}

                    </select>


                    <input
                        type="number"
                        class="form-control"
                        name="quantities[]"
                        value="${qtyText || 1}"
                        min="1"
                        required
                    >


                    <input
                        type="hidden"
                        name="dimention[]"
                        value="${normalizedDim}"
                    >


                    <button
                        type="button"
                        class="btn btn-danger btn-sm removeItemBtn"
                    >
                        x
                    </button>

                </div>

            `;


            container.appendChild(
                newRow
            );

        }
    );

}


// ============================================================
// NORMALIZE
// ============================================================

function normalize(str) {

    return String(
        str || ""
    )
    .toLowerCase()
    .replace(
        /[^\w\s]/gi,
        ""
    )
    .trim();

}

</script>


<!-- ============================================================
     PROJECT DETAILS JS
============================================================ -->

<script src="assets/js/project_details.js"></script>


<?php require "template/footer.php"; ?>