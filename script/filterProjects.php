<?php
require "../config/db.php"; // adjust
$where =[];
if(isset($_POST['year'])){
    $year = $_POST['year'];
    $where[] = "Year(created_at)= '$year'";
};
if(isset($_POST['agency'])){
    $agency = $_POST['agency'];
    $where[] = "agency='$agency'";
};
if(isset($_POST['status'])){
    $status = $_POST['status'];
    $where[] = "status= '$status'";
};

$sql = "SELECT * FROM projects WHERE " . implode(" AND ", $where);
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
