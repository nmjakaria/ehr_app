<?php
// patient/login.php
$title = "Patient Login";
require_once '../includes/db_connection.php';
require_once '../includes/header.php'; // header.php starts session_start()

$message = '';
$message_type = '';

// Check if already logged in as a patient
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'patient') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Email and password are required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.password FROM users u WHERE u.email = ? AND u.user_type = 'patient'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = 'patient';
                header("Location: dashboard.php"); // Redirect to patient dashboard
                exit();
            } else {
                $message = "Invalid email or password.";
                $message_type = "danger";
            }
        } catch (PDOException $e) {
            $message = "Login error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-success text-white text-center">
                <h3 class="mb-0">Patient Login</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['signup_success']) && $_GET['signup_success'] == 1): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Account created successfully! Please log in.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Login</button>
                </form>
                <div class="text-center mt-3">
                    Don't have an account? <a href="signup.php">Sign Up here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>