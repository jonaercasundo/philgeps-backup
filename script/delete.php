<?php
session_start();
require "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $page      = $_POST['source_page'] ?? '';
    $table     = $_POST['table'] ?? '';
    $id        = $_POST['id'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $password  = $_POST['deletePassword'] ?? '';
    $username  = $_SESSION['username'] ?? '';
    $user_id   = $_SESSION['user_id'] ?? null;

    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $condition = preg_replace('/[^a-zA-Z0-9_]/', '', $condition);

    // Basic validation
    if (empty($username) || empty($password) || empty($table) || empty($condition) || empty($id)) {
        header("Location: ../$page&toast=Missing data&type=danger");
        exit;
    }

    try {
        // Authenticate user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            header("Location: ../$page&toast=Invalid password&type=danger");
            exit;
        }

        // ✅ Delete record dynamically
        $sql = "DELETE FROM `$table` WHERE `$condition` = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        // 🔍 Extract project ID from URL (e.g. "schools.php?id=502661")
        $project_id = null;
        if (preg_match('/id=(\d+)/', $page, $matches)) {
            $project_id = $matches[1];
        }

        // 🧾 Get project name if applicable
        $projectName = "Unknown";
        if ($project_id) {
            $stmt = $pdo->prepare("SELECT project_name FROM projects WHERE project_id = ?");
            $stmt->execute([$project_id]);
            $projectName = $stmt->fetchColumn() ?: "Unknown";
        }

        // 🪵 Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([
            $user_id,
            $_SESSION['name'] . " deleted record ID:$id from table '$table' on project '$projectName'"
        ]);

        header("Location: ../$page&toast=Deletion Complete&type=success");
        exit;

    } catch (PDOException $e) {
        header("Location: ../$page&toast=" . urlencode("Database error: " . $e->getMessage()) . "&type=danger");
        exit;
    } catch (Exception $e) {
        header("Location: ../$page&toast=" . urlencode($e->getMessage()) . "&type=danger");
        exit;
    }
}
?>
