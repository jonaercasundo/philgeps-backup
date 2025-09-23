<?php
session_start();
require "../config/db.php";
$page = $_POST['source_page'];
$table = $_POST['table'];
$id = $_POST['id'];
$condition = $_POST['condition'];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_SESSION['username'];
    $password = $_POST['deletePassword'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        header("Location: ../$page&toast=Missing credentials&type=danger");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $condition = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

            header("Location: ../$page&toast=Deletion Complete&type=success");
            exit;
        } else {
            $_SESSION['error'] = "Invalid username or password!";
            header("Location: ../$page&toast=Invalid username or password!&type=danger");
            exit;
        }
        echo "$username, $password";
    exit;
    } catch (PDOException $e) {
        // Handle DB error
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: ../$page&toast=Database error&type=danger");
        exit;
    }
}
