<?php
// doctor/profile.php
$title = "Doctor Profile";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch current doctor details
try {
    $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.mobile, d.designation, d.chamber_location, d.education_qualification, d.specialty
                           FROM users u
                           JOIN doctors d ON u.id = d.user_id
                           WHERE u.id = ? AND u.user_type = 'doctor'");
    $stmt->execute([$user_id]);
    $doctor_data = $stmt->fetch();

    if (!$doctor_data) {
        // Should not happen if user is logged in, but good to handle
        die("Doctor profile not found.");
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $designation = trim($_POST['designation']);
    $chamber_location = trim($_POST['chamber_location']);
    $education_qualification = trim($_POST['education_qualification']);
    $specialty = trim($_POST['specialty']);
    $password = $_POST['password']; // New password, if provided
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($mobile) || empty($designation) || empty($chamber_location) || empty($education_qualification) || empty($specialty)) {
        $message = "All required fields must be filled.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
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

            // Update doctors table
            $stmt_doctor_update = $pdo->prepare("UPDATE doctors SET designation = ?, chamber_location = ?, education_qualification = ?, specialty = ? WHERE user_id = ?");
            $stmt_doctor_update->execute([$designation, $chamber_location, $education_qualification, $specialty, $user_id]);

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
            $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.mobile, d.designation, d.chamber_location, d.education_qualification, d.specialty
                                   FROM users u
                                   JOIN doctors d ON u.id = d.user_id
                                   WHERE u.id = ? AND u.user_type = 'doctor'");
            $stmt->execute([$user_id]);
            $doctor_data = $stmt->fetch();


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
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0">Doctor Profile</h3>
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
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($doctor_data['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($doctor_data['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo htmlspecialchars($doctor_data['mobile'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" class="form-control" id="designation" name="designation" value="<?php echo htmlspecialchars($doctor_data['designation'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="chamber_location" class="form-label">Chamber Location</label>
                        <textarea class="form-control" id="chamber_location" name="chamber_location" rows="3" required><?php echo htmlspecialchars($doctor_data['chamber_location'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="education_qualification" class="form-label">Education Qualification (e.g., MBBS, FCPS)</label>
                        <input type="text" class="form-control" id="education_qualification" name="education_qualification" value="<?php echo htmlspecialchars($doctor_data['education_qualification'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="specialty" class="form-label">Specialty (e.g., Cardiology, Pediatrics)</label>
                        <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo htmlspecialchars($doctor_data['specialty'] ?? ''); ?>" required>
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

                    <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>