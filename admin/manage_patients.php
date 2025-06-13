<?php
// admin/manage_patients.php
$title = "Manage Patients";
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

                // Get patient_id associated with the user_id
                $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
                $stmt->execute([$user_to_delete_id]);
                $patient_id = $stmt->fetchColumn();

                if ($patient_id) {
                    // If your tables have ON DELETE CASCADE, explicit deletion of dependent records
                    // (health_conditions, prescriptions, appointments, patient_access_tokens)
                    // might not be strictly necessary, but can be added for clarity or if cascades aren't fully set up.
                    // For example:
                    // $pdo->prepare("DELETE FROM health_conditions WHERE patient_id = ?")->execute([$patient_id]);
                    // $pdo->prepare("DELETE FROM prescriptions WHERE patient_id = ?")->execute([$patient_id]);
                    // $pdo->prepare("DELETE FROM appointments WHERE patient_id = ?")->execute([$patient_id]);
                    // $pdo->prepare("DELETE FROM patient_access_tokens WHERE patient_id = ?")->execute([$patient_id]);

                    // Delete from patients table
                    $stmt = $pdo->prepare("DELETE FROM patients WHERE user_id = ?");
                    $stmt->execute([$user_to_delete_id]);
                }

                // Delete from users table (this should trigger cascade if setup)
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'patient'");
                $stmt->execute([$user_to_delete_id]);

                if ($stmt->rowCount() > 0) {
                    $message = "Patient account deleted successfully.";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete patient account or account not found.";
                    $message_type = "warning";
                }
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Error deleting patient: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    } else {
        $message = "Invalid patient ID provided.";
        $message_type = "danger";
    }
}


// Fetch all patients
$patients = [];
try {
    $stmt = $pdo->query("SELECT u.id AS user_id, u.username, u.full_name, u.email, u.mobile, p.id AS patient_id, p.date_of_birth, p.gender, p.blood_group
                          FROM users u
                          JOIN patients p ON u.id = p.user_id
                          WHERE u.user_type = 'patient'
                          ORDER BY u.full_name");
    $patients = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching patients: " . $e->getMessage();
    $message_type = "danger";
}

?>

<div class="row justify-content-center">
    <div class="col-md-11 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Manage Patient</h2>
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
            <a href="add_patient.php" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i> Add New Patient</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">List of Patients</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($patients)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>DOB</th>
                                    <th>Gender</th>
                                    <th>Blood Group</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['username']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                        <td><?php htmlspecialchars($patient['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['date_of_birth']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['blood_group']); ?></td>
                                        <td>
                                            <a href="edit_patient.php?user_id=<?php echo htmlspecialchars($patient['user_id']); ?>" class="btn btn-sm btn-warning me-1" title="Edit Patient"><i class="fas fa-edit"></i></a>
                                            <form action="manage_patients.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($patient['full_name']); ?>? This action cannot be undone and will remove all associated patient data (health conditions, prescriptions, appointments, access tokens).');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($patient['user_id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Patient"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No patient accounts found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>