<?php
// doctor/view_patient_prescriptions.php
$title = "View Patient Prescriptions";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';
$selected_patient_id = $_GET['patient_id'] ?? null;
$patient_prescriptions = [];
$patient_name = '';

// Fetch list of patients for the dropdown
$patients = [];
try {
    $stmt = $pdo->query("SELECT p.id AS patient_id, u.full_name
                          FROM patients p JOIN users u ON p.user_id = u.id
                          ORDER BY u.full_name");
    $patients = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching patients: " . $e->getMessage();
    $message_type = "danger";
}

// Fetch prescriptions if a patient is selected
if ($selected_patient_id) {
    try {
        // Get patient's name
        $stmt_patient = $pdo->prepare("SELECT u.full_name FROM users u JOIN patients p ON u.id = p.user_id WHERE p.id = ?");
        $stmt_patient->execute([$selected_patient_id]);
        $patient_info = $stmt_patient->fetch();
        if ($patient_info) {
            $patient_name = $patient_info['full_name'];
        } else {
            $message = "Selected patient not found.";
            $message_type = "danger";
            $selected_patient_id = null; // Invalidate selected patient
        }

        // Fetch prescriptions for the selected patient
        if ($selected_patient_id) {
            $stmt = $pdo->prepare("SELECT pr.id, pr.diagnosis, pr.medications, pr.instructions, pr.prescription_date, u.full_name AS doctor_name, d.specialty
                                   FROM prescriptions pr
                                   JOIN doctors d ON pr.doctor_id = d.id
                                   JOIN users u ON d.user_id = u.id
                                   WHERE pr.patient_id = ?
                                   ORDER BY pr.prescription_date DESC");
            $stmt->execute([$selected_patient_id]);
            $patient_prescriptions = $stmt->fetchAll();
        }

    } catch (PDOException $e) {
        $message = "Error fetching prescriptions: " . $e->getMessage();
        $message_type = "danger";
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-10 col-lg-9">
        <h2 class="mb-4">View Patient Prescriptions</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Select Patient</h4>
            </div>
            <div class="card-body">
                <form action="view_patient_prescriptions.php" method="GET">
                    <div class="input-group mb-3">
                        <select class="form-select" id="patient_id" name="patient_id" required>
                            <option value="">-- Select a Patient --</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>"
                                    <?php echo ($selected_patient_id == $patient['patient_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" type="submit">View Prescriptions</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_patient_id && $patient_name): ?>
            <h3 class="mt-5 mb-3">Prescriptions for <?php echo htmlspecialchars($patient_name); ?></h3>
            <?php if (!empty($patient_prescriptions)): ?>
                <?php foreach ($patient_prescriptions as $prescription): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Prescription #<?php echo htmlspecialchars($prescription['id']); ?> 
                                <small class="float-end">Issued: <?php echo htmlspecialchars(date('M d, Y', strtotime($prescription['prescription_date']))); ?> by Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?> (<?php echo htmlspecialchars($prescription['specialty']); ?>)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($prescription['diagnosis'])); ?></p>
                            <h6>Medications:</h6>
                            <ul class="list-group mb-3">
                                <?php
                                $medications = json_decode($prescription['medications'], true);
                                if (is_array($medications)):
                                    foreach ($medications as $med): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($med['name'] ?? ''); ?></strong>
                                                <?php if (!empty($med['dosage'])): ?>
                                                    <small class="text-muted ms-2">(<?php echo htmlspecialchars($med['dosage']); ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($med['frequency'] ?? ''); ?></span>
                                        </li>
                                    <?php endforeach;
                                else: ?>
                                    <li class="list-group-item text-muted">No specific medications listed.</li>
                                <?php endif; ?>
                            </ul>
                            <p><strong>General Instructions:</strong> <?php echo nl2br(htmlspecialchars($prescription['instructions'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    No prescriptions found for <?php echo htmlspecialchars($patient_name); ?>.
                </div>
            <?php endif; ?>
        <?php elseif ($selected_patient_id === null): ?>
            <div class="alert alert-info" role="alert">
                Please select a patient from the dropdown above to view their prescriptions.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>