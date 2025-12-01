<?php
session_start();
require "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sales_gen_id = $_POST['sales_gen_id'] ?? null;
    $net_sales = $_POST['net_sales'] ?? null;
    $cogs = $_POST['cogs'] ?? null;
    $total_cost_of_sales = $_POST['total_cost_of_sales'] ?? null;
    $pgp = $_POST['pgp'] ?? null;
    $gpm = $_POST['gpm'] ?? null;
    $opex = $_POST['opex'] ?? null;
    $ppl = $_POST['ppl'] ?? null;
    $npm = $_POST['npm'] ?? null;

    if (!$sales_gen_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing sales generation ID'
        ]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Update sales_generation
        $stmt = $pdo->prepare("
            UPDATE sales_generation 
            SET net_sales = :net_sales,
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

        // Fetch project details
        $stmt = $pdo->prepare("
            SELECT p.project_name, p.project_id
            FROM sales_generation sg
            JOIN projects p ON p.project_id = sg.project_id
            WHERE sg.sales_gen_id = ?
        ");
        $stmt->execute([$sales_gen_id]);
        $projectInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Log activity
        $projectName = $projectInfo['project_name'] ?? 'Unknown Project';
        $action = sprintf(
            "%s updated sales generation for project %s",
            $_SESSION['name'],
            $projectName
        );

        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $action]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Sales generation updated successfully'
        ]);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update sales generation: ' . $e->getMessage()
        ]);
        exit;
    }
}
?>