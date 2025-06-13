<?php
// doctor/dashboard.php
$title = "Doctor Dashboard";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: login.php"); // Redirect to login page if not logged in as a doctor
    exit();
}

// Fetch doctor's information (example)
try {
    $stmt = $pdo->prepare("SELECT designation, specialty FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch();
} catch (PDOException $e) {
    // Handle database error
    echo "Error: " . $e->getMessage();
    exit();
}

?>

<div class="container mt-4">
    <h1 id="welcomeMessage">Welcome, Doctor <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-info-circle"></i> Your Information
                </div>
                <div class="card-body">
                    <p><strong>Designation:</strong> <?php echo htmlspecialchars($doctor['designation'] ?? 'N/A'); ?></p>
                    <p><strong>Specialty:</strong> <?php echo htmlspecialchars($doctor['specialty'] ?? 'N/A'); ?></p>
                    </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-calendar-alt"></i> Upcoming Appointments
                </div>
                <div class="card-body">
                    <p>No upcoming appointments yet.</p>
                    </div>
            </div>
        </div>
    </div>
    </div>

<?php require_once '../includes/footer.php'; ?>