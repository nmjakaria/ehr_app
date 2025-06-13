<?php
// doctor/give_prescription_walkin.php
$title = "New Walk-in Prescription";
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

// Store the newly issued prescription details to display for printing
$new_prescription_details = null;

// Handle form submission for new walk-in prescription
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $walkin_patient_name = trim($_POST['walkin_patient_name']);
    $walkin_patient_gender = $_POST['walkin_patient_gender'];
    $walkin_patient_dob = $_POST['walkin_patient_dob'];
    $diagnosis = trim($_POST['diagnosis']);
    $medications_json = $_POST['medications_json'];
    $instructions = trim($_POST['instructions']);

    // Basic validation
    if (empty($walkin_patient_name) || empty($walkin_patient_gender) || empty($walkin_patient_dob) ||
        empty($diagnosis) || empty($medications_json) || empty($instructions)) {
        $message = "Please fill all required patient and prescription fields.";
        $message_type = "danger";
    } elseif (!DateTime::createFromFormat('Y-m-d', $walkin_patient_dob)) {
        $message = "Invalid date of birth format.";
        $message_type = "danger";
    } else {
        // Decode the JSON medications
        $medications_array = json_decode($medications_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($medications_array) || count($medications_array) == 0) {
            $message = "Invalid or empty medication data provided.";
            $message_type = "danger";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO prescriptions (doctor_id, diagnosis, medications, instructions,
                                                                  walkin_patient_name, walkin_patient_gender, walkin_patient_dob)
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$doctor_id, $diagnosis, $medications_json, $instructions,
                                $walkin_patient_name, $walkin_patient_gender, $walkin_patient_dob]);

                $last_prescription_id = $pdo->lastInsertId();

                // Fetch newly created prescription to display for printing
                $stmt_fetch = $pdo->prepare("SELECT
                                                p.id, p.diagnosis, p.medications, p.instructions, p.prescription_date,
                                                p.walkin_patient_name, p.walkin_patient_gender, p.walkin_patient_dob,
                                                u.full_name AS doctor_name, d.specialty
                                            FROM prescriptions p
                                            JOIN doctors d ON p.doctor_id = d.id
                                            JOIN users u ON d.user_id = u.id
                                            WHERE p.id = ?");
                $stmt_fetch->execute([$last_prescription_id]);
                $new_prescription_details = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

                $message = "Walk-in prescription issued successfully for " . htmlspecialchars($walkin_patient_name) . "!";
                $message_type = "success";

                // Clear form fields after successful submission (optional)
                // $_POST = array(); // Uncomment if you want to clear form fields after print
            } catch (PDOException $e) {
                $message = "Error issuing walk-in prescription: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}
?>

<style>
@media print {
    /* Hide all elements that should NOT be printed */
    .no-print,
    .navbar,          /* Hide your navigation bar */
    .sidebar,         /* Hide any sidebar */
    .footer,          /* Hide your main footer */
    .breadcrumb,      /* If you have breadcrumbs */
    .alert,           /* Hide general alert messages */
    h2.mb-4,          /* Hide the specific h2 for "New Walk-in Prescription" at the top */
    .btn,             /* Hide all buttons (adjust if you need some for print) */
    form,
    .container              /* Hide all forms */
    {
        display: none !important;
    }

    /* Show only the content explicitly marked for printing */
    .print-area {
        display: block !important; /* Ensure the print area is visible */
        width: 100% !important; /* Ensure it takes full width of the print page */
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important; /* Remove shadows */
        border: none !important; /* Remove borders */
        background-color: #fff !important; /* Ensure white background */
        position: absolute; /* Position correctly for print */
        top: 0;
        left: 0;
        right: 0;
        max-width: none !important; /* Remove any max-width constraints */
    }

    /* Adjust page margins for A4 paper size */
    @page {
        size: A4 portrait; /* Set paper size to A4 portrait */
        margin: 1cm; /* Set margins to 1cm on all sides */
    }

    /* General styling for printed content */
    body {
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact; /* For WebKit browsers to print backgrounds/colors */
        print-color-adjust: exact;         /* Standard property */
    }

    /* Ensure text is clear and readable on print */
    h1, h2, h3, h4, h5, h6 {
        font-size: 1.2em; /* Adjust font size for headings on print */
        color: #000 !important; /* Ensure black text for print */
    }
    p, li, small {
        font-size: 0.9em; /* Adjust font size for body text on print */
        color: #000 !important;
    }

    /* Specific adjustments for your prescription content */
    #prescription-content {
        padding: 20px; /* Add some padding within the printed card body */
        border: 1px solid #ccc; /* Add a subtle border around the content */
        margin-top: 20px;
    }

    /* Ensure list items look clean */
    .list-group-item {
        border: none !important;
        padding: 0.4rem 0 !important;
    }
    .list-group {
        border: 1px solid #eee; /* Add a light border around the whole medication list */
        padding: 5px;
    }
}
</style>
<div class="row justify-content-center mt-5">
    <div class="col-md-9 col-lg-8">
        <h2 class="mb-4">Give New Walk-in Prescription</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($new_prescription_details && $message_type == 'success'): ?>
            <div class="card shadow-sm mb-4 print-area">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Prescription Issued!</h4>
                </div>
                <div class="card-body" id="prescription-content">
                    <div class="text-center mb-4">
                        <img src="../assets/img/logo.png" alt="EHR Logo" style="max-width: 100px;">
                        <h4 class="mt-2 mb-0">Electronic Health Records System</h4>
                        <p class="text-muted">Prescription Details</p>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($new_prescription_details['walkin_patient_name']); ?></p>
                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($new_prescription_details['walkin_patient_gender']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($new_prescription_details['walkin_patient_dob']); ?></p>
                            <p><strong>Prescription Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($new_prescription_details['prescription_date']))); ?></p>
                        </div>
                    </div>

                    <h5 class="mb-3">Diagnosis:</h5>
                    <p class="border p-2 rounded bg-light"><?php echo nl2br(htmlspecialchars($new_prescription_details['diagnosis'])); ?></p>

                    <h5 class="mb-3">Medications:</h5>
                    <ul class="list-group mb-3">
                        <?php
                        $medications = json_decode($new_prescription_details['medications'], true);
                        if (is_array($medications) && count($medications) > 0):
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

                    <h5 class="mb-3">General Instructions:</h5>
                    <p class="border p-2 rounded bg-light"><?php echo nl2br(htmlspecialchars($new_prescription_details['instructions'])); ?></p>

                    <div class="text-end mt-5">
                        <p class="mb-1">Dr. <?php echo htmlspecialchars($new_prescription_details['doctor_name']); ?></p>
                        <p class="text-muted"><?php echo htmlspecialchars($new_prescription_details['specialty']); ?></p>
                        <p class="text-muted small">Electronic Signature</p>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end no-print">
                    <button type="button" class="btn btn-primary" onclick="printPrescription()">
                        <i class="fas fa-print me-2"></i> Print Prescription
                    </button>
                    <a href="give_prescription_walkin.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-plus-circle me-2"></i> Issue Another Prescription
                    </a>
                </div>
            </div>
        <?php else: // Display the form if no successful prescription yet ?>
            <div class="card shadow-sm mb-4 no-print">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Patient & Prescription Details</h4>
                </div>
                <div class="card-body">
                    <form action="give_prescription_walkin.php" method="POST">
                        <h5 class="mb-3">Walk-in Patient Information</h5>
                        <div class="mb-3">
                            <label for="walkin_patient_name" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="walkin_patient_name" name="walkin_patient_name" required value="<?php echo htmlspecialchars($_POST['walkin_patient_name'] ?? ''); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="walkin_patient_gender" class="form-label">Gender</label>
                                <select class="form-select" id="walkin_patient_gender" name="walkin_patient_gender" required>
                                    <option value="">-- Select --</option>
                                    <option value="Male" <?php echo (($_POST['walkin_patient_gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (($_POST['walkin_patient_gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo (($_POST['walkin_patient_gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="walkin_patient_dob" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="walkin_patient_dob" name="walkin_patient_dob" required value="<?php echo htmlspecialchars($_POST['walkin_patient_dob'] ?? ''); ?>">
                            </div>
                        </div>

                        <hr class="mt-4 mb-4">
                        <h5 class="mb-3">Prescription Details</h5>

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
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const medicationsContainer = document.getElementById('medications-container');
    const addMedicationBtn = document.getElementById('add-medication-btn');
    const medicationsJsonInput = document.getElementById('medications_json');
    const prescriptionForm = document.querySelector('form');

    let medicationCounter = medicationsContainer ? medicationsContainer.children.length : 0;
    if (medicationCounter === 0 && medicationsContainer) { // Only add if container exists and is empty
        addMedicationEntry();
    }

    function addMedicationEntry(name = '', dosage = '', frequency = '') {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'mb-2', 'medication-entry');
        newRow.id = `med-row-${Date.now()}-${medicationCounter}`;
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
        if (medicationsContainer) {
            medicationsContainer.appendChild(newRow);
            medicationCounter++;
        }
    }

    if (addMedicationBtn) {
        addMedicationBtn.addEventListener('click', function() {
            addMedicationEntry();
        });
    }

    if (medicationsContainer) {
        medicationsContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-medication-btn') || event.target.closest('.remove-medication-btn')) {
                const button = event.target.closest('.remove-medication-btn');
                const rowId = button.dataset.rowId;
                document.getElementById(rowId).remove();
            }
        });
    }

    if (prescriptionForm) {
        prescriptionForm.addEventListener('submit', function(event) {
            const medications = [];
            let allMedicationsValid = true;
            const medicationEntries = document.querySelectorAll('.medication-entry');

            if (medicationEntries.length === 0) {
                alert('Please add at least one medication.');
                event.preventDefault();
                return;
            }

            medicationEntries.forEach(function(row) {
                const nameInput = row.querySelector('.medication-name');
                const dosageInput = row.querySelector('.medication-dosage');
                const frequencyInput = row.querySelector('.medication-frequency');

                const name = nameInput.value;
                const dosage = dosageInput.value;
                const frequency = frequencyInput.value;

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

            if (!allMedicationsValid) {
                alert('Please fill in all required medication fields (Name and Frequency) or correct invalid entries.');
                event.preventDefault();
                return;
            }
            
            if (medicationsJsonInput) {
                medicationsJsonInput.value = JSON.stringify(medications);
            }
        });
    }
});

// JavaScript function to trigger printing
function printPrescription() {
    window.print();
}
</script>

<!-- <?php require_once '../includes/footer.php'; ?> -->