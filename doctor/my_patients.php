<?php
// doctor/my_patients.php
$title = "My Patients";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: ../login.php");
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

// Fetch unique patients associated with this doctor (via appointments or lab orders)
$my_patients = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id AS patient_id, u.full_name, u.username, u.email, u.mobile
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.id IN (
            SELECT patient_id FROM appointments WHERE doctor_id = :doctor_id_app AND status = 'approved'
            UNION
            SELECT patient_id FROM lab_orders WHERE doctor_id = :doctor_id_lab
        )
        ORDER BY u.full_name ASC
    ");
    $stmt->bindParam(':doctor_id_app', $doctor_id, PDO::PARAM_INT);
    $stmt->bindParam(':doctor_id_lab', $doctor_id, PDO::PARAM_INT);
    $stmt->execute();
    $my_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching your patients: " . $e->getMessage();
    $message_type = "danger";
}

?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-9">
        <h2 class="mb-4">My Patients</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Patients under Your Care</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($my_patients)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_patients as $patient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['username']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['mobile']); ?></td>
                                        <td>
                                            <a href="patient_dashboard.php?patient_id=<?php echo htmlspecialchars($patient['patient_id']); ?>" class="btn btn-sm btn-primary" title="View Patient Dashboard">
                                                <i class="fas fa-eye me-1"></i> Dashboard
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">You currently have no patients associated with your profile (via approved appointments or lab orders).</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>