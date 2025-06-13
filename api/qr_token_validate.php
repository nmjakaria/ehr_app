<?php
// api/qr_token_validate.php
// This API endpoint handles validation of QR code tokens and returns patient data.

require_once '../includes/db_connection.php'; // Path to your database connection

header('Content-Type: application/json'); // Indicate that the response will be JSON

// Allow cross-origin requests (CORS) for development/testing.
// In a production environment, restrict this to specific trusted origins.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request (pre-flight for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Invalid Request',
    'data' => null
];

// Expecting the token via GET or POST request
// For demonstration, we'll allow GET for easy testing, but POST is generally preferred for APIs.
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    $response['message'] = 'Token is missing.';
    echo json_encode($response);
    exit();
}

try {
    // 1. Validate the token in the database
    $stmt = $pdo->prepare("SELECT
                                pt.patient_id,
                                u.full_name AS patient_name,
                                p.date_of_birth,
                                p.gender,
                                p.blood_group,
                                pt.expires_at,
                                pt.is_active
                           FROM patient_qr_tokens pt
                           JOIN patients p ON pt.patient_id = p.id
                           JOIN users u ON p.user_id = u.id
                           WHERE pt.token = ? AND pt.is_active = TRUE AND pt.expires_at > NOW()");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        $response['message'] = 'Invalid, expired, or inactive token.';
        echo json_encode($response);
        exit();
    }

    $patient_id = $token_data['patient_id'];

    // 2. Token is valid, fetch additional patient details
    $patient_details = [
        'name' => $token_data['patient_name'],
        'date_of_birth' => $token_data['date_of_birth'],
        'gender' => $token_data['gender'],
        'blood_group' => $token_data['blood_group'],
        'token_valid_until' => $token_data['expires_at'] // Information for the consumer
    ];

    // Fetch Chronic Conditions
    $chronic_conditions = [];
    $stmt_cc = $pdo->prepare("SELECT condition_name, diagnosis_date, notes FROM chronic_conditions WHERE patient_id = ?");
    $stmt_cc->execute([$patient_id]);
    $chronic_conditions = $stmt_cc->fetchAll(PDO::FETCH_ASSOC);
    $patient_details['chronic_conditions'] = $chronic_conditions;

    // Fetch Allergies
    $allergies = [];
    $stmt_a = $pdo->prepare("SELECT allergen_name, reaction, severity, diagnosis_date, notes FROM allergies WHERE patient_id = ?");
    $stmt_a->execute([$patient_id]);
    $allergies = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
    $patient_details['allergies'] = $allergies;

    // Success response
    $response['success'] = true;
    $response['message'] = 'Token validated successfully. Patient data retrieved.';
    $response['data'] = $patient_details;

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    // Log the error in a real application, don't expose full error in production
} catch (Exception $e) {
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit();

?>