<?php
// admin/add_doctor.php
$title = "Add New Doctor";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // User details
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);

    // Doctor specific details
    $specialty = trim($_POST['specialty']);
    $license_number = trim($_POST['license_number']);

    // Basic validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($specialty) || empty($license_number) || empty($mobile)) {
        $message = "Please fill in all required fields.";
        $message_type = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "danger";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_type = 'doctor'; // Fixed type for this page

        try {
            $pdo->beginTransaction();

            // 1. Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, mobile, user_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $email, $mobile, $user_type]);
            $new_user_id = $pdo->lastInsertId();

            // 2. Insert into doctors table
            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, specialty, license_number) VALUES (?, ?, ?)");
            $stmt->execute([$new_user_id, $specialty, $license_number]);

            $pdo->commit();
            $message = "Doctor account added successfully!";
            $message_type = "success";

            // Clear form fields on success
            $_POST = array();

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Check for duplicate username/email
            if ($e->getCode() == '23000') { // SQLSTATE for integrity constraint violation
                if (strpos($e->getMessage(), 'username') !== false) {
                    $message = "Error: Username already exists. Please choose a different username.";
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $message = "Error: Email already registered. Please use a different email.";
                } else {
                    $message = "Database error: A record with this unique identifier already exists.";
                }
            } else {
                $message = "Error adding doctor: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Add New Doctor Account</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Doctor Details</h4>
            </div>
            <div class="card-body">
                <form action="add_doctor.php" method="POST">
                    <h5 class="mb-3">Login Credentials</h5>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <hr class="mt-4 mb-4">
                    <h5 class="mb-3">Personal Information</h5>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" required value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                    </div>

                    <hr class="mt-4 mb-4">
                    <h5 class="mb-3">Professional Information</h5>
                    <div class="mb-3">
                        <label for="specialty" class="form-label">Specialty</label>
                        <input type="text" class="form-control" id="specialty" name="specialty" required value="<?php echo htmlspecialchars($_POST['specialty'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="license_number" class="form-label">License Number</label>
                        <input type="text" class="form-control" id="license_number" name="license_number" required value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Add Doctor</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>