<?php
require "config/db.php";

// Hash a password before inserting
$hashedPassword = password_hash("rey", PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->execute(["rey@metro-mobilia.com", $hashedPassword]);

echo "User created!";
