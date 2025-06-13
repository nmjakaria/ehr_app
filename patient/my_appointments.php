<?php
// patient/my_appointments.php
$title = "My Appointments";
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

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_appointment') {
    $appointment_id_to_cancel = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);

    if (!$appointment_id_to_cancel) {
        $message = "Invalid appointment ID.";
        $message_type = "danger";
    } else {
        try {
            // Verify that this appointment belongs to the logged-in patient and is pending
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled', notes = CONCAT(notes, '\nPatient cancelled on ', NOW()) WHERE id = ? AND patient_id = ? AND status = 'pending'");
            $stmt->execute([$appointment_id_to_cancel, $patient_id]);

            if ($stmt->rowCount() > 0) {
                $message = "Appointment cancelled successfully.";
                $message_type = "success";
            } else {
                $message = "Could not cancel appointment. It may no longer be pending or does not belong to you.";
                $message_type = "warning";
            }
        } catch (PDOException $e) {
            $message = "Error cancelling appointment: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}


// Fetch appointments for the logged-in patient
$appointments = [];
try {
    $stmt = $pdo->prepare("SELECT
                            a.id,
                            a.appointment_date_time,
                            a.reason,
                            a.status,
                            a.doctor_notes,          -- ADD THIS LINE
                            a.reason_for_decline,    -- ADD THIS LINE (so patient can see decline reason too)
                            a.reason_for_cancellation,    -- ADD THIS LINE (so patient can see decline reason too)
                            u.full_name AS doctor_name,
                            d.specialty
                        FROM
                            appointments a
                        JOIN
                            doctors d ON a.doctor_id = d.id
                        JOIN
                            users u ON d.user_id = u.id
                        WHERE
                            a.patient_id = ?
                        ORDER BY
                            a.appointment_date_time DESC");
    $stmt->execute([$patient_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ... error handling ...
}

?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <h2 class="mb-4">My Appointments</h2>
            <div class="text-end">
                <a href="book_appointment.php" class="btn btn-sm btn-outline-primary ms-2 mb-3"><i class="fas fa-plus-circle me-1"></i>New Appointment</a>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">List of Appointments</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($appointments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialty</th>
                                        <th>Date & Time</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                                            <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($appointment['appointment_date_time']))); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($appointment['status']) {
                                                    case 'pending':
                                                        $status_class = 'badge bg-warning text-dark';
                                                        break;
                                                    case 'accepted':
                                                        $status_class = 'badge bg-success';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'badge bg-danger';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'badge bg-info';
                                                        break;
                                                    default:
                                                        $status_class = 'badge bg-secondary';
                                                        break;
                                                }
                                                ?>
                                                <span class="<?php echo $status_class; ?>"><?php echo ucfirst(htmlspecialchars($appointment['status'])); ?></span>

                                                <?php if ($appointment['status'] == 'accepted' && !empty($appointment['doctor_notes'])): ?>
                                                    <p class="mt-2 mb-0 small text-success">
                                                        <strong>Doctor's Message:</strong> <?php echo nl2br(htmlspecialchars($appointment['doctor_notes'])); ?>
                                                    </p>
                                                <?php elseif ($appointment['status'] == 'cancelled' && !empty($appointment['reason_for_decline'])): ?>
                                                    <p class="mt-2 mb-0 small text-danger">
                                                        <strong>Decline Reason:</strong> <?php echo nl2br(htmlspecialchars($appointment['reason_for_decline'])); ?>
                                                    </p>
                                                <?php elseif ($appointment['status'] == 'cancelled' && !empty($appointment['reason_for_cancellation'])): ?>
                                                    <p class="mt-2 mb-0 small text-danger">
                                                        <strong>Cancel Reason:</strong> <?php echo nl2br(htmlspecialchars($appointment['reason_for_cancellation'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($appointment['status'] == 'pending'): ?>
                                                    <form action="my_appointments.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                        <input type="hidden" name="action" value="cancel_appointment">
                                                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <small class="text-muted">No action</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You have no appointments booked yet. <a href="book_appointment.php">Book an appointment now!</a></p>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>