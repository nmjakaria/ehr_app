<?php
// patient/view_lab_results.php
$title = "My Lab Results";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

$message = '';
$message_type = '';

// Fetch doctor-ordered lab results
// Fetch doctor-ordered lab results
$doctor_ordered_lab_results = []; // Renamed variable for clarity
try {
    $stmt = $pdo->prepare("SELECT
                                lo.id AS order_id,
                                lt.test_name,
                                lo.order_date,
                                lo.status,
                                lr.result_data,
                                lr.result_date,
                                lr.notes AS result_notes,
                                u.full_name AS doctor_name -- Doctor's full name from users table
                           FROM
                                lab_orders lo
                           JOIN
                                lab_tests lt ON lo.test_id = lt.id
                           LEFT JOIN
                                lab_results lr ON lo.id = lr.order_id
                           JOIN
                                doctors d ON lo.doctor_id = d.id
                           JOIN
                                users u ON d.user_id = u.id
                           WHERE
                                lo.patient_id = ?
                           ORDER BY
                                lo.order_date DESC");
    $stmt->execute([$patient_id]);
    $doctor_ordered_lab_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching doctor-ordered lab results: " . $e->getMessage();
    $message_type = "danger";
}

// Fetch patient-uploaded lab reports
$uploaded_lab_reports = [];
try {
    $stmt = $pdo->prepare("SELECT id, test_name, notes, image_path, uploaded_at
                            FROM patient_uploaded_lab_reports
                            WHERE patient_id = ?
                            ORDER BY uploaded_at DESC");
    $stmt->execute([$patient_id]);
    $uploaded_lab_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= ($message ? "<br>" : "") . "Error fetching uploaded lab reports: " . $e->getMessage();
    $message_type = "danger";
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-10">
        <h2 class="mb-4">My Lab Results</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($lab_results) && empty($uploaded_lab_reports)): ?>
            <div class="alert alert-info" role="alert">
                You have no lab results to display.
                <a href="upload_lab_report.php" class="alert-link">Click here to add one.</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($doctor_ordered_lab_results)): ?>
            <h3 class="mt-4 mb-3">Lab Results Ordered by Doctors</h3>
            <?php foreach ($doctor_ordered_lab_results as $lab_order): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($lab_order['test_name']); ?>
                            <small class="ms-3">Ordered: <?php echo htmlspecialchars(date('M d, Y', strtotime($lab_order['order_date']))); ?> by Dr. <?php echo htmlspecialchars($lab_order['doctor_name']); ?></small>
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="manageDocLabResult<?php echo $lab_order['order_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                Manage
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="manageDocLabResult<?php echo $lab_order['order_id']; ?>">
                                <li><button class="dropdown-item delete-lab-result" data-id="<?php echo htmlspecialchars($lab_order['order_id']); ?>" data-type="doctor_ordered" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">Delete</button></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong>
                            <?php
                            $status_class = '';
                            switch ($lab_order['status']) {
                                case 'pending':
                                    $status_class = 'badge bg-warning text-dark';
                                    break;
                                case 'completed':
                                    $status_class = 'badge bg-success';
                                    break;
                                case 'cancelled':
                                    $status_class = 'badge bg-danger';
                                    break;
                                default:
                                    $status_class = 'badge bg-secondary';
                                    break;
                            }
                            ?>
                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst(htmlspecialchars($lab_order['status'])); ?></span>
                        </p>

                        <?php if ($lab_order['status'] == 'completed' && !empty($lab_order['result_data'])): ?>
                            <h6>Results:</h6>
                            <pre class="bg-light p-3 rounded"><code><?php
                                                                    // Pretty print JSON
                                                                    $decoded_results = json_decode($lab_order['result_data'], true);
                                                                    if (json_last_error() === JSON_ERROR_NONE) {
                                                                        echo htmlspecialchars(json_encode($decoded_results, JSON_PRETTY_PRINT));
                                                                    } else {
                                                                        echo htmlspecialchars($lab_order['result_data']); // Fallback if not valid JSON
                                                                    }
                                                                    ?></code></pre>
                            <p><strong>Result Date:</strong> <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($lab_order['result_date']))); ?></p>
                            <?php if (!empty($lab_order['result_notes'])): ?>
                                <h5 class="mt-3">Notes from Lab:</h5>
                                <p class="bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($lab_order['result_notes'])); ?></p>
                            <?php endif; ?>
                        <?php elseif ($lab_order['status'] == 'pending'): ?>
                            <p class="text-muted">Results Pending</p>
                        <?php else: ?>
                            <p class="text-danger">No results available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($uploaded_lab_reports)): ?>
            <h3 class="mt-4 mb-3">My Uploaded Lab Reports <a href="upload_lab_report.php" class="btn btn-sm btn-outline-primary ms-2"><i class="fas fa-plus-circle me-1"></i> Add New</a></h3>
            <?php foreach ($uploaded_lab_reports as $uploaded_report): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($uploaded_report['test_name']); ?>
                            <small class="ms-3">Uploaded: <?php echo htmlspecialchars(date('M d, Y', strtotime($uploaded_report['uploaded_at']))); ?></small>
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="managePatientLabReport<?php echo $uploaded_report['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                Manage
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="managePatientLabReport<?php echo $uploaded_report['id']; ?>">
                                <li><button class="dropdown-item delete-lab-result" data-id="<?php echo htmlspecialchars($uploaded_report['id']); ?>" data-type="patient_uploaded" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">Delete</button></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($uploaded_report['notes'])): ?>
                            <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($uploaded_report['notes'])); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($uploaded_report['image_path'])): ?>
                            <div class="mt-3">
                                <h6>Uploaded Document:</h6>
                                <?php
                                $image_url = '../' . htmlspecialchars($uploaded_report['image_path']);
                                $file_extension = pathinfo($image_url, PATHINFO_EXTENSION);
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <a href="<?php echo $image_url; ?>" target="_blank">
                                        <img src="<?php echo $image_url; ?>" class="img-fluid rounded" alt="Lab Report Image" style="max-width: 200px; height: auto;">
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
                            <p class="text-muted">No image or document uploaded for this report.</p>
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
                Are you sure you want to delete this lab report record? This action cannot be undone.
                <p class="mt-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Note: Deleting doctor-ordered lab reports might require administrative approval.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let labReportToDeleteId = null;
        let labReportToDeleteType = null;
        const deleteConfirmationModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const alertContainer = document.querySelector('.container .row .col-md-10'); // Adjust selector as needed

        // Attach click event to all delete buttons
        document.querySelectorAll('.delete-lab-result').forEach(button => {
            button.addEventListener('click', function() {
                labReportToDeleteId = this.dataset.id;
                labReportToDeleteType = this.dataset.type;
                // Modal is already triggered by data-bs-toggle/data-bs-target
            });
        });

        // Handle confirmation of delete
        confirmDeleteBtn.addEventListener('click', function() {
            deleteConfirmationModal.hide(); // Hide the modal

            if (labReportToDeleteId && labReportToDeleteType) {
                // Determine the correct card element to remove later
                let cardToRemove = document.querySelector(`.delete-lab-result[data-id="${labReportToDeleteId}"][data-type="${labReportToDeleteType}"]`).closest('.card');
                // Send AJAX request to delete the lab report
                fetch('delete_lab_report.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${labReportToDeleteId}&type=${labReportToDeleteType}`
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
                        labReportToDeleteId = null; // Reset variables
                        labReportToDeleteType = null;
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