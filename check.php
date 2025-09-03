<?php
session_start();
require 'config/db.php';
$status = $_POST['status'];
if($status == "Pending"){
    $status = "Accepted";
}else{
    $status = "Delivered";
}
$delivered_email = $_POST['email'];
$delivered_phone = $_POST['phone'];
$delivery_id = $_POST['id'];
$delivered_date = date('Y-m-d');
if ($_POST['captcha_answer'] == $_SESSION['captcha']) {
    try {
    $stmt = $pdo->prepare("UPDATE deliveries 
                               SET status = :status,
                                   delivered_date = :delivered_date,
                                   delivered_email = :delivered_email,
                                   delivered_phone = :delivered_phone
                               WHERE delivery_id = :delivery_id");
        $stmt->execute([
            ':status' => $status,
            ':delivered_date' => $delivered_date,
            ':delivered_email' => $delivered_email,
            ':delivered_phone' => $delivered_phone,
            ':delivery_id' => $delivery_id
        ]);

        header('Location: success.php?status=' . urlencode($status));
        exit;

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
} else {
    echo "Captcha failed!";
}
