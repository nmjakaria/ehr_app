<?php
// patient/profile.php
$title = "Patient Profile";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch current patient details
try {
    $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.mobile, p.date_of_birth, p.gender, p.blood_group
                           FROM users u
                           JOIN patients p ON u.id = p.user_id
                           WHERE u.id = ? AND u.user_type = 'patient'");
    $stmt->execute([$user_id]);
    $patient_data = $stmt->fetch();

    if (!$patient_data) {
        die("Patient profile not found.");
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = trim($_POST['gender']);
    $blood_group = trim($_POST['blood_group']);
    $password = $_POST['password']; // New password, if provided
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($mobile) || empty($date_of_birth) || empty($gender) || empty($blood_group)) {
        $message = "All required fields must be filled.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } elseif (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) {
        $message = "Invalid gender selected.";
        $message_type = "danger";
    } elseif (!empty($password) && strlen($password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = "danger";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            // Update users table
            $stmt_user_update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, mobile = ? WHERE id = ?");
            $stmt_user_update->execute([$full_name, $email, $mobile, $user_id]);

            // Update patients table
            $stmt_patient_update = $pdo->prepare("UPDATE patients SET date_of_birth = ?, gender = ?, blood_group = ? WHERE user_id = ?");
            $stmt_patient_update->execute([$date_of_birth, $gender, $blood_group, $user_id]);

            // Update password if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_password_update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_password_update->execute([$hashed_password, $user_id]);
            }

            $pdo->commit();
            $message = "Profile updated successfully!";
            $message_type = "success";

            // Refresh session data if full_name changed
            $_SESSION['full_name'] = $full_name;
            // Re-fetch data to display updated values on page
            $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.mobile, p.date_of_birth, p.gender, p.blood_group
                                   FROM users u
                                   JOIN patients p ON u.id = p.user_id
                                   WHERE u.id = ? AND u.user_type = 'patient'");
            $stmt->execute([$user_id]);
            $patient_data = $stmt->fetch();

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Duplicate email error
                $message = "The email address is already in use by another account.";
            } else {
                $message = "Error updating profile: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-success text-white text-center">
                <h3 class="mb-0">Patient Profile</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form action="profile.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($patient_data['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($patient_data['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo htmlspecialchars($patient_data['mobile'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($patient_data['date_of_birth'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (($patient_data['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (($patient_data['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (($patient_data['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="blood_group" class="form-label">Blood Group</label>
                            <input type="text" class="form-control" id="blood_group" name="blood_group" placeholder="e.g., A+, O-" value="<?php echo htmlspecialchars($patient_data['blood_group'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5>Change Password (Optional)</h5>
                    <p class="text-muted">Leave blank if you don't want to change your password.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>