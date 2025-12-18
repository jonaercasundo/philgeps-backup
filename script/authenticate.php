<?php
session_start();
require "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required.";
        header("Location: ../index.php?toast=Missing credentials&type=danger");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['warehouse_id'] = $user['warehouse_id'] ?? null;


            // Define main navigation links
            switch($_SESSION['role']){
            case "Office Admin":
                header("Location: ../dashboard.php?toast=Welcome ". $_SESSION['name']."!&type=success");
            break;
            case "Warehouse Admin":
                header("Location: ../inventory.php?toast=Welcome ". $_SESSION['name']."!&type=success");
                
            break;
            case "Warehouse Coordinator":
                header("Location: ../inventory.php?toast=Welcome ". $_SESSION['name']."!&type=success");
                
            break;
            case "Office Coordinator":
                header("Location: ../projects.php?toast=Welcome ". $_SESSION['name']."!&type=success");
                
            break;
            case "Viewer":
                header("Location: ../projects.php?toast=Welcome ". $_SESSION['name']."!&type=success");
                
            break;
            case "Logistics":
                header("Location: ../logistics_package.php?toast=Welcome ". $_SESSION['name']."!&type=success");
            break;
            default:
                header("Location: ../dashboard.php?toast=Welcome ". $_SESSION['name']."!&type=success");
            break;
            }
            exit;
            
        } else {
            $_SESSION['error'] = "Invalid username or password!";
            header("Location: ../index.php?toast=Invalid username or password!&type=danger");
            exit;
        }
    } catch (PDOException $e) {
        // Handle DB error
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: ../index.php?toast=Database error&type=danger");
        exit;
    }
}
