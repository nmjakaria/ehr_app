<?php
// doctor/signup.php
$title = "Doctor Sign Up";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $mobile = trim($_POST['mobile']);
    $designation = trim($_POST['designation']);
    $chamber_location = trim($_POST['chamber_location']);
    $education_qualification = trim($_POST['education_qualification']);
    $specialty = trim($_POST['specialty']);
    $license_number = trim($_POST['license_number']);

    // Basic server-side validation
    if (empty($full_name) || empty($email) || empty($password) || empty($mobile) || empty($designation) || empty($chamber_location) || empty($education_qualification) || empty($specialty)) {
        $message = "All fields are required.";
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

        try {
            // Start a transaction
            $pdo->beginTransaction();

            // 1. Insert into users table
            $stmt_user = $pdo->prepare("INSERT INTO users (full_name, email, username, password, mobile, user_type) VALUES (?, ?, ?, ?, ?, 'doctor')");
            $stmt_user->execute([$full_name, $email, $username, $hashed_password, $mobile]);
            $user_id = $pdo->lastInsertId();

            // 2. Insert into doctors table
            $stmt_doctor = $pdo->prepare("INSERT INTO doctors (user_id, designation, chamber_location, education_qualification, specialty, license_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_doctor->execute([$user_id, $designation, $chamber_location, $education_qualification, $specialty, $license_number]);

            $pdo->commit(); // Commit the transaction if all is well

            $message = "Doctor account created successfully! You can now log in.";
            $message_type = "success";

            // Optional: Redirect to login page after successful signup
            // header("Location: login.php?signup_success=1");
            // exit();

        } catch (PDOException $e) {
            $pdo->rollBack(); // Rollback on error
            if ($e->getCode() == 23000) { // Duplicate entry error code
                $message = "Email already registered. Please use a different email or login.";
            } else {
                $message = "Error: Could not create account. Please try again. " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0">Doctor Sign Up</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form action="signup.php" method="POST">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">username</label>
                        <input type="username" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="mobile" class="form-label">Mobile</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" required value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="designation" class="form-label">Designation</label>
                        <input type="text" class="form-control" id="designation" name="designation" required value="<?php echo htmlspecialchars($_POST['designation'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="chamber_location" class="form-label">Chamber Location</label>
                        <textarea class="form-control" id="chamber_location" name="chamber_location" rows="3" required><?php echo htmlspecialchars($_POST['chamber_location'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="education_qualification" class="form-label">Education Qualification (e.g., MBBS, FCPS)</label>
                        <input type="text" class="form-control" id="education_qualification" name="education_qualification" required value="<?php echo htmlspecialchars($_POST['education_qualification'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="specialty" class="form-label">Specialty (e.g., Cardiology, Pediatrics)</label>
                        <input type="text" class="form-control" id="specialty" name="specialty" required value="<?php echo htmlspecialchars($_POST['specialty'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="license_number" class="form-label">License Number</label>
                        <input type="text" class="form-control" id="license_number" name="license_number" required value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                </form>
                <div class="text-center mt-3">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>