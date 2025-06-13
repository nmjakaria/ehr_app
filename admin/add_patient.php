<?php
// admin/add_patient.php
$title = "Add New Patient";
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

    // Patient specific details
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];

    // Basic validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($mobile) ||
        empty($date_of_birth) || empty($gender) || empty($blood_group)) {
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
    } elseif (!DateTime::createFromFormat('Y-m-d', $date_of_birth)) {
        $message = "Invalid date of birth format.";
        $message_type = "danger";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_type = 'patient'; // Fixed type for this page

        try {
            $pdo->beginTransaction();

            // 1. Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, mobile, user_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $email, $mobile, $user_type]);
            $new_user_id = $pdo->lastInsertId();

            // 2. Insert into patients table
            $stmt = $pdo->prepare("INSERT INTO patients (user_id, date_of_birth, gender, blood_group) VALUES (?, ?, ?, ?)");
            $stmt->execute([$new_user_id, $date_of_birth, $gender, $blood_group]);

            $pdo->commit();
            $message = "Patient account added successfully!";
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
                $message = "Error adding patient: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Add New Patient Account</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Patient Details</h4>
            </div>
            <div class="card-body">
                <form action="add_patient.php" method="POST">
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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">-- Select --</option>
                                <option value="Male" <?php echo (($_POST['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (($_POST['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (($_POST['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
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

                    <button type="submit" class="btn btn-success w-100">Add Patient</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>