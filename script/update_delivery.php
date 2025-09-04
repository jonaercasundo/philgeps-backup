<?php
require "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['delivery_id'];
    $dr_no = $_POST['dr_no'];
    $delivery_date = $_POST['delivery_date'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE deliveries 
            SET dr_no=?, delivery_date=?, status=?
            WHERE delivery_id=?");
        $stmt->execute([$dr_no, $delivery_date, $status, $id]);

        header("Location: ../deliveries.php?toast=Delivery updated successfully&type=success");
        exit;
    } catch (PDOException $e) {
        header("Location: ../deliveries.php?toast=Error updating delivery&type=danger");
        exit;
    }
}
