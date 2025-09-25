<?php
require "../config/db.php"; // your PDO connection

$agency = $_GET['projectid'] ?? '';

$response = [
  "lots" => []
];


  $stmt = $pdo->query("SELECT lot_id as id, lot_name, agency as name FROM lot WHERE project_id = $agency");
  $response["lots"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/json");
echo json_encode($response);
