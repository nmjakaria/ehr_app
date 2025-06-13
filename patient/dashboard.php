<?php
// patient/dashboard.php
$title = "Patient Dashboard";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: ../login.php"); // Corrected path to login.php
    exit();
}

$patient_user_id = $_SESSION['user_id'];
$patient_id = null; // Initialize patient_id
$message = ''; // Initialize message for alerts
$message_type = ''; // Initialize message_type for alerts

// Fetch patient_id and other patient information from the patients table
try {
    $stmt = $pdo->prepare("SELECT id, date_of_birth, gender, blood_group FROM patients WHERE user_id = ?");
    $stmt->execute([$patient_user_id]); // Use patient_user_id (which is $_SESSION['user_id'])
    $patient_data = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

    if ($patient_data) {
        $patient_id = $patient_data['id']; // THIS IS THE CRUCIAL LINE: Set patient_id
        $patient_info = $patient_data; // Store other patient info for display
    } else {
        // This case should ideally not happen if patient is correctly registered
        // and has a corresponding entry in 'patients' table.
        die("Patient record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching patient ID and info: " . $e->getMessage());
}

// --- NOW, all your other SQL queries can safely use $patient_id ---

// --- Fetch Patient's Chronic Conditions ---
$patient_chronic_conditions = [];
try {
    $stmt = $pdo->prepare("SELECT cc.condition_name, cc.diagnosis_date, cc.notes, du.full_name AS doctor_name_cc
                           FROM chronic_conditions cc
                           JOIN doctors d ON cc.doctor_id = d.id
                           JOIN users du ON d.user_id = du.id
                           WHERE cc.patient_id = ?
                           ORDER BY cc.diagnosis_date DESC, cc.created_at DESC");
    $stmt->execute([$patient_id]); // Now $patient_id is defined
    $patient_chronic_conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Correct way to append message if there's an existing one or create new
    $message = ($message ? $message . "<br>" : "") . "Error fetching chronic conditions: " . $e->getMessage();
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
                           ORDER BY a.severity DESC, a.allergen_name ASC");
    $stmt->execute([$patient_id]); // Now $patient_id is defined
    $patient_allergies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = ($message ? $message . "<br>" : "") . "Error fetching allergies: " . $e->getMessage();
    $message_type = "danger";
}

// --- Placeholder for other patient-specific fetches (e.g., appointments, prescriptions, lab results) ---
// You will integrate your existing code for these sections here,
// making sure they also use $patient_id where appropriate.

// Example of how you might fetch appointments (add your actual code here)
/*
$patient_appointments = [];
try {
    $stmt = $pdo->prepare("SELECT a.appointment_date_time, a.reason, a.status, d.full_name AS doctor_name_app, d.specialty
                          FROM appointments a
                          JOIN doctors doc ON a.doctor_id = doc.id
                          JOIN users du ON doc.user_id = du.id
                          WHERE a.patient_id = ?
                          ORDER BY a.appointment_date_time DESC");
    $stmt->execute([$patient_id]);
    $patient_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = ($message ? $message . "<br>" : "") . "Error fetching appointments: " . $e->getMessage();
    $message_type = "danger";
}
*/

// Similarly, add your existing code for fetching prescriptions and lab results here.

?>

<div class="container mt-4">
    <h1 id="welcomeMessage">Welcome, Patient <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-info-circle"></i> Your Information
                </div>
                <div class="card-body">
                    <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($patient_info['date_of_birth'] ?? 'N/A'); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient_info['gender'] ?? 'N/A'); ?></p>
                    <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient_info['blood_group'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-file-medical-alt"></i> Medical Records Summary
                </div>
                <div class="card-body">
                    <p>This section can provide a quick overview or links to more detailed records.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-file-medical-alt"></i> Generate QR Code
                </div>
                <div class="card-body">
                    <p>This section can provide to generate a QR code for scan patient Pracricption and Madical reports.</p>
                    <a href="generate_access_token.php" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fas fa-qrcode me-1"></i> Generate QR Code for Access
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 d-md-flex justify-content-center">
                        <a href="book_appointment.php" class="btn btn-primary btn-lg flex-fill me-md-2 mb-2 mb-md-0">
                            <i class="fas fa-calendar-plus me-2"></i> Book New Appointment
                        </a>
                        <a href="my_appointments.php" class="btn btn-info btn-lg flex-fill me-md-2 mb-2 mb-md-0">
                            <i class="fas fa-calendar-alt me-2"></i> My Appointments
                        </a>
                        <a href="my_prescriptions.php" class="btn btn-success btn-lg flex-fill me-md-2 mb-2 mb-md-0">
                            <i class="fas fa-prescription-bottle-alt me-2"></i> My Prescriptions
                        </a>
                        <a href="view_lab_results.php" class="btn btn-warning btn-lg flex-fill text-dark">
                            <i class="fas fa-microscope me-2"></i> My Lab Results
                        </a>
                        <a href="upload_prescription.php" class="btn btn-dark btn-lg flex-fill">
                            <i class="fas fa-upload me-2"></i> Upload Prescription
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">My Chronic Conditions</h5>
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
                        <p class="text-muted">No chronic conditions recorded for you.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">My Allergies</h5>
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
                        <p class="text-muted">No allergies recorded for you.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once '../includes/footer.php'; ?>