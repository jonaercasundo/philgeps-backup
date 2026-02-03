<?php
session_start();
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log incoming data
    error_log("Received POST data: " . print_r($_POST, true));
    error_log("Raw input: " . file_get_contents('php://input'));

    // Try to get data from JSON input first
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if ($requestData && isset($requestData['updates'])) {
        $updates = $requestData['updates'];
    } else {
        // Fallback to form data
        $updates = isset($_POST['updates']) ? json_decode($_POST['updates'], true) : [];

        // If it's still a string, it might be double-encoded
        if (is_string($updates)) {
            $updates = json_decode($updates, true);
        }

        // If updates is still empty, check if the data was sent directly without JSON encoding
        if (empty($updates) && isset($_POST['updates'])) {
            $updates = $_POST['updates'];
        }
    }

    // Debug: Log parsed updates
    error_log("Parsed updates: " . print_r($updates, true));

    if (empty($updates)) {
        echo json_encode([
            'success' => false,
            'message' => 'No updates provided. Input: ' . $input . ', POST: ' . print_r($_POST, true)
        ]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        foreach ($updates as $update) {
            $sales_gen_id = $update['sales_gen_id'] ?? null;

            if (!$sales_gen_id) {
                throw new Exception('Missing sales generation ID');
            }

            // Extract all fields from the update
            $project_name = $update['project_name'] ?? null;
            $abc = $update['abc'] ?? null;
            $contract_amount = $update['contract_amount'] ?? null;
            $net_sales = $update['net_sales'] ?? null;
            $cogs = $update['cogs'] ?? null;
            $total_cost_of_sales = $update['total_cost_of_sales'] ?? null;
            $pgp = $update['pgp'] ?? null;
            $gpm = $update['gpm'] ?? null;
            $opex = $update['opex'] ?? null;
            $ppl = $update['ppl'] ?? null;
            $npm = $update['npm'] ?? null;

            // Update sales_generation
            $stmt = $pdo->prepare("
                UPDATE sales_generation
                SET project_name = :project_name,
                    abc = :abc,
                    contract_amount = :contract_amount,
                    net_sales = :net_sales,
                    cogs = :cogs,
                    total_cost_of_sales = :total_cost_of_sales,
                    pgp = :pgp,
                    gpm = :gpm,
                    opex = :opex,
                    ppl = :ppl,
                    npm = :npm,
                    updated_at = NOW()
                WHERE sales_gen_id = :id
            ");
            $stmt->execute([
                ':project_name' => $project_name,
                ':abc' => $abc,
                ':contract_amount' => $contract_amount,
                ':net_sales' => $net_sales,
                ':cogs' => $cogs,
                ':total_cost_of_sales' => $total_cost_of_sales,
                ':pgp' => $pgp,
                ':gpm' => $gpm,
                ':opex' => $opex,
                ':ppl' => $ppl,
                ':npm' => $npm,
                ':id' => $sales_gen_id
            ]);
        }

        // Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['name'] . " updated multiple sales generation records"
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'All sales generation records updated successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update sales generation records: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>