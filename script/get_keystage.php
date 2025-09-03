<?php
require "../config/db.php"; // your PDO connection
$id = $_GET['lotid'];

$stmt = $pdo->prepare("SELECT keystage_id as id,  CONCAT(keystage_num, ' ', description) AS name FROM `keystage` WHERE lot_id = $id");
$stmt->execute();
$response = [
  "keystages" => []
];
$response["keystages"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($response);
