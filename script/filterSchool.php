<?php
require "../config/db.php"; // adjust
$where =[];
if(isset($_POST['region'])){
    $region = $_POST['region'];
    $where[] = "region= '$region'";
};
if(isset($_POST['division'])){
    $division = $_POST['division'];
    $where[] = "division='$division'";
};
if(isset($_POST['municipality'])){
    $municipality = $_POST['municipality'];
    $where[] = "municipality= '$municipality'";
};

$sql = "SELECT * FROM school WHERE " . implode(" AND ", $where);
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
