<?php
// doctor/patient_dashboard.php
$title = "Patient Dashboard";
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

// Fetch doctor_id from the doctors table
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$doctor_user_id]);
    $doctor_data = $stmt->fetch();
    if ($doctor_data) {
        $doctor_id = $doctor_data['id'];
    } else {
        die("Doctor record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching doctor ID: " . $e->getMessage());
}

// Get patient_id from GET request
$patient_id = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT);

// Redirect if no patient_id is provided
if (!$patient_id) {
    header("Location: my_patients.php?message=" . urlencode("No patient selected for dashboard.") . "&message_type=danger");
    exit();
}

// --- Fetch Patient's Basic Information ---
$patient_info = null;
try {
    $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.mobile, p.date_of_birth, p.gender, p.blood_group, u.username
                          FROM patients p
                          JOIN users u ON p.user_id = u.id
                          WHERE p.id = ?");
    $stmt->execute([$patient_id]);
    $patient_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient_info) {
        header("Location: my_patients.php?message=" . urlencode("Patient not found or not associated with you.") . "&message_type=danger");
        exit();
    }
} catch (PDOException $e) {
    $message = "Error fetching patient info: " . $e->getMessage();
    $message_type = "danger";
}


// --- Fetch Patient's Appointments with THIS Doctor ---
$patient_appointments = [];
try {
    $stmt = $pdo->prepare("SELECT a.appointment_date_time, a.reason, a.status, du.full_name AS doctor_name_app, d.specialty
                          FROM appointments a
                          JOIN doctors d ON a.doctor_id = d.id
                          JOIN users du ON d.user_id = du.id -- ADD THIS LINE: Join users table for doctor's name
                          WHERE a.patient_id = ? AND a.doctor_id = ?
                          ORDER BY a.appointment_date_time DESC");
    $stmt->execute([$patient_id, $doctor_id]); // Filter by current doctor's ID
    $patient_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= "Error fetching appointments: " . $e->getMessage();
    $message_type = "danger";
}

// --- Fetch Patient's Appointments with THIS Doctor ---
$patient_appointments = [];
try {
    $stmt = $pdo->prepare("SELECT a.appointment_date_time, a.reason, a.status, du.full_name AS doctor_name_app, d.specialty
                          FROM appointments a
                          JOIN doctors d ON a.doctor_id = d.id
                          JOIN users du ON d.user_id = du.id -- ADD THIS LINE: Join users table for doctor's name
                          WHERE a.patient_id = ? AND a.doctor_id = ?
                          ORDER BY a.appointment_date_time DESC");
    $stmt->execute([$patient_id, $doctor_id]); // Filter by current doctor's ID
    $patient_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= "Error fetching appointments: " . $e->getMessage();
    $message_type = "danger";
}

// --- Fetch Patient's Lab Orders and Results (Ordered by THIS Doctor) ---
// Note: You could modify this to show ALL lab results for the patient,
// regardless of which doctor ordered them, for a full patient view.
// For now, we'll keep it to those ordered by the current doctor.
$patient_lab_results = [];
try {
    $stmt = $pdo->prepare("SELECT
                                lo.id AS order_id,
                                lt.test_name,
                                lo.order_date,
                                lo.status,
                                lr.result_data,
                                lr.result_date,
                                lr.notes AS result_notes
                           FROM
                                lab_orders lo
                           JOIN
                                lab_tests lt ON lo.test_id = lt.id
                           LEFT JOIN
                                lab_results lr ON lo.id = lr.order_id
                           WHERE
                                lo.patient_id = ? AND lo.doctor_id = ?
                           ORDER BY
                                lo.order_date DESC");
    $stmt->execute([$patient_id, $doctor_id]); // Filter by current doctor's ID
    $patient_lab_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= "Error fetching lab results: " . $e->getMessage();
    $message_type = "danger";
}

// --- Fetch Patient's Chronic Conditions ---
$patient_chronic_conditions = [];
try {
    $stmt = $pdo->prepare("SELECT cc.condition_name, cc.diagnosis_date, cc.notes, du.full_name AS doctor_name_cc
                           FROM chronic_conditions cc
                           JOIN doctors d ON cc.doctor_id = d.id
                           JOIN users du ON d.user_id = du.id
                           WHERE cc.patient_id = ?
                           ORDER BY cc.diagnosis_date DESC, cc.created_at DESC");
    $stmt->execute([$patient_id]);
    $patient_chronic_conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= "Error fetching chronic conditions: " . $e->getMessage();
    $message_type = "danger";
}

// --- Fetch Patient's Allergies ---
$patient_allergies = [];
try {
    $stmt = $pdo->prepare("SELECT a.allergen_name, a.reaction, a.severity, a.diagnosis_date, a.notes, du.full_name AS doctor_name_allergy
                           FROM allergies a
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users du ON d.user_id = du.id
                           WHERE a.patient_id = ?
                           ORDER BY a.severity DESC, a.allergen_name ASC"); // Order by severity to show critical ones first
    $stmt->execute([$patient_id]);
    $patient_allergies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= "Error fetching allergies: " . $e->getMessage();
    $message_type = "danger";
}
?>
<div class="contianer mt-4">
<div class="row justify-content-center">
    <div class="col-md-11 col-lg-10">
        <h2 class="mb-4">Patient Dashboard: <?php echo htmlspecialchars($patient_info['full_name'] ?? 'N/A'); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <a href="my_patients.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Back to My Patients</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Patient Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($patient_info['full_name'] ?? 'N/A'); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($patient_info['username'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info['email'] ?? 'N/A'); ?></p>
                        <p><strong>Mobile:</strong> <?php echo htmlspecialchars($patient_info['mobile'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($patient_info['date_of_birth'] ?? 'N/A'); ?></p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient_info['gender'] ?? 'N/A'); ?></p>
                        <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient_info['blood_group'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Appointments</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($patient_appointments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patient_appointments as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($app['appointment_date_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($app['reason']); ?></td>
                                        <td><span class="badge <?php echo ($app['status'] == 'approved' ? 'bg-success' : ($app['status'] == 'pending' ? 'bg-warning text-dark' : 'bg-danger')); ?>"><?php echo ucfirst(htmlspecialchars($app['status'])); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No appointments recorded with you for this patient.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Prescriptions</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($patient_prescriptions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Instructions</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patient_prescriptions as $pres): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($pres['prescription_date']))); ?></td>
                                        <td><?php echo htmlspecialchars($pres['medication']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['dosage']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['instructions']); ?></td>
                                        <td><?php echo htmlspecialchars($pres['notes']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No prescriptions issued by you for this patient.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Lab Results</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($patient_lab_results)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Test Name</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patient_lab_results as $lab): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lab['test_name']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($lab['order_date']))); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($lab['status']) {
                                                case 'pending':
                                                    $status_class = 'badge bg-secondary';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'badge bg-success';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'badge bg-danger';
                                                    break;
                                                default:
                                                    $status_class = 'badge bg-info';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst(htmlspecialchars($lab['status'])); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($lab['status'] == 'completed' && !empty($lab['result_data'])): ?>
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#labResultModal_<?php echo htmlspecialchars($lab['order_id']); ?>">
                                                    View Results
                                                </button>

                                                <div class="modal fade" id="labResultModal_<?php echo htmlspecialchars($lab['order_id']); ?>" tabindex="-1" aria-labelledby="labResultModalLabel_<?php echo htmlspecialchars($lab['order_id']); ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title" id="labResultModalLabel_<?php echo htmlspecialchars($lab['order_id']); ?>">Lab Results for <?php echo htmlspecialchars($lab['test_name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>Test:</strong> <?php echo htmlspecialchars($lab['test_name']); ?></p>
                                                                <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($lab['order_date']))); ?></p>
                                                                <p><strong>Result Date:</strong> <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($lab['result_date']))); ?></p>
                                                                <hr>
                                                                <h5>Results:</h5>
                                                                <pre class="bg-light p-3 rounded"><code><?php
                                                                                                        $decoded_results = json_decode($lab['result_data'], true);
                                                                                                        if (json_last_error() === JSON_ERROR_NONE) {
                                                                                                            echo htmlspecialchars(json_encode($decoded_results, JSON_PRETTY_PRINT));
                                                                                                        } else {
                                                                                                            echo htmlspecialchars($lab['result_data']); // Fallback
                                                                                                        }
                                                                                                        ?></code></pre>
                                                                <?php if (!empty($lab['result_notes'])): ?>
                                                                    <h5 class="mt-3">Notes from Lab:</h5>
                                                                    <p class="bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($lab['result_notes'])); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif ($lab['status'] == 'pending'): ?>
                                                <span class="text-muted">Results Pending</span>
                                            <?php else: ?>
                                                <span class="text-danger">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No lab orders by you found for this patient.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Chronic Conditions</h5>
                <a href="add_chronic_condition.php?patient_id=<?php echo htmlspecialchars($patient_id); ?>" class="btn btn-sm btn-light">
                    <i class="fas fa-plus-circle me-1"></i> Add Condition
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($patient_chronic_conditions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Condition</th>
                                    <th>Diagnosis Date</th>
                                    <th>Notes</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patient_chronic_conditions as $condition): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($condition['condition_name']); ?></td>
                                        <td><?php echo htmlspecialchars($condition['diagnosis_date'] ? date('M d, Y', strtotime($condition['diagnosis_date'])) : 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($condition['notes'] ?: 'N/A'); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($condition['doctor_name_cc']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No chronic conditions recorded for this patient.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Allergies</h5>
                <a href="add_allergy.php?patient_id=<?php echo htmlspecialchars($patient_id); ?>" class="btn btn-sm btn-light">
                    <i class="fas fa-plus-circle me-1"></i> Add Allergy
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($patient_allergies)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Allergen</th>
                                    <th>Reaction</th>
                                    <th>Severity</th>
                                    <th>Diagnosis Date</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patient_allergies as $allergy): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($allergy['allergen_name']); ?></td>
                                        <td><?php echo htmlspecialchars($allergy['reaction']); ?></td>
                                        <td>
                                            <?php
                                            $severity_class = '';
                                            switch ($allergy['severity']) {
                                                case 'mild':
                                                    $severity_class = 'badge bg-success';
                                                    break;
                                                case 'moderate':
                                                    $severity_class = 'badge bg-info text-dark';
                                                    break;
                                                case 'severe':
                                                    $severity_class = 'badge bg-warning text-dark';
                                                    break;
                                                case 'life-threatening':
                                                    $severity_class = 'badge bg-danger';
                                                    break;
                                                default:
                                                    $severity_class = 'badge bg-secondary';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $severity_class; ?>"><?php echo ucfirst(htmlspecialchars($allergy['severity'])); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($allergy['diagnosis_date'] ? date('M d, Y', strtotime($allergy['diagnosis_date'])) : 'N/A'); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($allergy['doctor_name_allergy']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No allergies recorded for this patient.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>


<?php require_once '../includes/footer.php'; ?>