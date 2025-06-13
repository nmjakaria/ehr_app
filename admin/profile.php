<?php
// admin/profile.php
$title = "Admin Profile";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch current admin details
try {
    $stmt = $pdo->prepare("SELECT full_name, email, mobile FROM users WHERE id = ? AND user_type = 'admin'");
    $stmt->execute([$user_id]);
    $admin_data = $stmt->fetch();

    if (!$admin_data) {
        die("Admin profile not found.");
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = $_POST['password']; // New password, if provided
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($mobile)) {
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
            $stmt = $pdo->prepare("SELECT full_name, email, mobile FROM users WHERE id = ? AND user_type = 'admin'");
            $stmt->execute([$user_id]);
            $admin_data = $stmt->fetch();

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
            <div class="card-header bg-info text-white text-center">
                <h3 class="mb-0">Admin Profile</h3>
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
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin_data['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo htmlspecialchars($admin_data['mobile'] ?? ''); ?>" required>
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

                    <button type="submit" class="btn btn-info w-100 text-white">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>