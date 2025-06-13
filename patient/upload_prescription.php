<?php
// patient/upload_prescription.php
$title = "Upload Prescription";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$patient_id = null;
$message = '';
$message_type = '';

// Fetch patient_id from the patients table
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $patient_data = $stmt->fetch();
    if ($patient_data) {
        $patient_id = $patient_data['id'];
    } else {
        die("Patient record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching patient ID: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $patient_id) {
    $notes = trim($_POST['notes'] ?? '');
    $image_path = null;
    $medications_json = null;

    // Collect dynamic medication inputs
    $medications = [];
    if (isset($_POST['med_name']) && is_array($_POST['med_name'])) {
        foreach ($_POST['med_name'] as $key => $name) {
            $name = trim($name);
            $dosage = trim($_POST['med_dosage'][$key] ?? '');
            $frequency = trim($_POST['med_frequency'][$key] ?? '');

            if (!empty($name)) { // Only add if medication name is not empty
                $medications[] = [
                    'name' => $name,
                    'dosage' => $dosage,
                    'frequency' => $frequency
                ];
            }
        }
    }

    if (empty($medications)) {
        $message = "At least one medication entry is required.";
        $message_type = "danger";
    } else {
        $medications_json = json_encode($medications);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = "Error encoding medications data.";
            $message_type = "danger";
        }
    }

    // Handle file upload (only if no prior errors)
    if ($message_type !== "danger" && isset($_FILES['prescription_image']) && $_FILES['prescription_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/prescriptions/"; // Directory to store uploaded images
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
        }

        $file_name = uniqid('pres_') . '_' . basename($_FILES['prescription_image']['name']);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is an actual image or PDF
        $check = getimagesize($_FILES['prescription_image']['tmp_name']);
        $is_image = ($check !== false);
        $is_pdf = ($imageFileType == "pdf");

        if ($is_image || $is_pdf) {
            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "pdf") {
                $message = "Sorry, only JPG, JPEG, PNG, GIF & PDF files are allowed.";
                $message_type = "danger";
            } else {
                // Check file size (e.g., max 5MB)
                if ($_FILES['prescription_image']['size'] > 5000000) {
                    $message = "Sorry, your file is too large (max 5MB).";
                    $message_type = "danger";
                } else {
                    if (move_uploaded_file($_FILES['prescription_image']['tmp_name'], $target_file)) {
                        $image_path = 'uploads/prescriptions/' . $file_name; // Path to store in DB
                    } else {
                        $message = "Sorry, there was an error uploading your file.";
                        $message_type = "danger";
                    }
                }
            }
        } else {
            $message = "File is not an image or PDF.";
            $message_type = "danger";
        }
    } elseif ($message_type !== "danger" && isset($_FILES['prescription_image']) && $_FILES['prescription_image']['error'] != UPLOAD_ERR_NO_FILE) {
        $message = "File upload error: " . $_FILES['prescription_image']['error'];
        $message_type = "danger";
    }

    // If no errors so far, attempt to save to DB
    if ($message_type !== "danger") {
        try {
            $stmt = $pdo->prepare("INSERT INTO patient_uploaded_prescriptions (patient_id, medications_json, notes, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$patient_id, $medications_json, $notes, $image_path]);

            $message = "Prescription record added successfully!";
            $message_type = "success";
            // Optionally clear form fields or redirect after success
            // header("Location: my_prescriptions.php"); exit();
        } catch (PDOException $e) {
            $message = "Error adding prescription record: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <h2 class="mb-4">Upload Your Prescription/Medication Info</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    Add New Medication Record
                </div>
                <div class="card-body">
                    <form action="upload_prescription.php" method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label">Medications <span class="text-danger">*</span></label>
                            <div id="medicationsContainer">
                                <div class="medication-item row g-2 mb-2 border rounded p-2 align-items-center">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="med_name[]" placeholder="Medication Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="med_dosage[]" placeholder="Dosage (e.g., 10 mg)">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" name="med_frequency[]" placeholder="Frequency (e.g., OD, BID, TID, QID)">
                                    </div>
                                    <div class="col-md-1 d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-medication" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addMedicationBtn">
                                <i class="fas fa-plus-circle me-1"></i> Add Another Medication
                            </button>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">General Notes/Instructions</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="prescription_image" class="form-label">Upload Prescription Image/Document (JPG, PNG, GIF, PDF - Max 5MB)</label>
                            <input type="file" class="form-control" id="prescription_image" name="prescription_image" accept="image/*,.pdf">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Medication Record</button>
                        <a href="my_prescriptions.php" class="btn btn-danger">Cancel</a>
                        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const medicationsContainer = document.getElementById('medicationsContainer');
        const addMedicationBtn = document.getElementById('addMedicationBtn');

        // Function to update remove button visibility
        function updateRemoveButtons() {
            const medicationItems = medicationsContainer.querySelectorAll('.medication-item');
            if (medicationItems.length > 1) {
                medicationItems.forEach(item => {
                    item.querySelector('.remove-medication').style.display = 'block';
                });
            } else {
                medicationItems.forEach(item => {
                    item.querySelector('.remove-medication').style.display = 'none';
                });
            }
        }

        // Add event listener for "Add Another Medication" button
        addMedicationBtn.addEventListener('click', function() {
            const template = medicationsContainer.querySelector('.medication-item');
            const newMedicationItem = template.cloneNode(true);

            // Clear values in the new cloned inputs
            newMedicationItem.querySelectorAll('input').forEach(input => input.value = '');

            // Show remove button for the new item
            const removeButton = newMedicationItem.querySelector('.remove-medication');
            removeButton.style.display = 'block'; // Ensure remove button is visible

            // Add event listener for the new remove button
            removeButton.addEventListener('click', function() {
                newMedicationItem.remove();
                updateRemoveButtons(); // Update visibility after removal
            });

            medicationsContainer.appendChild(newMedicationItem);
            updateRemoveButtons(); // Update visibility after adding
        });

        // Add event listeners to initial remove buttons (if any)
        medicationsContainer.querySelectorAll('.remove-medication').forEach(button => {
            button.addEventListener('click', function() {
                button.closest('.medication-item').remove();
                updateRemoveButtons(); // Update visibility after removal
            });
        });

        // Initial call to set correct button visibility
        updateRemoveButtons();
    });
</script>

<?php require_once '../includes/footer.php'; ?>