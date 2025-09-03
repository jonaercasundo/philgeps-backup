<?php
require "../config/db.php"; // adjust
$where =[];
if(isset($_POST['year'])){
    $year = $_POST['year'];
    $where[] = "Year(d.created_at)= '$year'";
};
if(isset($_POST['project_id'])){
    $project_id = $_POST['project_id'];
    $where[] = "d.project_id='$project_id'";
};
if(isset($_POST['status'])){
    $status = $_POST['status'];
    $where[] = "d.status= '$status'";
};

$sql = "SELECT d.*, p.project_id, p.project_name
        FROM deliveries d JOIN projects p ON p.project_id = d.project_id WHERE " . implode(" AND ", $where);
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);


