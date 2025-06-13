<?php
// admin/edit_lab_test.php
$title = "Edit Lab Test";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';
$lab_test_data = null; // To store fetched lab test details

// Get test_id from GET request
$test_id_to_edit = filter_var($_GET['test_id'] ?? null, FILTER_VALIDATE_INT);

// If no test_id is provided, redirect back to manage lab tests
if (!$test_id_to_edit) {
    header("Location: manage_lab_tests.php?message=" . urlencode("No lab test selected for editing.") . "&message_type=danger");
    exit();
}

// Fetch existing lab test data
try {
    $stmt = $pdo->prepare("SELECT id, test_name, description FROM lab_tests WHERE id = ?");
    $stmt->execute([$test_id_to_edit]);
    $lab_test_data = $stmt->fetch();

    if (!$lab_test_data) {
        header("Location: manage_lab_tests.php?message=" . urlencode("Lab test not found.") . "&message_type=danger");
        exit();
    }
} catch (PDOException $e) {
    $message = "Error fetching lab test data: " . $e->getMessage();
    $message_type = "danger";
}

// Handle form submission for updating lab test
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $lab_test_data) {
    $test_name = trim($_POST['test_name']);
    $description = trim($_POST['description']);

    // Basic validation
    if (empty($test_name)) {
        $message = "Test Name is required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE lab_tests SET test_name = ?, description = ? WHERE id = ?");
            $stmt->execute([$test_name, $description, $test_id_to_edit]);

            $message = "Lab test '<strong>" . htmlspecialchars($test_name) . "</strong>' updated successfully!";
            $message_type = "success";

            // Refresh the fetched data to show updated values immediately
            $stmt = $pdo->prepare("SELECT id, test_name, description FROM lab_tests WHERE id = ?");
            $stmt->execute([$test_id_to_edit]);
            $lab_test_data = $stmt->fetch();

        } catch (PDOException $e) {
            // Check for duplicate test name (UNIQUE constraint)
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'test_name') !== false) {
                $message = "Error: A lab test with this name already exists. Please choose a different name.";
            } else {
                $message = "Error updating lab test: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    }
}

?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Edit Lab Test: <?php echo htmlspecialchars($lab_test_data['test_name'] ?? ''); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Update Lab Test Details</h4>
            </div>
            <div class="card-body">
                <form action="edit_lab_test.php?test_id=<?php echo htmlspecialchars($test_id_to_edit); ?>" method="POST">
                    <div class="mb-3">
                        <label for="test_name" class="form-label">Test Name</label>
                        <input type="text" class="form-control" id="test_name" name="test_name" required value="<?php echo htmlspecialchars($_POST['test_name'] ?? $lab_test_data['test_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $lab_test_data['description'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Update Lab Test</button>
                    <a href="manage_lab_tests.php" class="btn btn-secondary w-100 mt-2">Back to Manage Lab Tests</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>