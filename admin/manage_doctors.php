<?php
// admin/manage_doctors.php
$title = "Manage Doctors";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $user_to_delete_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);

    if ($user_to_delete_id) {
        // Prevent admin from deleting their own account (optional but recommended)
        if ($user_to_delete_id == $_SESSION['user_id']) {
            $message = "You cannot delete your own admin account.";
            $message_type = "danger";
        } else {
            try {
                $pdo->beginTransaction();

                // Get doctor_id associated with the user_id
                $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $stmt->execute([$user_to_delete_id]);
                $doctor_id = $stmt->fetchColumn();

                if ($doctor_id) {
                    // Delete related data first (e.g., prescriptions, appointments for this doctor)
                    // If your tables have ON DELETE CASCADE, this might not be strictly necessary,
                    // but explicit deletion can be safer or for logging.
                    // For example, if you wanted to keep prescriptions but anonymize doctor, you'd update not delete.
                    // For now, assuming CASCADE or full deletion is acceptable.
                    // If appointments or prescriptions don't cascade on doctor deletion, you'd need:
                    // $stmt = $pdo->prepare("DELETE FROM appointments WHERE doctor_id = ?");
                    // $stmt->execute([$doctor_id]);
                    // $stmt = $pdo->prepare("DELETE FROM prescriptions WHERE doctor_id = ?");
                    // $stmt->execute([$doctor_id]);

                    // Delete from doctors table
                    $stmt = $pdo->prepare("DELETE FROM doctors WHERE user_id = ?");
                    $stmt->execute([$user_to_delete_id]);
                }

                // Delete from users table (this will cascade to doctors table if FK is set with ON DELETE CASCADE)
                // If not, you must delete from doctors first then users.
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'doctor'");
                $stmt->execute([$user_to_delete_id]);

                if ($stmt->rowCount() > 0) {
                    $message = "Doctor account deleted successfully.";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete doctor account or account not found.";
                    $message_type = "warning";
                }
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error deleting doctor: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    } else {
        $message = "Invalid doctor ID provided.";
        $message_type = "danger";
    }
}


// Fetch all doctors
$doctors = [];
try {
    $stmt = $pdo->query("SELECT u.id AS user_id, u.username, u.full_name, u.email, u.mobile, d.id AS doctor_id, d.specialty, d.license_number
                          FROM users u
                          JOIN doctors d ON u.id = d.user_id
                          WHERE u.user_type = 'doctor'
                          ORDER BY u.full_name");
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching doctors: " . $e->getMessage();
    $message_type = "danger";
}

?>

<div class="row justify-content-center mt-4">
    <div class="col-md-11 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Manage Doctor</h2>
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

        <div class="d-flex justify-content-end mb-3">
            <a href="add_doctor.php" class="btn btn-primary"><i class="fas fa-plus-circle me-2"></i> Add New Doctor</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">List of Doctors</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($doctors)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Specialty</th>
                                    <th>License No.</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doctor['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['username']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['specialty']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['license_number']); ?></td>
                                        <td>
                                            <a href="edit_doctor.php?user_id=<?php echo htmlspecialchars($doctor['user_id']); ?>" class="btn btn-sm btn-warning me-1" title="Edit Doctor"><i class="fas fa-edit"></i></a>
                                            <form action="manage_doctors.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($doctor['full_name']); ?>? This action cannot be undone and will remove all associated data.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($doctor['user_id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Doctor"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No doctor accounts found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>