<?php
// patient/my_prescriptions.php
$title = "My Prescriptions";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$patient_id = null;
$message = '';
$message_type = '';
$prescriptions = [];

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

// Fetch prescriptions for the logged-in patient
try {
    $stmt = $pdo->prepare("SELECT pr.id, pr.diagnosis, pr.medications, pr.notes, pr.instructions, pr.prescription_date,
                                   u.full_name AS doctor_name, d.specialty
                            FROM prescriptions pr
                            JOIN doctors d ON pr.doctor_id = d.id
                            JOIN users u ON d.user_id = u.id
                            WHERE pr.patient_id = ?
                            ORDER BY pr.prescription_date DESC");
    $stmt->execute([$patient_id]);
    $prescriptions = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching prescriptions: " . $e->getMessage();
    $message_type = "danger";
}
// Fetch patient's uploaded prescriptions
$uploaded_prescriptions = [];
try {
    $stmt = $pdo->prepare("SELECT id, medications_json, notes, image_path, uploaded_at
                            FROM patient_uploaded_prescriptions
                            WHERE patient_id = ?
                            ORDER BY uploaded_at DESC");
    $stmt->execute([$patient_id]);
    $uploaded_prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
} catch (PDOException $e) {
    $message .= ($message ? "<br>" : "") . "Error fetching uploaded prescriptions: " . $e->getMessage();
    $message_type = "danger";
}
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <h2 class="mb-4">My Prescriptions</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($prescriptions) && empty($uploaded_prescriptions)): ?>
                <div class="alert alert-info" role="alert">
                    You have no prescription records (doctor-issued or uploaded) to display.
                    <a href="upload_prescription.php" class="alert-link">Click here to add one.</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($uploaded_prescriptions)): ?>
                <h3 class="mt-4 mb-3">My Uploaded Medication/Prescription Records
                    <div class="text-end">
                        <a href="upload_prescription.php" class="btn btn-sm btn-outline-primary ms-2 text-end"><i class="fas fa-plus-circle me-1"></i> Add New</a>
                    </div>
                </h3>
                <?php foreach ($uploaded_prescriptions as $uploaded_pres): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Patient Uploaded Record
                                <small class="ms-3">Uploaded: <?php echo htmlspecialchars(date('M d, Y', strtotime($uploaded_pres['uploaded_at']))); ?></small>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="managePatientPrescription<?php echo $uploaded_pres['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    Manage
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="managePatientPrescription<?php echo $uploaded_pres['id']; ?>">
                                    <li><button class="dropdown-item delete-prescription" data-id="<?php echo htmlspecialchars($uploaded_pres['id']); ?>" data-type="patient_uploaded" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">Delete</button></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6>Medications:</h6>
                            <ul class="list-group mb-3">
                                <?php
                                $uploaded_medications = json_decode($uploaded_pres['medications_json'], true);
                                if (is_array($uploaded_medications) && !empty($uploaded_medications)):
                                    foreach ($uploaded_medications as $med): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($med['name'] ?? 'N/A'); ?></strong>
                                                <?php if (!empty($med['dosage'])): ?>
                                                    <small class="text-muted ms-2">(<?php echo htmlspecialchars($med['dosage']); ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($med['frequency'] ?? 'N/A'); ?></span>
                                        </li>
                                    <?php endforeach;
                                else: ?>
                                    <li class="list-group-item text-muted">No specific medications listed for this record.</li>
                                <?php endif; ?>
                            </ul>

                            <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($uploaded_pres['notes'] ?: 'None')); ?></p>

                            <?php if (!empty($uploaded_pres['image_path'])): ?>
                                <div class="mt-3">
                                    <h6>Uploaded Document:</h6>
                                    <?php
                                    $image_url = '../' . htmlspecialchars($uploaded_pres['image_path']); // Adjust path for display
                                    $file_extension = pathinfo($image_url, PATHINFO_EXTENSION); // Corrected variable name from $uploaded_url to $image_url
                                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <a href="<?php echo $image_url; ?>" target="_blank">
                                            <img src="<?php echo $image_url; ?>" class="img-fluid rounded" alt="Prescription Image" style="max-width: 200px; height: auto;">
                                        </a>
                                    <?php elseif ($file_extension == 'pdf'): ?>
                                        <a href="<?php echo $image_url; ?>" target="_blank" class="btn btn-outline-primary">
                                            <i class="fas fa-file-pdf me-2"></i> View PDF Document
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">Unsupported file type for preview. <a href="<?php echo $image_url; ?>" target="_blank">Download File</a></p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No image or document uploaded for this record.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($prescriptions)): ?>
                <h3 class="mt-4 mb-3">Prescriptions Issued by Doctors</h3>
                <?php foreach ($prescriptions as $prescription): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Prescription #<?php echo htmlspecialchars($prescription['id']); ?>
                                <small class="ms-3">Issued: <?php echo htmlspecialchars(date('M d, Y', strtotime($prescription['prescription_date']))); ?> by Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?> (<?php echo htmlspecialchars($prescription['specialty']); ?>)</small>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="manageDocPrescription<?php echo $prescription['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    Manage
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="manageDocPrescription<?php echo $prescription['id']; ?>">
                                    <li><button class="dropdown-item delete-prescription" data-id="<?php echo htmlspecialchars($prescription['id']); ?>" data-type="doctor_issued" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">Delete</button></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6>Medications:</h6>
                            <ul class="list-group mb-3">
                                <?php
                                $uploaded_medications = json_decode($prescription['medications'], true);
                                if (is_array($uploaded_medications) && !empty($uploaded_medications)):
                                    foreach ($uploaded_medications as $med): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($med['name'] ?? 'N/A'); ?></strong>
                                                <?php if (!empty($med['dosage'])): ?>
                                                    <small class="text-muted ms-2">(<?php echo htmlspecialchars($med['dosage']); ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($med['frequency'] ?? 'N/A'); ?></span>
                                        </li>
                                    <?php endforeach;
                                else: ?>
                                    <li class="list-group-item text-muted">No specific medications listed for this record.</li>
                                <?php endif; ?>
                            </ul>

                            <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($prescription['notes'] ?: 'None')); ?></p>

                            <?php if (!empty($prescription['image_path'])): ?>
                                <div class="mt-3">
                                    <h6>Uploaded Document:</h6>
                                    <?php
                                    $image_url = '../' . htmlspecialchars($uploaded_pres['image_path']); // Adjust path for display
                                    $file_extension = pathinfo($image_url, PATHINFO_EXTENSION); // Corrected variable name
                                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <a href="<?php echo $image_url; ?>" target="_blank">
                                            <img src="<?php echo $image_url; ?>" class="img-fluid rounded" alt="Prescription Image" style="max-width: 200px; height: auto;">
                                        </a>
                                    <?php elseif ($file_extension == 'pdf'): ?>
                                        <a href="<?php echo $image_url; ?>" target="_blank" class="btn btn-outline-primary">
                                            <i class="fas fa-file-pdf me-2"></i> View PDF Document
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">Unsupported file type for preview. <a href="<?php echo $image_url; ?>" target="_blank">Download File</a></p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No image or document uploaded for this record.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this prescription record? This action cannot be undone.
                    <p class="mt-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Note: Deleting doctor-issued prescriptions might require administrative approval or might not be fully supported by your system's design. This will primarily manage your uploaded records.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let prescriptionToDeleteId = null;
        let prescriptionToDeleteType = null;
        const deleteConfirmationModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const alertContainer = document.querySelector('.container .row .col-md-10'); // Adjust selector as needed to target the main content area for alerts

        // Attach click event to all delete buttons
        document.querySelectorAll('.delete-prescription').forEach(button => {
            button.addEventListener('click', function() {
                prescriptionToDeleteId = this.dataset.id;
                prescriptionToDeleteType = this.dataset.type;
                // Modal is already triggered by data-bs-toggle/data-bs-target
            });
        });

        // Handle confirmation of delete
        confirmDeleteBtn.addEventListener('click', function() {
            deleteConfirmationModal.hide(); // Hide the modal

            if (prescriptionToDeleteId && prescriptionToDeleteType) {
                // Determine the correct card element to remove later
                let cardToRemove = document.querySelector(`.delete-prescription[data-id="${prescriptionToDeleteId}"][data-type="${prescriptionToDeleteType}"]`).closest('.card');

                // Send AJAX request to delete the prescription
                fetch('delete_prescription.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${prescriptionToDeleteId}&type=${prescriptionToDeleteType}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (cardToRemove) {
                                cardToRemove.remove(); // Remove the card from the DOM
                                showAlert('success', data.message);
                            } else {
                                showAlert('success', data.message + ' (Card could not be removed from view automatically.)');
                            }
                        } else {
                            showAlert('danger', 'Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('danger', 'An error occurred during deletion.');
                    })
                    .finally(() => {
                        prescriptionToDeleteId = null; // Reset variables
                        prescriptionToDeleteType = null;
                    });
            }
        });

        // Function to display dynamic alerts
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            // Prepend the alert to the top of the main content area
            if (alertContainer) {
                alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
            } else {
                console.warn('Alert container not found, alert will not be displayed on page.');
            }
        }
    });
</script>
<?php require_once '../includes/footer.php'; ?>