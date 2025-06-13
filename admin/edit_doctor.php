<?php
// admin/edit_doctor.php
$title = "Edit Doctor Profile";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';
$doctor_data = null; // To store fetched doctor details

// Get user_id from GET request
$user_id_to_edit = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT);

// If no user_id is provided, redirect back to manage doctors
if (!$user_id_to_edit) {
    header("Location: manage_doctors.php?message=" . urlencode("No doctor selected for editing.") . "&message_type=danger");
    exit();
}

// Fetch existing doctor data
try {
    $stmt = $pdo->prepare("SELECT u.id AS user_id, u.username, u.full_name, u.email, u.mobile, d.specialty, d.license_number
                          FROM users u
                          JOIN doctors d ON u.id = d.user_id
                          WHERE u.id = ? AND u.user_type = 'doctor'");
    $stmt->execute([$user_id_to_edit]);
    $doctor_data = $stmt->fetch();

    if (!$doctor_data) {
        header("Location: manage_doctors.php?message=" . urlencode("Doctor not found.") . "&message_type=danger");
        exit();
    }
} catch (PDOException $e) {
    $message = "Error fetching doctor data: " . $e->getMessage();
    $message_type = "danger";
}

// Handle form submission for updating doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $doctor_data) {
    // User details
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);

    // Doctor specific details
    $specialty = trim($_POST['specialty']);
    $license_number = trim($_POST['license_number']);

    // Optional password change
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($full_name) || empty($email) || empty($mobile) || empty($specialty) || empty($license_number)) {
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

            // Update doctors table
            $stmt = $pdo->prepare("UPDATE doctors SET specialty = ?, license_number = ? WHERE user_id = ?");
            $stmt->execute([$specialty, $license_number, $user_id_to_edit]);

            $pdo->commit();
            $message = "Doctor profile updated successfully!";
            $message_type = "success";

            // Refresh the fetched data to show updated values immediately
            $stmt = $pdo->prepare("SELECT u.id AS user_id, u.username, u.full_name, u.email, u.mobile, d.specialty, d.license_number
                                  FROM users u JOIN doctors d ON u.id = d.user_id WHERE u.id = ?");
            $stmt->execute([$user_id_to_edit]);
            $doctor_data = $stmt->fetch();

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Check for duplicate email if UNIQUE constraint is on email
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'email') !== false) {
                $message = "Error: Email already registered for another account. Please use a different email.";
            } else {
                $message = "Error updating doctor: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Edit Doctor Profile: <?php echo htmlspecialchars($doctor_data['full_name'] ?? ''); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Update Doctor Details</h4>
            </div>
            <div class="card-body">
                <form action="edit_doctor.php?user_id=<?php echo htmlspecialchars($user_id_to_edit); ?>" method="POST">
                    <h5 class="mb-3">Login & Personal Information</h5>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($doctor_data['username'] ?? ''); ?>" disabled>
                        <small class="form-text text-muted">Username cannot be changed.</small>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? $doctor_data['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $doctor_data['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" required value="<?php echo htmlspecialchars($_POST['mobile'] ?? $doctor_data['mobile'] ?? ''); ?>">
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
                    <h5 class="mb-3">Professional Information</h5>
                    <div class="mb-3">
                        <label for="specialty" class="form-label">Specialty</label>
                        <input type="text" class="form-control" id="specialty" name="specialty" required value="<?php echo htmlspecialchars($_POST['specialty'] ?? $doctor_data['specialty'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="license_number" class="form-label">License Number</label>
                        <input type="text" class="form-control" id="license_number" name="license_number" required value="<?php echo htmlspecialchars($_POST['license_number'] ?? $doctor_data['license_number'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Update Doctor Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>