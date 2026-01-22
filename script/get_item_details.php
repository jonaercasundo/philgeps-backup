<?php
header('Content-Type: application/json');
require_once "../config/db.php";

// Default response structure
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (isset($pdo) && $pdo !== null) {
    if (!isset($_GET['item_id']) || empty($_GET['item_id'])) {
        $response = ['success' => false, 'message' => 'Item ID not provided.'];
    } else {
        try {
            $item_id = $_GET['item_id'];
            
            // Using 'items' table as it is the likely correct name.
            $sql = "SELECT item_id, item_name FROM item WHERE item_id = :item_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $response = ['success' => true, 'item' => $item];
            } else {
                $response = ['success' => false, 'message' => 'Item not found.'];
            }
            
        } catch (Exception $e) {
            // In case of a DB error, format the response consistently
            $response = ['success' => false, 'message' => "DB Query Error: " . $e->getMessage()];
        }
    }
} else {
    $response = ['success' => false, 'message' => "Database connection not available."];
}

echo json_encode($response);
?>