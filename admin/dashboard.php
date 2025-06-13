<?php
// admin/dashboard.php
$title = "Admin Dashboard";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php"); // Redirect to login if not authenticated as admin
    exit();
}

$message = '';
$message_type = '';

// Fetch counts for summary
$total_doctors = 0;
$total_patients = 0;
$total_appointments = 0;
$pending_appointments = 0;
$total_lab_test = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(id) FROM doctors");
    $total_doctors = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(id) FROM patients");
    $total_patients = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(id) FROM appointments");
    $total_appointments = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(id) FROM appointments WHERE status = 'pending'");
    $pending_appointments = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(id) FROM lab_orders");
    $total_lab_test = $stmt->fetchColumn();

} catch (PDOException $e) {
    $message = "Error fetching dashboard data: " . $e->getMessage();
    $message_type = "danger";
}

?>

<div class="row justify-content-center mt-5">
    <div class="col-md-10 col-lg-9">
        <h2 class="mb-4">Admin Dashboard</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Total Doctors</h5>
                                <h1 class="card-text"><?php echo htmlspecialchars($total_doctors); ?></h1>
                            </div>
                            <i class="fas fa-user-md fa-3x"></i>
                        </div>
                        <a href="manage_doctors.php" class="stretched-link text-white text-decoration-none mt-3 d-block">Manage Doctors <i class="fas fa-arrow-circle-right ms-2"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Total Patients</h5>
                                <h1 class="card-text"><?php echo htmlspecialchars($total_patients); ?></h1>
                            </div>
                            <i class="fas fa-procedures fa-3x"></i>
                        </div>
                        <a href="manage_patients.php" class="stretched-link text-white text-decoration-none mt-3 d-block">Manage Patients <i class="fas fa-arrow-circle-right ms-2"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Total Appointments</h5>
                                <h1 class="card-text"><?php echo htmlspecialchars($total_appointments); ?></h1>
                            </div>
                            <i class="fas fa-calendar-alt fa-3x"></i>
                        </div>
                        <a href="manage_appointments.php" class="stretched-link text-white text-decoration-none mt-3 d-block disabled">View All Appointments<i class="fas fa-arrow-circle-right ms-2"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Pending Appointments</h5>
                                <h1 class="card-text"><?php echo htmlspecialchars($pending_appointments); ?></h1>
                            </div>
                            <i class="fas fa-hourglass-half fa-3x"></i>
                        </div>
                        <a href="manage_appointments.php" class="stretched-link text-white text-decoration-none mt-3 d-block disabled">Review Pending <i class="fas fa-arrow-circle-right ms-2"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card text-white bg-secondary bg-gradient shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">Total Lab Test</h5>
                                <h1 class="card-text"><?php echo htmlspecialchars($total_lab_test)?></h1>
                            </div>
                            <i class="fas fa-flask-vial fa-3x"></i>
                        </div>
                        <a href="../admin/manage_lab_tests.php" class="stretched-link text-white text-decoration-none mt-3 d-block">View all lab test report <i class="fas fa-arrow-circle-right ms-2"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>