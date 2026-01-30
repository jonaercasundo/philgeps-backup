<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input
        $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
        $refNo = trim($_POST['ref_no'] ?? '');
        $projectName = trim($_POST['project_name'] ?? '');
        $contractAmount = filter_var($_POST['rawNumber'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $abc = filter_var($_POST['rawNumber2'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $status = trim($_POST['status'] ?? '');

        // Validate required fields
        if (!$projectId || !$refNo || !$projectName || !$startDate || !$endDate || !$status) {
            throw new Exception('All required fields must be filled out.');
        }

        // Validate dates
        if (strtotime($startDate) === false || strtotime($endDate) === false) {
            throw new Exception('Invalid date format.');
        }

        // Validate that end date is not before start date
        if (strtotime($endDate) < strtotime($startDate)) {
            throw new Exception('End date cannot be before start date.');
        }

        // Prepare and execute the update query
        $sql = "UPDATE projects SET
                    ref_no = :ref_no,
                    project_name = :project_name,
                    contract_amount = :contract_amount,
                    ABC = :abc,
                    start_date = :start_date,
                    end_date = :end_date,
                    status = :status
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':ref_no', $refNo, PDO::PARAM_STR);
        $stmt->bindParam(':project_name', $projectName, PDO::PARAM_STR);
        $stmt->bindParam(':contract_amount', $contractAmount, PDO::PARAM_STR); // Using STR to preserve decimal precision
        $stmt->bindParam(':abc', $abc, PDO::PARAM_STR); // Using STR to preserve decimal precision
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);

        $result = $stmt->execute();

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
        } else {
            throw new Exception('Failed to update project in database.');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>