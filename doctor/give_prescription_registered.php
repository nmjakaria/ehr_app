<?php
// doctor/give_prescription_registered.php
$title = "Give New Prescription";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_user_id = $_SESSION['user_id'];
$doctor_id = null;
$message = '';
$message_type = '';
$patient_info = null;
$patient_prescriptions = [];

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

// Get patient_id from GET request (passed from access_patient_data.php)
$patient_id = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT);

if (!$patient_id) {
    // If patient_id is not provided or invalid, redirect back or show error
    $message = "No patient selected. Please access patient data via QR/Link first.";
    $message_type = "danger";
    // Optionally redirect to access_patient_data.php
    // header("Location: access_patient_data.php?message=" . urlencode($message) . "&message_type=" . $message_type);
    // exit();
} else {
    // Fetch patient's full name to display
    try {
        $stmt_patient = $pdo->prepare("SELECT u.full_name FROM users u JOIN patients p ON u.id = p.user_id WHERE p.id = ?");
        $stmt_patient->execute([$patient_id]);
        $patient_info = $stmt_patient->fetch();
        if (!$patient_info) {
            $message = "Patient not found.";
            $message_type = "danger";
            $patient_id = null; // Invalidate patient_id if not found
        }
    } catch (PDOException $e) {
        $message = "Error fetching patient info: " . $e->getMessage();
        $message_type = "danger";
        $patient_id = null;
    }

    // Fetch existing prescriptions for this patient
    if ($patient_id) {
        try {
            $stmt = $pdo->prepare("SELECT pr.id, pr.diagnosis, pr.medications, pr.instructions, pr.prescription_date, u.full_name AS doctor_name, d.specialty
                                   FROM prescriptions pr
                                   JOIN doctors d ON pr.doctor_id = d.id
                                   JOIN users u ON d.user_id = u.id
                                   WHERE pr.patient_id = ?
                                   ORDER BY pr.prescription_date DESC");
            $stmt->execute([$patient_id]);
            $patient_prescriptions = $stmt->fetchAll();
        } catch (PDOException $e) {
            $message = "Error fetching previous prescriptions: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Handle form submission for new prescription
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $patient_id) {
    $diagnosis = trim($_POST['diagnosis']);
    $medications_json = $_POST['medications_json']; // This will be a JSON string from JS
    $instructions = trim($_POST['instructions']);

    // Basic validation
    if (empty($diagnosis) || empty($medications_json) || empty($instructions)) {
        $message = "Please fill all required fields (Diagnosis, Medications, Instructions).";
        $message_type = "danger";
    } else {
        // Decode the JSON medications to ensure it's valid
        $medications_array = json_decode($medications_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($medications_array) || count($medications_array) == 0) {
            $message = "Invalid or empty medication data provided.";
            $message_type = "danger";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO prescriptions (doctor_id, patient_id, diagnosis, medications, instructions) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$doctor_id, $patient_id, $diagnosis, $medications_json, $instructions]);

                $message = "Prescription issued successfully for " . htmlspecialchars($patient_info['full_name']) . "!";
                $message_type = "success";

                // Refresh the page to show the new prescription and clear form fields
                // header("Location: give_prescription_registered.php?patient_id=" . $patient_id . "&success=1");
                // exit();

            } catch (PDOException $e) {
                $message = "Error issuing prescription: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}

// Display success message after redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Prescription issued successfully!";
    $message_type = "success";
}

?>

<div class="row justify-content-center mt-4">
    <div class="col-md-9 col-lg-8">
        <button type="button" class="btn btn-secondary" onclick="history.back()">
            <i class="fas fa-arrow-left me-2"></i> Back
        </button>
        <?php if ($patient_id && $patient_info): ?>
            <h2 class="mb-4">Give New Prescription for <span class="text-primary"><?php echo htmlspecialchars($patient_info['full_name']); ?></span></h2>
        <?php else: ?>
            <h2 class="mb-4">Give New Prescription</h2>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($patient_id && $patient_info): // Only show form if patient is valid 
        ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">New Prescription Details</h4>
                </div>
                <div class="card-body">
                    <form action="give_prescription_registered.php?patient_id=<?php echo htmlspecialchars($patient_id); ?>" method="POST">
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">Diagnosis</label>
                            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required><?php echo htmlspecialchars($_POST['diagnosis'] ?? ''); ?></textarea>
                        </div>

                        <hr>
                        <h5>Medications</h5>
                        <div id="medications-container">
                            <?php // Re-populate medication fields if form submission failed
                            if (isset($_POST['medications_json']) && !empty($_POST['medications_json'])):
                                $old_meds = json_decode($_POST['medications_json'], true);
                                if (is_array($old_meds)):
                                    foreach ($old_meds as $idx => $med): ?>
                                        <div class="row mb-2 medication-entry" id="med-row-<?php echo $idx; ?>">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control medication-name" placeholder="Medication Name" value="<?php echo htmlspecialchars($med['name'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" class="form-control medication-dosage" placeholder="Dosage (e.g., 10mg)" value="<?php echo htmlspecialchars($med['dosage'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control medication-frequency" placeholder="Frequency (e.g., BID, TDS)" value="<?php echo htmlspecialchars($med['frequency'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-medication-btn" data-row-id="med-row-<?php echo $idx; ?>"><i class="fas fa-minus-circle"></i></button>
                                            </div>
                                        </div>
                            <?php endforeach;
                                endif;
                            endif; ?>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-medication-btn"><i class="fas fa-plus-circle"></i> Add Medication</button>
                        <input type="hidden" name="medications_json" id="medications_json">

                        <hr>
                        <div class="mb-3 mt-4">
                            <label for="instructions" class="form-label">General Instructions / Notes</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="4" required><?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Issue Prescription</button>
                    </form>
                </div>
            </div>

            <h3 class="mt-5 mb-3">Previous Prescriptions for <?php echo htmlspecialchars($patient_info['full_name']); ?></h3>
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
                    No previous prescriptions found for this patient.
                </div>
            <?php endif; ?>

        <?php else: // If patient_id is not valid 
        ?>
            <div class="alert alert-warning" role="alert">
                Please go to <a href="access_patient_data.php">Access Patient Data</a> to securely select a patient first.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const medicationsContainer = document.getElementById('medications-container');
        const addMedicationBtn = document.getElementById('add-medication-btn');
        const medicationsJsonInput = document.getElementById('medications_json');
        const prescriptionForm = document.querySelector('form');

        // Initialize medicationCounter based on existing elements if re-populating after failed submission
        let medicationCounter = medicationsContainer.children.length;
        if (medicationCounter === 0) { // If no medications were pre-filled, add one empty entry
            addMedicationEntry();
        }

        function addMedicationEntry(name = '', dosage = '', frequency = '') {
            const newRow = document.createElement('div');
            newRow.classList.add('row', 'mb-2', 'medication-entry');
            newRow.id = `med-row-${Date.now()}-${medicationCounter}`; // Unique ID using timestamp + counter
            newRow.innerHTML = `
            <div class="col-md-4">
                <input type="text" class="form-control medication-name" placeholder="Medication Name" value="${name}" required>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control medication-dosage" placeholder="Dosage (e.g., 10mg)" value="${dosage}">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control medication-frequency" placeholder="Frequency (e.g., BID, TDS)" value="${frequency}" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm remove-medication-btn" data-row-id="${newRow.id}"><i class="fas fa-minus-circle"></i></button>
            </div>
        `;
            medicationsContainer.appendChild(newRow);
            medicationCounter++;
        }

        addMedicationBtn.addEventListener('click', function() {
            addMedicationEntry();
        });

        medicationsContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-medication-btn') || event.target.closest('.remove-medication-btn')) {
                const button = event.target.closest('.remove-medication-btn');
                const rowId = button.dataset.rowId;
                document.getElementById(rowId).remove();
            }
        });

        // Before form submission, gather all medication data and put it into the hidden JSON input
        prescriptionForm.addEventListener('submit', function(event) {
            const medications = [];
            let allMedicationsValid = true; // Flag to track overall medication validation

            document.querySelectorAll('.medication-entry').forEach(function(row) {
                const nameInput = row.querySelector('.medication-name');
                const dosageInput = row.querySelector('.medication-dosage');
                const frequencyInput = row.querySelector('.medication-frequency');

                const name = nameInput.value;
                const dosage = dosageInput.value;
                const frequency = frequencyInput.value;

                // Client-side validation for required medication fields
                if (!name.trim()) {
                    nameInput.classList.add('is-invalid');
                    allMedicationsValid = false;
                } else {
                    nameInput.classList.remove('is-invalid');
                }
                if (!frequency.trim()) {
                    frequencyInput.classList.add('is-invalid');
                    allMedicationsValid = false;
                } else {
                    frequencyInput.classList.remove('is-invalid');
                }

                medications.push({
                    name: name,
                    dosage: dosage,
                    frequency: frequency
                });
            });

            // If any medication field is invalid or no medications are added, prevent submission
            if (!allMedicationsValid) {
                alert('Please fill in all required medication fields (Name and Frequency) or correct invalid entries.');
                event.preventDefault();
                return;
            }
            if (medications.length === 0) {
                alert('Please add at least one medication.');
                event.preventDefault();
                return;
            }

            medicationsJsonInput.value = JSON.stringify(medications);
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>