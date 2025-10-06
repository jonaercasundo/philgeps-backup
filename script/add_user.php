<?php
require "../config/db.php";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['uname']);
    $name = trim($_POST['fname']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // hash the password
    $role = $_POST['role'];
    $warehouse_id = !empty($_POST['warehouse_id']) ? $_POST['warehouse_id'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role, warehouse_id) 
                               VALUES (:name, :username, :password, :role, :warehouse_id)");
        $stmt->execute([
            ':name' => $name,
            ':username' => $username,
            ':password' => $password,
            ':role' => $role,
            ':warehouse_id' => $warehouse_id
        ]);

    header("Location: ../users.php?toast=User Added!&type=success");
} catch (Exception $e) {
    header("Location: ../users.php?toast=".$e->getMessage()."&type=danger");
}
}