<?php
require "../config/db.php";

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_school_id = $_POST['id'] ?? '';
    $original_school_id = $_POST['original_school_id'] ?? '';
    $school_name = $_POST['school'] ?? '';
    $address = $_POST['address'] ?? '';
    $contact_person = $_POST['person'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $municipality = $_POST['municipality'] ?? '';
    $division = $_POST['division'] ?? '';
    $region = $_POST['region'] ?? '';
    $project_id = $_POST['project_id'] ?? '';

    if (empty($new_school_id) || empty($original_school_id) || empty($school_name) || empty($address) || empty($division) || empty($region)) {
        $response['message'] = 'Required fields are missing.';
        echo json_encode($response);
        exit();
    }

    try {
        // Check if the new school ID already exists for a different school
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM school WHERE school_id = :new_school_id AND school_id != :original_school_id");
        $stmt_check->execute([
            'new_school_id' => $new_school_id,
            'original_school_id' => $original_school_id
        ]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $response['message'] = 'The submitted School ID already exists for another school.';
            echo json_encode($response);
            exit();
        }

        // Proceed with the update
        $stmt = $pdo->prepare(
            "UPDATE school SET 
                school_id = :new_school_id,
                school_name = :school_name, 
                address = :address, 
                contact_person = :contact_person, 
                contact = :contact, 
                municipality = :municipality, 
                division = :division, 
                region = :region 
            WHERE school_id = :original_school_id"
        );

        $stmt->execute([
            'new_school_id' => $new_school_id,
            'school_name' => $school_name,
            'address' => $address,
            'contact_person' => $contact_person,
            'contact' => $contact,
            'municipality' => $municipality,
            'division' => $division,
            'region' => $region,
            'original_school_id' => $original_school_id
        ]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'School updated successfully.';
        } else {
            $response['message'] = 'No changes made or school not found.';
        }

    } catch (PDOException $e) {
        // Check for foreign key constraint violation
        if ($e->getCode() == '23000') {
            $response['message'] = 'Cannot update School ID because it is being used in other parts of the system.';
        } else {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
    echo json_encode($response);
} else {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>