<?php
require "../config/db.php"; // include your PDO connection

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    $user_id = $_POST['user_id'] ?? null;
    $password = trim($_POST['password'] ?? '');

    if (!$user_id || !$password) {
        throw new Exception("Missing required fields.");
    }

    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update in database
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
    $stmt->execute([
        ':password' => $hashedPassword,
        ':user_id' => $user_id
    ]);

    // Redirect or success response
    header("Location: ../users.php?toast=Password Updated&type=success");
    exit;
} catch (Exception $e) {
    error_log("Password Update Error: " . $e->getMessage());
    header("Location: ../users.php?type=danger&toast=" . urlencode($e->getMessage()));
    exit;
}
?>
