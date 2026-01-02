<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// doctor/give_prescription_registered.php
$title = "Give New Prescription";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: ../login.php");
    exit();
}

// 1. Get Patient ID and Doctor ID
$patient_id = $_GET['patient_id'] ?? null;
$doctor_user_id = $_SESSION['user_id'];
$doctor_id = null; // Will be fetched from the database

$message = '';
$message_type = '';
$patient_name = 'Unknown Patient';

// 2. Fetch Doctor's Internal ID
try {
    $stmt_doc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt_doc->execute([$doctor_user_id]);
    $doctor_data = $stmt_doc->fetch();
    if ($doctor_data) {
        $doctor_id = $doctor_data['id'];
    } else {
        $message = "Error: Doctor profile not found.";
        $message_type = "danger";
    }
} catch (PDOException $e) {
    $message = "Database error fetching doctor ID: " . $e->getMessage();
    $message_type = "danger";
}


// 3. Fetch Patient's Name for Display
if ($patient_id && $doctor_id) {
    try {
        $stmt_patient = $pdo->prepare("SELECT u.full_name FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmt_patient->execute([$patient_id]);
        $patient_data = $stmt_patient->fetch();

        if ($patient_data) {
            $patient_name = $patient_data['full_name'];
        } else {
            $message = "Invalid patient ID or patient profile not found.";
            $message_type = "danger";
            $patient_id = null; // Invalidate the ID to prevent submission
        }
    } catch (PDOException $e) {
        $message = "Database error fetching patient name: " . $e->getMessage();
        $message_type = "danger";
        $patient_id = null;
    }
} else {
    // If patient_id is missing from URL, redirect back or show error
    if (empty($_GET['patient_id'])) {
        $message = "Patient ID is missing. Cannot issue a prescription.";
        $message_type = "danger";
    }
    // No need to show error for missing doctor_id if already handled above
}


// 4. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $patient_id && $doctor_id) {
    $diagnosis = trim($_POST['diagnosis']);
    $instructions = trim($_POST['instructions']);
    $medication_names = $_POST['medication_name'] ?? [];
    $medication_dosages = $_POST['medication_dosage'] ?? [];
    $medication_frequencies = $_POST['medication_frequency'] ?? [];

    $medications_array = [];
    if (!empty($medication_names)) {
        foreach ($medication_names as $key => $name) {
            if (!empty(trim($name))) {
                $medications_array[] = [
                    'name' => trim($name),
                    'dosage' => trim($medication_dosages[$key] ?? ''),
                    'frequency' => trim($medication_frequencies[$key] ?? ''),
                ];
            }
        }
    }

    $medications_json = json_encode($medications_array, JSON_UNESCAPED_UNICODE);

    if (empty($diagnosis) && empty($medications_array) && empty($instructions)) {
        $message = "Prescription cannot be empty. Please enter a diagnosis, medications, or instructions.";
        $message_type = "warning";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_id, diagnosis, medications, instructions, prescription_date)
                                    VALUES (?, ?, ?, ?, ?, NOW())");

            $stmt->execute([
                $patient_id,
                $doctor_id,
                $diagnosis,
                $medications_json,
                $instructions
            ]);

            $message = "Prescription for " . htmlspecialchars($patient_name) . " saved successfully!";
            $message_type = "success";

            // Clear the form data
            $diagnosis = '';
            $instructions = '';
            $medications_array = [];

        } catch (PDOException $e) {
            $message = "Error saving prescription: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-10 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">New Prescription for <?php echo htmlspecialchars($patient_name); ?> (ID: <?php echo htmlspecialchars($patient_id); ?>)</h2>
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($patient_id && $doctor_id): ?>
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Prescription Details</h4>
                </div>
                <div class="card-body">
                    <form action="give_prescription_registered.php?patient_id=<?php echo htmlspecialchars($patient_id); ?>" method="POST">
                        
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">Diagnosis/Chief Complaint</label>
                            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required><?php echo htmlspecialchars($diagnosis ?? ''); ?></textarea>
                            <div class="form-text">Enter the patient's diagnosis or the reason for the visit.</div>
                        </div>

                        <hr>

                        <h5 class="mt-4 mb-3">Medications</h5>
                        <div id="medication-list">
                            <?php if (empty($medications_array)): ?>
                                <div class="row mb-2 medication-entry">
                                    <div class="col-5">
                                        <input type="text" class="form-control" name="medication_name[]" placeholder="Medication Name (e.g., Paracetamol)" required>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" name="medication_dosage[]" placeholder="Dosage (e.g., 500mg)">
                                    </div>
                                    <div class="col-3 d-flex">
                                        <input type="text" class="form-control me-2" name="medication_frequency[]" placeholder="Frequency (e.g., 1+0+1)">
                                        <button type="button" class="btn btn-danger btn-sm remove-med-btn" style="width: 40px; flex-shrink: 0;"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            <?php else: 
                                // Repopulate form after failed submission
                                foreach ($medications_array as $med_data): ?>
                                <div class="row mb-2 medication-entry">
                                    <div class="col-5">
                                        <input type="text" class="form-control" name="medication_name[]" placeholder="Medication Name (e.g., Paracetamol)" required value="<?php echo htmlspecialchars($med_data['name']); ?>">
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" name="medication_dosage[]" placeholder="Dosage (e.g., 500mg)" value="<?php echo htmlspecialchars($med_data['dosage']); ?>">
                                    </div>
                                    <div class="col-3 d-flex">
                                        <input type="text" class="form-control me-2" name="medication_frequency[]" placeholder="Frequency (e.g., 1+0+1)" value="<?php echo htmlspecialchars($med_data['frequency']); ?>">
                                        <button type="button" class="btn btn-danger btn-sm remove-med-btn" style="width: 40px; flex-shrink: 0;"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            <?php endforeach;
                            endif; ?>

                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm mt-2" id="add-medication-btn"><i class="fas fa-plus-circle me-2"></i>Add Another Medication</button>

                        <hr class="mt-4">

                        <div class="mb-3">
                            <label for="instructions" class="form-label">General Instructions/Notes</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="4"><?php echo htmlspecialchars($instructions ?? ''); ?></textarea>
                            <div class="form-text">Lifestyle advice, follow-up instructions, or special notes for the patient/pharmacist.</div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg mt-4 w-100"><i class="fas fa-save me-2"></i>Save Prescription</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                Cannot load prescription form. Please ensure a valid patient ID is provided in the URL and the doctor is logged in.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const medicationList = document.getElementById('medication-list');
        const addMedicationBtn = document.getElementById('add-medication-btn');

        // Function to create a new medication input row
        function createMedicationRow(name = '', dosage = '', frequency = '') {
            const row = document.createElement('div');
            row.className = 'row mb-2 medication-entry';
            row.innerHTML = `
                <div class="col-5">
                    <input type="text" class="form-control" name="medication_name[]" placeholder="Medication Name (e.g., Paracetamol)" value="${name}" required>
                </div>
                <div class="col-4">
                    <input type="text" class="form-control" name="medication_dosage[]" placeholder="Dosage (e.g., 500mg)" value="${dosage}">
                </div>
                <div class="col-3 d-flex">
                    <input type="text" class="form-control me-2" name="medication_frequency[]" placeholder="Frequency (e.g., 1+0+1)" value="${frequency}">
                    <button type="button" class="btn btn-danger btn-sm remove-med-btn" style="width: 40px; flex-shrink: 0;"><i class="fas fa-times"></i></button>
                </div>
            `;
            // Add event listener to the remove button
            row.querySelector('.remove-med-btn').addEventListener('click', function() {
                row.remove();
            });
            return row;
        }

        // Event listener for "Add Another Medication" button
        addMedicationBtn.addEventListener('click', function() {
            medicationList.appendChild(createMedicationRow());
        });

        // Attach listeners to existing remove buttons (for repopulated fields)
        medicationList.querySelectorAll('.remove-med-btn').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.medication-entry').remove();
            });
        });
        
        // Ensure there is always at least one row, or the user is prompted to add one
        if (medicationList.children.length === 0) {
            medicationList.appendChild(createMedicationRow());
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>