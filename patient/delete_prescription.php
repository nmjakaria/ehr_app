<?php
// patient/delete_prescription.php
require_once '../includes/db_connection.php';
// session_start(); // Assuming session is started by db_connection or header

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

    if (!$id || !in_array($type, ['doctor_issued', 'patient_uploaded'])) {
        $response['message'] = 'Invalid ID or prescription type.';
        echo json_encode($response);
        exit();
    }

    try {
        if ($type == 'patient_uploaded') {
            // Fetch image path before deleting the record
            $stmt = $pdo->prepare("SELECT image_path FROM patient_uploaded_prescriptions WHERE id = ? AND patient_id = ?");
            $stmt->execute([$id, $patient_id]);
            $uploaded_pres = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($uploaded_pres) {
                // Delete the record from the database
                $stmt = $pdo->prepare("DELETE FROM patient_uploaded_prescriptions WHERE id = ? AND patient_id = ?");
                if ($stmt->execute([$id, $patient_id])) {
                    // If there was an image, attempt to delete the file
                    if (!empty($uploaded_pres['image_path'])) {
                        $file_path = '../' . $uploaded_pres['image_path']; // Adjust path
                        if (file_exists($file_path) && is_file($file_path)) {
                            unlink($file_path); // Delete the file
                        }
                    }
                    $response['success'] = true;
                    $response['message'] = 'Patient-uploaded prescription deleted successfully.';
                } else {
                    $response['message'] = 'Failed to delete patient-uploaded prescription from database.';
                }
            } else {
                $response['message'] = 'Patient-uploaded prescription not found or unauthorized.';
            }
        } elseif ($type == 'doctor_issued') {
            // For doctor-issued prescriptions, we usually don't allow direct patient deletion.
            // You might implement a "request deletion" feature, or only allow doctors/admins to delete these.
            // For now, we'll mark it as not supported or require specific patient ownership check.
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE id = ? AND patient_id = ?");
            $stmt->execute([$id, $patient_id]);
            if ($stmt->fetchColumn() > 0) {
                 // Example: If you want to allow direct deletion by patient
                 $stmt_delete = $pdo->prepare("DELETE FROM prescriptions WHERE id = ? AND patient_id = ?");
                 if ($stmt_delete->execute([$id, $patient_id])) {
                     $response['success'] = true;
                     $response['message'] = 'Doctor-issued prescription deleted successfully.';
                 } else {
                     $response['message'] = 'Failed to delete doctor-issued prescription.';
                 }
            } else {
                $response['message'] = 'Doctor-issued prescription not found or unauthorized to delete.';
            }
            // ALTERNATIVE: Prevent patient from deleting doctor-issued:
            // $response['message'] = 'Deletion of doctor-issued prescriptions is not allowed via this interface.';
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