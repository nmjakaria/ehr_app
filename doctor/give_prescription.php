<?php
// doctor/give_prescription.php
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

// Handle form submission for new prescription
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT);
    $diagnosis = trim($_POST['diagnosis']);
    $medications_json = $_POST['medications_json']; // This will be a JSON string from JS
    $instructions = trim($_POST['instructions']);

    // Basic validation
    if (!$patient_id || empty($diagnosis) || empty($medications_json) || empty($instructions)) {
        $message = "Please fill all required fields.";
        $message_type = "danger";
    } else {
        // Decode the JSON medications to ensure it's valid
        $medications_array = json_decode($medications_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($medications_array)) {
            $message = "Invalid medication data provided.";
            $message_type = "danger";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO prescriptions (doctor_id, patient_id, diagnosis, medications, instructions) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$doctor_id, $patient_id, $diagnosis, $medications_json, $instructions]);

                $message = "Prescription issued successfully!";
                $message_type = "success";

                // Clear form fields after successful submission (optional)
                $_POST = array(); // Clear POST data
            } catch (PDOException $e) {
                $message = "Error issuing prescription: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-9 col-lg-8">
        <h2 class="mb-4">Give New Prescription</h2>
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left me-2"></i> Back
            </button>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Prescription Details</h4>
            </div>
            <div class="card-body">
                <form action="give_prescription.php" method="POST">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Select Patient</label>
                        <select class="form-select" id="patient_id" name="patient_id" required>
                            <option value="">-- Select a Patient --</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>"
                                    <?php echo (($_POST['patient_id'] ?? '') == $patient['patient_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required><?php echo htmlspecialchars($_POST['diagnosis'] ?? ''); ?></textarea>
                    </div>

                    <hr>
                    <h5>Medications</h5>
                    <div id="medications-container">
                        <?php if (isset($_POST['medications_json']) && !empty($_POST['medications_json'])):
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const medicationsContainer = document.getElementById('medications-container');
    const addMedicationBtn = document.getElementById('add-medication-btn');
    const medicationsJsonInput = document.getElementById('medications_json');
    const prescriptionForm = document.querySelector('form');

    let medicationCounter = <?php echo isset($old_meds) && is_array($old_meds) ? count($old_meds) : 0; ?>;

    function addMedicationEntry(name = '', dosage = '', frequency = '') {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'mb-2', 'medication-entry');
        newRow.id = `med-row-${medicationCounter}`;
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
                <button type="button" class="btn btn-danger btn-sm remove-medication-btn" data-row-id="med-row-${medicationCounter}"><i class="fas fa-minus-circle"></i></button>
            </div>
        `;
        medicationsContainer.appendChild(newRow);
        medicationCounter++;
    }

    // Add initial empty medication entry if none are pre-filled (e.g., on first load)
    if (medicationCounter === 0) {
        addMedicationEntry();
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
        document.querySelectorAll('.medication-entry').forEach(function(row) {
            const name = row.querySelector('.medication-name').value;
            const dosage = row.querySelector('.medication-dosage').value;
            const frequency = row.querySelector('.medication-frequency').value;

            // Basic client-side validation for required medication fields
            if (!name.trim() || !frequency.trim()) {
                alert('Please fill in all required medication fields (Name and Frequency).');
                event.preventDefault(); // Stop form submission
                return; // Stop processing this medication entry
            }

            medications.push({
                name: name,
                dosage: dosage,
                frequency: frequency
            });
        });

        if (medications.length === 0) {
            alert('Please add at least one medication.');
            event.preventDefault(); // Stop form submission
            return;
        }

        medicationsJsonInput.value = JSON.stringify(medications);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>