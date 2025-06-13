<?php
// patient/book_appointment.php
$title = "Book Appointment";
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

// Fetch list of doctors for the dropdown
$doctors = [];
try {
    $stmt = $pdo->query("SELECT d.id AS doctor_id, u.full_name, d.specialty
                          FROM doctors d JOIN users u ON d.user_id = u.id
                          ORDER BY u.full_name");
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching doctors: " . $e->getMessage();
    $message_type = "danger";
}

// Handle form submission for appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = filter_var($_POST['doctor_id'], FILTER_VALIDATE_INT);
    $appointment_date_time = trim($_POST['appointment_date'] . ' ' . $_POST['appointment_time']);
    $reason = trim($_POST['reason']);

    // Basic validation
    if (!$doctor_id || empty($appointment_date_time) || empty($reason)) {
        $message = "Please fill all required fields.";
        $message_type = "danger";
    } elseif (!DateTime::createFromFormat('Y-m-d H:i', $appointment_date_time)) {
        $message = "Invalid date or time format.";
        $message_type = "danger";
    } else {
        // Convert to DateTime object to validate future date/time
        $requested_datetime = new DateTime($appointment_date_time);
        $current_datetime = new DateTime();

        if ($requested_datetime < $current_datetime) {
            $message = "Appointment date and time cannot be in the past.";
            $message_type = "danger";
        } else {
            try {
                // Check for overlapping appointments for the patient
                $stmt_patient_overlap = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date_time = ? AND status IN ('pending', 'accepted')");
                $stmt_patient_overlap->execute([$patient_id, $appointment_date_time]);
                if ($stmt_patient_overlap->fetchColumn() > 0) {
                    $message = "You already have an appointment booked for this exact time. Please choose a different slot.";
                    $message_type = "warning";
                } else {
                    // Check for overlapping appointments for the doctor
                    $stmt_doctor_overlap = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date_time = ? AND status IN ('pending', 'accepted')");
                    $stmt_doctor_overlap->execute([$doctor_id, $appointment_date_time]);
                    if ($stmt_doctor_overlap->fetchColumn() > 0) {
                        $message = "The selected doctor already has an appointment booked for this exact time. Please choose a different slot or doctor.";
                        $message_type = "warning";
                    } else {
                        // Insert the appointment
                        $stmt = $pdo->prepare("INSERT INTO appointments (doctor_id, patient_id, appointment_date_time, reason, status) VALUES (?, ?, ?, ?, 'pending')");
                        $stmt->execute([$doctor_id, $patient_id, $appointment_date_time, $reason]);

                        $message = "Appointment request sent successfully! Please wait for the doctor's approval.";
                        $message_type = "success";

                        // Clear form fields after successful submission (optional)
                        $_POST = array(); // Clear POST data
                    }
                }
            } catch (PDOException $e) {
                $message = "Error booking appointment: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <h2 class="mb-4">Book a New Appointment</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Appointment Details</h4>
                </div>
                <div class="card-body">
                    <form action="book_appointment.php" method="POST">
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">Select Doctor</label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">-- Select a Doctor --</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo htmlspecialchars($doctor['doctor_id']); ?>"
                                        <?php echo (($_POST['doctor_id'] ?? '') == $doctor['doctor_id']) ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> (<?php echo htmlspecialchars($doctor['specialty']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="appointment_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? date('Y-m-d')); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="appointment_time" class="form-label">Time</label>
                                <input type="time" class="form-control" id="appointment_time" name="appointment_time" required value="<?php echo htmlspecialchars($_POST['appointment_time'] ?? date('H:i')); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Appointment</label>
                            <textarea class="form-control" id="reason" name="reason" rows="4" required><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Request Appointment</button>
                    </form>
                    <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once '../includes/footer.php'; ?>