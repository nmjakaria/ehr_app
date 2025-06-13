<?php
// admin/manage_appointments.php
$title = "Manage Appointments";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submission for updating appointment status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $reason_for_cancellation = trim($_POST['reason_for_cancellation'] ?? '');

    // Basic validation
    if (!$appointment_id || !in_array($status, ['pending', 'accepted', 'confirmed', 'cancelled', 'completed'])) {
        $message = "Invalid input for appointment update.";
        $message_type = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            if ($status == 'cancelled' && empty($reason_for_cancellation)) {
                $message = "Reason for cancellation is required if status is 'Cancelled'.";
                $message_type = "danger";
            } else {
                $stmt = $pdo->prepare("UPDATE appointments SET status = ?, reason_for_cancellation = ? WHERE id = ?");
                $stmt->execute([$status, ($status == 'cancelled' ? $reason_for_cancellation : NULL), $appointment_id]);

                $pdo->commit();
                $message = "Appointment ID " . $appointment_id . " status updated to " . strtoupper($status) . " successfully!";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Database error updating appointment: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Fetch all appointments
$appointments = [];
try {
    $stmt = $pdo->query("SELECT
                            a.id AS appointment_id,
                            a.appointment_date_time,
                            a.reason AS appointment_reason,
                            a.status AS appointment_status,
                            a.reason_for_cancellation,
                            pu.full_name AS patient_name,
                            pu.username AS patient_username,
                            du.full_name AS doctor_name,
                            d.specialty
                        FROM
                            appointments a
                        JOIN
                            patients p ON a.patient_id = p.id
                        JOIN
                            users pu ON p.user_id = pu.id
                        JOIN
                            doctors d ON a.doctor_id = d.id
                        JOIN
                            users du ON d.user_id = du.id
                        ORDER BY
                            a.appointment_date_time DESC");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching appointments: " . $e->getMessage();
    $message_type = "danger";
}
?>

<div class="row justify-content-center">
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
                <h4 class="mb-0">All Appointments</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($appointments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped caption-top">
                            <caption>List of all appointments. Click "Manage" to update status.</caption>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['appointment_id']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y H:i A', strtotime($appointment['appointment_date_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?> (<?php echo htmlspecialchars($appointment['patient_username']); ?>)</td>
                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?> (<?php echo htmlspecialchars($appointment['specialty']); ?>)</td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_reason']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($appointment['appointment_status']) {
                                                case 'pending': $status_class = 'badge bg-warning text-dark'; break;
                                                case 'accepted': $status_class = 'badge bg-secondary text-light'; break;
                                                case 'confirmed': $status_class = 'badge bg-primary'; break;
                                                case 'cancelled': $status_class = 'badge bg-danger'; break;
                                                case 'completed': $status_class = 'badge bg-success'; break;
                                                default: $status_class = 'badge bg-secondary'; break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst(htmlspecialchars($appointment['appointment_status'])); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#manageAppointmentModal_<?php echo htmlspecialchars($appointment['appointment_id']); ?>">
                                                Manage
                                            </button>

                                            <!-- Manage Appointment Modal -->
                                            <div class="modal fade" id="manageAppointmentModal_<?php echo htmlspecialchars($appointment['appointment_id']); ?>" tabindex="-1" aria-labelledby="manageAppointmentModalLabel_<?php echo htmlspecialchars($appointment['appointment_id']); ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-info text-white">
                                                            <h5 class="modal-title" id="manageAppointmentModalLabel_<?php echo htmlspecialchars($appointment['appointment_id']); ?>">Manage Appointment #<?php echo htmlspecialchars($appointment['appointment_id']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="POST">
                                                            <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['appointment_id']); ?>">
                                                            <input type="hidden" name="update_appointment" value="1">
                                                            <div class="modal-body">
                                                                <p><strong>Patient:</strong> <?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                                                <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                                                <p><strong>Date & Time:</strong> <?php echo htmlspecialchars(date('M d, Y H:i A', strtotime($appointment['appointment_date_time']))); ?></p>
                                                                <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($appointment['appointment_reason'])); ?></p>
                                                                
                                                                <hr>

                                                                <div class="mb-3">
                                                                    <label for="status_<?php echo htmlspecialchars($appointment['appointment_id']); ?>" class="form-label">Update Status</label>
                                                                    <select class="form-select" id="status_<?php echo htmlspecialchars($appointment['appointment_id']); ?>" name="status" required onchange="toggleCancellationReason(this)">
                                                                        <option value="pending" <?php echo ($appointment['appointment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="accepted" <?php echo ($appointment['appointment_status'] == 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                                                                        <option value="confirmed" <?php echo ($appointment['appointment_status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                                        <option value="completed" <?php echo ($appointment['appointment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                                        <option value="cancelled" <?php echo ($appointment['appointment_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3" id="cancellationReasonDiv_<?php echo htmlspecialchars($appointment['appointment_id']); ?>" style="<?php echo ($appointment['appointment_status'] == 'cancelled') ? 'display: block;' : 'display: none;'; ?>">
                                                                    <label for="reason_for_cancellation_<?php echo htmlspecialchars($appointment['appointment_id']); ?>" class="form-label">Reason for Cancellation</label>
                                                                    <textarea class="form-control" id="reason_for_cancellation_<?php echo htmlspecialchars($appointment['appointment_id']); ?>" name="reason_for_cancellation" rows="3"><?php echo htmlspecialchars($appointment['reason_for_cancellation'] ?? ''); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No appointments found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript function to toggle cancellation reason field visibility
    function toggleCancellationReason(selectElement) {
        const appointmentId = selectElement.id.split('_')[1]; // Extract ID from select element's ID
        const cancellationReasonDiv = document.getElementById('cancellationReasonDiv_' + appointmentId);
        if (selectElement.value === 'cancelled') {
            cancellationReasonDiv.style.display = 'block';
            cancellationReasonDiv.querySelector('textarea').setAttribute('required', 'required');
        } else {
            cancellationReasonDiv.style.display = 'none';
            cancellationReasonDiv.querySelector('textarea').removeAttribute('required');
        }
    }

    // Initialize state for already rendered modals (on page load)
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('select[name="status"]').forEach(select => {
            toggleCancellationReason(select);
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>