<?php
require "db.php"; // your PDO connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = $_POST["name"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $phone]);

        echo json_encode(["status" => "success", "message" => "User registered successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
