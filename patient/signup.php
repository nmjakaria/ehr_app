<?php
// patient/signup.php
$title = "Patient Sign Up";
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
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = trim($_POST['gender']);
    $blood_group = trim($_POST['blood_group']);

    // Basic server-side validation
    if (empty($full_name) || empty($email) || empty($password) || empty($mobile) || empty($date_of_birth) || empty($gender) || empty($blood_group)) {
        $message = "All fields are required.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "danger";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $stmt_user = $pdo->prepare("INSERT INTO users (full_name, email, username, password, mobile, user_type) VALUES (?, ?, ?, ?, ?, 'patient')");
            $stmt_user->execute([$full_name, $email, $username, $hashed_password, $mobile]);
            $user_id = $pdo->lastInsertId();

            $stmt_patient = $pdo->prepare("INSERT INTO patients (user_id, date_of_birth, gender, blood_group) VALUES (?, ?, ?, ?)");
            $stmt_patient->execute([$user_id, $date_of_birth, $gender, $blood_group]);

            $pdo->commit();

            $message = "Patient account created successfully! You can now log in.";
            $message_type = "success";

            // header("Location: login.php?signup_success=1");
            // exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
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
            <div class="card-header bg-success text-white text-center">
                <h3 class="mb-0">Patient Sign Up</h3>
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
                        <label for="username" class="form-label">Username</label>
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
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (($_POST['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($_POST['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($_POST['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="blood_group" class="form-label">Blood Group</label>
                        <select class="form-select" id="blood_group" name="blood_group" required>
                            <option value="">-- Select --</option>
                            <option value="A+" <?php echo (($_POST['blood_group'] ?? '') == 'A+') ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo (($_POST['blood_group'] ?? '') == 'A-') ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo (($_POST['blood_group'] ?? '') == 'B+') ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo (($_POST['blood_group'] ?? '') == 'B-') ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo (($_POST['blood_group'] ?? '') == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo (($_POST['blood_group'] ?? '') == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+" <?php echo (($_POST['blood_group'] ?? '') == 'O+') ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo (($_POST['blood_group'] ?? '') == 'O-') ? 'selected' : ''; ?>>O-</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Sign Up</button>
                </form>
                <div class="text-center mt-3">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>