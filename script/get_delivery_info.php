<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['delivery_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing delivery_id parameter']);
    exit;
}

$delivery_id = $_GET['delivery_id'];

try {
    // Prepare and execute query to get the DR number and status for the given delivery ID
    $stmt = $pdo->prepare("SELECT dr_no, status FROM deliveries WHERE delivery_id = :delivery_id");
    $stmt->bindParam(':delivery_id', $delivery_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        if ($result['status'] === 'delivered') {
            echo json_encode([
                'success' => true,
                'dr_no' => $result['dr_no'],
                'status' => $result['status']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Delivery status is "' . $result['status'] . '", only "delivered" status allowed'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Delivery not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>