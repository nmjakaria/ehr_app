<?php
// admin/add_lab_test.php
$title = "Add New Lab Test";
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
    $test_name = trim($_POST['test_name']);
    $description = trim($_POST['description']);

    // Basic validation
    if (empty($test_name)) {
        $message = "Test Name is required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO lab_tests (test_name, description) VALUES (?, ?)");
            $stmt->execute([$test_name, $description]);

            $message = "Lab test '<strong>" . htmlspecialchars($test_name) . "</strong>' added successfully!";
            $message_type = "success";

            // Clear form fields on success
            $_POST = array();

        } catch (PDOException $e) {
            // Check for duplicate test name (UNIQUE constraint)
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'test_name') !== false) {
                $message = "Error: A lab test with this name already exists. Please choose a different name.";
            } else {
                $message = "Error adding lab test: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Add New Lab Test</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Lab Test Details</h4>
            </div>
            <div class="card-body">
                <form action="add_lab_test.php" method="POST">
                    <div class="mb-3">
                        <label for="test_name" class="form-label">Test Name</label>
                        <input type="text" class="form-control" id="test_name" name="test_name" required value="<?php echo htmlspecialchars($_POST['test_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Add Lab Test</button>
                    <a href="manage_lab_tests.php" class="btn btn-secondary w-100 mt-2">Back to Manage Lab Tests</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>