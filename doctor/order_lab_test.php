<?php
// doctor/order_lab_test.php
$title = "Order Lab Test";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_user_id = $_SESSION['user_id'];
$doctor_id = null;
$message = '';
$message_type = '';
$preselected_patient_id = null;
$preselected_patient_info = null;

// Fetch doctor_id from the doctors table
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$doctor_user_id]);
    $doctor_data = $stmt->fetch();
    if ($doctor_data) {
        $doctor_id = $doctor_data['id'];
    } else {
        // This should ideally not happen if user_type is doctor and doctors table is properly populated
        die("Doctor record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching doctor ID: " . $e->getMessage());
}

// Check for preselected patient ID from GET parameter
if (isset($_GET['patient_id'])) {
    $preselected_patient_id = filter_var($_GET['patient_id'], FILTER_VALIDATE_INT);
    if (!$preselected_patient_id) {
        $message = "Invalid patient ID provided in the URL. Please access patient data first.";
        $message_type = "danger";
        // Redirect to access_patient_data.php if ID is invalid
        header("Location: access_patient_data.php?error_message=" . urlencode($message));
        exit();
    } else {
        // Fetch details of the preselected patient
        try {
            $stmt = $pdo->prepare("SELECT p.id, u.full_name, u.username FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
            $stmt->execute([$preselected_patient_id]);
            $preselected_patient_info = $stmt->fetch();
            if (!$preselected_patient_info) {
                $message = "Patient not found for the provided ID. Please access patient data first.";
                $message_type = "danger";
                header("Location: access_patient_data.php?error_message=" . urlencode($message));
                exit();
            }
        } catch (PDOException $e) {
            $message = "Database error fetching patient details: " . $e->getMessage();
            $message_type = "danger";
            header("Location: access_patient_data.php?error_message=" . urlencode($message)); // Redirect on DB error
            exit();
        }
    }
} else {
    // If no patient_id is in the URL, redirect to access_patient_data.php
    $message = "Please access patient data via QR code or manual token first to order lab tests.";
    $message_type = "danger";
    header("Location: access_patient_data.php?error_message=" . urlencode($message));
    exit();
}

// Fetch all registered patients (only if not preselected, though now always preselected)
// This block is now less critical as patient selection is forced by URL
$patients = [];
if ($preselected_patient_info) {
    $patients[] = $preselected_patient_info; // Only include the preselected patient
} else {
    // This case should ideally not be reached due to the redirect above
    try {
        $stmt = $pdo->query("SELECT p.id, u.full_name, u.username FROM patients p JOIN users u ON p.user_id = u.id ORDER BY u.full_name ASC");
        $patients = $stmt->fetchAll();
    } catch (PDOException $e) {
        $message = "Error fetching patients: " . $e->getMessage();
        $message_type = "danger";
    }
}


// Fetch all available lab tests
$lab_tests = [];
try {
    $stmt = $pdo->query("SELECT id, test_name FROM lab_tests ORDER BY test_name ASC");
    $lab_tests = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching lab tests: " . $e->getMessage();
    $message_type = "danger";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Use the preselected patient ID from GET or the hidden input
    $patient_id_from_post = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
    // Ensure the patient ID submitted is the same as the one preselected (if applicable)
    if ($preselected_patient_id && $patient_id_from_post != $preselected_patient_id) {
        $message = "Security alert: Mismatch in patient ID. Please re-access patient data.";
        $message_type = "danger";
        // Optionally redirect back to access_patient_data
        header("Location: access_patient_data.php?error_message=" . urlencode($message));
        exit();
    }

    $patient_id_to_use = $preselected_patient_id; // Always use the preselected ID

    $selected_tests = $_POST['selected_tests'] ?? []; // Array of test IDs
    $notes = trim($_POST['notes']);

    if (empty($patient_id_to_use) || empty($selected_tests)) {
        $message = "Please select a patient and at least one lab test.";
        $message_type = "danger";
    } else {
        try {
            $pdo->beginTransaction();
            $successful_orders = 0;

            foreach ($selected_tests as $test_id) {
                $test_id = filter_var($test_id, FILTER_VALIDATE_INT);
                if ($test_id) {
                    $stmt = $pdo->prepare("INSERT INTO lab_orders (patient_id, doctor_id, test_id, notes) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$patient_id_to_use, $doctor_id, $test_id, $notes]);
                    $successful_orders++;
                }
            }

            $pdo->commit();
            if ($successful_orders > 0) {
                $message = $successful_orders . " lab order(s) placed successfully for " . htmlspecialchars($preselected_patient_info['full_name']) . "!";
                $message_type = "success";
                // Clear form fields on success, but keep preselected patient
                $_POST = array();
            } else {
                $message = "No valid lab tests were selected for ordering.";
                $message_type = "warning";
                $pdo->rollBack(); // If nothing was ordered, rollback
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error placing lab order: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-9 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Order Lab Test</h2>
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left me-2"></i> Back
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Create New Lab Order</h4>
            </div>
            <div class="card-body">
                <form action="order_lab_test.php?patient_id=<?php echo htmlspecialchars($preselected_patient_id); ?>" method="POST">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Patient</label>
                        <?php if ($preselected_patient_info): ?>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($preselected_patient_info['full_name']); ?> (<?php echo htmlspecialchars($preselected_patient_info['username']); ?>)" readonly>
                            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($preselected_patient_info['id']); ?>">
                        <?php else: ?>
                            <select class="form-select" id="patient_id" name="patient_id" required disabled>
                                <option value="">-- Patient Not Selected --</option>
                            </select>
                            <small class="text-danger">Patient not pre-selected. Please go back and access patient data first.</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Lab Tests (Select one or more)</label>
                        <?php if (!empty($lab_tests)): ?>
                            <div class="row">
                                <?php foreach ($lab_tests as $test): ?>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="test_<?php echo htmlspecialchars($test['id']); ?>"
                                                name="selected_tests[]" value="<?php echo htmlspecialchars($test['id']); ?>"
                                                <?php echo (in_array($test['id'], ($_POST['selected_tests'] ?? []))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="test_<?php echo htmlspecialchars($test['id']); ?>">
                                                <?php echo htmlspecialchars($test['test_name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning" role="alert">
                                No lab tests defined yet. Please ask the administrator to add them.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes for Lab (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-info w-100" <?php echo (empty($preselected_patient_info) || empty($lab_tests)) ? 'disabled' : ''; ?>>Place Lab Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>