<?php
// admin/edit_patient.php
$title = "Edit Patient Profile";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';
$patient_data = null; // To store fetched patient details

// Get user_id from GET request
$user_id_to_edit = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT);

// If no user_id is provided, redirect back to manage patients
if (!$user_id_to_edit) {
    header("Location: manage_patients.php?message=" . urlencode("No patient selected for editing.") . "&message_type=danger");
    exit();
}

// Fetch existing patient data
try {
    $stmt = $pdo->prepare("SELECT u.id AS user_id, u.username, u.full_name, u.email, u.mobile, p.date_of_birth, p.gender, p.blood_group
                          FROM users u
                          JOIN patients p ON u.id = p.user_id
                          WHERE u.id = ? AND u.user_type = 'patient'");
    $stmt->execute([$user_id_to_edit]);
    $patient_data = $stmt->fetch();

    if (!$patient_data) {
        header("Location: manage_patients.php?message=" . urlencode("Patient not found.") . "&message_type=danger");
        exit();
    }
} catch (PDOException $e) {
    $message = "Error fetching patient data: " . $e->getMessage();
    $message_type = "danger";
}

// Handle form submission for updating patient
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $patient_data) {
    // User details
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);

    // Patient specific details
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];

    // Optional password change
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($full_name) || empty($email) || empty($mobile) || empty($date_of_birth) || empty($gender) || empty($blood_group)) {
        $message = "Please fill in all required fields.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $message = "New passwords do not match.";
        $message_type = "danger";
    } elseif (!empty($password) && strlen($password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = "danger";
    } elseif (!DateTime::createFromFormat('Y-m-d', $date_of_birth)) {
        $message = "Invalid date of birth format.";
        $message_type = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            // Update users table
            $update_user_sql = "UPDATE users SET full_name = ?, email = ?, mobile = ? WHERE id = ?";
            $user_params = [$full_name, $email, $mobile, $user_id_to_edit];

            // If password is provided, update it
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_user_sql = "UPDATE users SET full_name = ?, email = ?, mobile = ?, password_hash = ? WHERE id = ?";
                $user_params = [$full_name, $email, $mobile, $hashed_password, $user_id_to_edit];
            }
            $stmt = $pdo->prepare($update_user_sql);
            $stmt->execute($user_params);

            // Update patients table
            $stmt = $pdo->prepare("UPDATE patients SET date_of_birth = ?, gender = ?, blood_group = ? WHERE user_id = ?");
            $stmt->execute([$date_of_birth, $gender, $blood_group, $user_id_to_edit]);

            $pdo->commit();
            $message = "Patient profile updated successfully!";
            $message_type = "success";

            // Refresh the fetched data to show updated values immediately
            $stmt = $pdo->prepare("SELECT u.id AS user_id, u.username, u.full_name, u.email, u.mobile, p.date_of_birth, p.gender, p.blood_group
                                  FROM users u JOIN patients p ON u.id = p.user_id WHERE u.id = ?");
            $stmt->execute([$user_id_to_edit]);
            $patient_data = $stmt->fetch();

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Check for duplicate email if UNIQUE constraint is on email
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'email') !== false) {
                $message = "Error: Email already registered for another account. Please use a different email.";
            } else {
                $message = "Error updating patient: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Edit Patient Profile: <?php echo htmlspecialchars($patient_data['full_name'] ?? ''); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Update Patient Details</h4>
            </div>
            <div class="card-body">
                <form action="edit_patient.php?user_id=<?php echo htmlspecialchars($user_id_to_edit); ?>" method="POST">
                    <h5 class="mb-3">Login & Personal Information</h5>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($patient_data['username'] ?? ''); ?>" disabled>
                        <small class="form-text text-muted">Username cannot be changed.</small>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? $patient_data['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $patient_data['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" required value="<?php echo htmlspecialchars($_POST['mobile'] ?? $patient_data['mobile'] ?? ''); ?>">
                    </div>

                    <h5 class="mt-4 mb-3">Change Password (Optional)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted">Leave blank if you don't want to change password.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <hr class="mt-4 mb-4">
                    <h5 class="mb-3">Personal Information</h5>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? $patient_data['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">-- Select --</option>
                            <option value="Male" <?php echo (($_POST['gender'] ?? $patient_data['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($_POST['gender'] ?? $patient_data['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($_POST['gender'] ?? $patient_data['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="blood_group" class="form-label">Blood Group</label>
                        <select class="form-select" id="blood_group" name="blood_group" required>
                            <option value="">-- Select --</option>
                             <option value="A+" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'A+') ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'A-') ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'B+') ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'B-') ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'O+') ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo (($_POST['blood_group'] ?? $patient_data['blood_group'] ?? '') == 'O-') ? 'selected' : ''; ?>>O-</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Update Patient Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>