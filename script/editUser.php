<?php
require "../config/db.php"; // include your PDO connection

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    // Get and sanitize inputs
    $user_id = $_POST['user_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? ''); // your form uses "school" as the name
    $role = trim($_POST['role'] ?? '');
    $warehouse_id = $_POST['warehouse_id'] ?? null;

    if (!$user_id || !$name || !$email || !$role) {
        throw new Exception("Missing required fields.");
    }

    // Prepare SQL
    $sql = "UPDATE users 
            SET name = :name, username = :email, role = :role, warehouse_id = :warehouse_id 
            WHERE user_id = :user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':role' => $role,
        ':warehouse_id' => $warehouse_id ?: null,
        ':user_id' => $user_id
    ]);

    // Redirect or respond with success
    header("Location: ../users.php?toast=Edited User Succesfully&type=success");
    exit;
} catch (Exception $e) {
    error_log("Edit User Error: " . $e->getMessage());
    header("Location: ../users.php?type=danger&toast=" . urlencode($e->getMessage()));
    exit;
}
?>
