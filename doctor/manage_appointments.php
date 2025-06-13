<?php
// doctor/manage_appointments.php
$title = "Manage Appointments";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$doctor_id = null;
$message = '';
$message_type = '';

// Fetch doctor_id from the doctors table
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doctor_data = $stmt->fetch();
    if ($doctor_data) {
        $doctor_id = $doctor_data['id'];
    } else {
        die("Doctor record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching doctor ID: " . $e->getMessage());
}

// Handle appointment action (accept/decline) from modals
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $new_status = '';
    $notes = null; // for doctor_notes
    $decline_reason = null; // for reason_for_decline

    if ($_POST['action'] == 'accept') {
        $new_status = 'accepted';
        $notes = trim($_POST['doctor_message'] ?? '');
    } elseif ($_POST['action'] == 'decline') {
        $new_status = 'cancelled'; // Using 'cancelled' status as it often represents decline
        $decline_reason = trim($_POST['decline_reason'] ?? '');
        if (empty($decline_reason)) {
            $message = "Reason for decline is required.";
            $message_type = "danger";
        }
    } else {
        $message = "Invalid action.";
        $message_type = "danger";
    }

    if (!empty($new_status) && $appointment_id && ($message_type == '' || $message_type == 'success')) {
        try {
            $pdo->beginTransaction();

            // Prepare the statement to update appointment status and notes/reason
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, doctor_notes = ?, reason_for_decline = ? WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$new_status, $notes, $decline_reason, $appointment_id, $doctor_id]);

            // If updating to 'cancelled' (declined), you might want to consider notifying the patient.
            // For 'accepted', the patient also receives a message.

            $pdo->commit();
            $message = "Appointment " . ($new_status == 'accepted' ? 'accepted' : 'declined') . " successfully!";
            $message_type = "success";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Database error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Fetch appointments assigned to the logged-in doctor
$appointments = [];
try {
    $stmt = $pdo->prepare("SELECT
                            a.id,
                            a.appointment_date_time,
                            a.reason,
                            a.status,
                            a.doctor_notes,          -- Fetch doctor_notes
                            a.reason_for_decline,    -- Fetch reason_for_decline
                            u.full_name AS patient_name,
                            u.username AS patient_username
                        FROM
                            appointments a
                        JOIN
                            patients p ON a.patient_id = p.id
                        JOIN
                            users u ON p.user_id = u.id
                        WHERE
                            a.doctor_id = ?
                        ORDER BY
                            a.appointment_date_time DESC");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching appointments: " . $e->getMessage();
    $message_type = "danger";
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-11 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Manage Appointments</h2>
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left me-2"></i> Back
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Your Appointments</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($appointments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped caption-top">
                            <caption>List of your appointments.</caption>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y H:i A', strtotime($appointment['appointment_date_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?> (<?php echo htmlspecialchars($appointment['patient_username']); ?>)</td>
                                        <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($appointment['status']) {
                                                case 'pending': $status_class = 'badge bg-warning text-dark'; break;
                                                case 'accepted': $status_class = 'badge bg-success'; break;
                                                case 'cancelled': $status_class = 'badge bg-danger'; break; // This now includes declined
                                                case 'completed': $status_class = 'badge bg-info'; break;
                                                default: $status_class = 'badge bg-secondary'; break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst(htmlspecialchars($appointment['status'])); ?></span>
                                            <?php if ($appointment['status'] == 'accepted' && !empty($appointment['doctor_notes'])): ?>
                                                <i class="fas fa-info-circle text-info ms-1" title="Doctor's Notes: <?php echo htmlspecialchars($appointment['doctor_notes']); ?>"></i>
                                            <?php elseif ($appointment['status'] == 'cancelled' && !empty($appointment['reason_for_decline'])): ?>
                                                <i class="fas fa-exclamation-triangle text-danger ms-1" title="Reason for Decline: <?php echo htmlspecialchars($appointment['reason_for_decline']); ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] == 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#acceptModal_<?php echo htmlspecialchars($appointment['id']); ?>">
                                                    Accept
                                                </button>

                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#declineModal_<?php echo htmlspecialchars($appointment['id']); ?>">
                                                    Decline
                                                </button>

                                                <div class="modal fade" id="acceptModal_<?php echo htmlspecialchars($appointment['id']); ?>" tabindex="-1" aria-labelledby="acceptModalLabel_<?php echo htmlspecialchars($appointment['id']); ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-success text-white">
                                                                <h5 class="modal-title" id="acceptModalLabel_<?php echo htmlspecialchars($appointment['id']); ?>">Accept Appointment #<?php echo htmlspecialchars($appointment['id']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="manage_appointments.php" method="POST">
                                                                <input type="hidden" name="action" value="accept">
                                                                <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                                <div class="modal-body">
                                                                    <p>Confirm acceptance of appointment with **<?php echo htmlspecialchars($appointment['patient_name']); ?>** on **<?php echo htmlspecialchars(date('M d, Y H:i A', strtotime($appointment['appointment_date_time']))); ?>**.</p>
                                                                    <div class="mb-3">
                                                                        <label for="doctor_message_<?php echo htmlspecialchars($appointment['id']); ?>" class="form-label">Message for Patient (Optional)</label>
                                                                        <textarea class="form-control" id="doctor_message_<?php echo htmlspecialchars($appointment['id']); ?>" name="doctor_message" rows="3" placeholder="e.g., 'Looking forward to seeing you.' or 'Please bring your previous medical records.'"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-success">Confirm Acceptance</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="modal fade" id="declineModal_<?php echo htmlspecialchars($appointment['id']); ?>" tabindex="-1" aria-labelledby="declineModalLabel_<?php echo htmlspecialchars($appointment['id']); ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="declineModalLabel_<?php echo htmlspecialchars($appointment['id']); ?>">Decline Appointment #<?php echo htmlspecialchars($appointment['id']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="manage_appointments.php" method="POST">
                                                                <input type="hidden" name="action" value="decline">
                                                                <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to decline the appointment with **<?php echo htmlspecialchars($appointment['patient_name']); ?>** on **<?php echo htmlspecialchars(date('M d, Y H:i A', strtotime($appointment['appointment_date_time']))); ?>**?</p>
                                                                    <div class="mb-3">
                                                                        <label for="decline_reason_<?php echo htmlspecialchars($appointment['id']); ?>" class="form-label">Reason for Decline (Required)</label>
                                                                        <textarea class="form-control" id="decline_reason_<?php echo htmlspecialchars($appointment['id']); ?>" name="decline_reason" rows="3" placeholder="e.g., 'Conflicting schedule', 'Not specialized in this area'." required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-danger">Confirm Decline</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php else: ?>
                                                <small class="text-muted">No actions available for <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?> appointments.</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">You have no appointments to manage yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>