<?php
// patient/delete_lab_report.php
require_once '../includes/db_connection.php';
session_start(); // Ensure session is started for $_SESSION variables

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

// Get patient_id from session
$user_id = $_SESSION['user_id'];
$patient_id = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $patient_data = $stmt->fetch();
    if ($patient_data) {
        $patient_id = $patient_data['id'];
    } else {
        $response['message'] = 'Patient record not found.';
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error fetching patient ID.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

    if (!$id || !in_array($type, ['doctor_ordered', 'patient_uploaded'])) {
        $response['message'] = 'Invalid ID or lab report type.';
        echo json_encode($response);
        exit();
    }

    try {
        if ($type == 'patient_uploaded') {
            // Fetch image path before deleting the record
            $stmt = $pdo->prepare("SELECT image_path FROM patient_uploaded_lab_reports WHERE id = ? AND patient_id = ?");
            $stmt->execute([$id, $patient_id]);
            $uploaded_report = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($uploaded_report) {
                // Delete the record from the database
                $stmt = $pdo->prepare("DELETE FROM patient_uploaded_lab_reports WHERE id = ? AND patient_id = ?");
                if ($stmt->execute([$id, $patient_id])) {
                    // If there was an image, attempt to delete the file
                    if (!empty($uploaded_report['image_path'])) {
                        $file_path = '../' . $uploaded_report['image_path']; // Adjust path
                        if (file_exists($file_path) && is_file($file_path)) {
                            unlink($file_path); // Delete the file
                        }
                    }
                    $response['success'] = true;
                    $response['message'] = 'Patient-uploaded lab report deleted successfully.';
                } else {
                    $response['message'] = 'Failed to delete patient-uploaded lab report from database.';
                }
            } else {
                $response['message'] = 'Patient-uploaded lab report not found or unauthorized.';
            }
        } elseif ($type == 'doctor_ordered') {
            // For doctor-ordered lab results, typically patients cannot delete them directly.
            // This example allows deletion if the lab result belongs to the patient.
            // You might choose to disallow this or implement a "request deletion" feature.
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_results WHERE id = ? AND patient_id = ?");
            $stmt->execute([$id, $patient_id]);
            if ($stmt->fetchColumn() > 0) {
                 $stmt_delete = $pdo->prepare("DELETE FROM lab_results WHERE id = ? AND patient_id = ?");
                 if ($stmt_delete->execute([$id, $patient_id])) {
                     $response['success'] = true;
                     $response['message'] = 'Doctor-ordered lab result deleted successfully.';
                 } else {
                     $response['message'] = 'Failed to delete doctor-ordered lab result.';
                 }
            } else {
                $response['message'] = 'Doctor-ordered lab result not found or unauthorized to delete.';
            }
            // ALTERNATIVE: Uncomment the line below to prevent patient from deleting doctor-ordered:
            // $response['message'] = 'Deletion of doctor-ordered lab results is not allowed via this interface.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error during deletion: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
?>