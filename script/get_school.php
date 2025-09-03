<?php
require "../config/db.php"; // your PDO connection
$id = $_GET['id'];
$q = isset($_GET['q']) ? $_GET['q'] : '';
$limit = 20; // only return 20 results

$stmt = $pdo->prepare("SELECT school_id, school_name, address
                       FROM school 
                       WHERE school_name LIKE :q AND project_id = $id
                       ORDER BY school_name 
                       LIMIT :limit");
$stmt->bindValue(':q', "%$q%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
