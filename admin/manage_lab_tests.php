<?php
// admin/manage_lab_tests.php
$title = "Manage Lab Tests";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $test_id_to_delete = filter_var($_POST['test_id'], FILTER_VALIDATE_INT);

    if ($test_id_to_delete) {
        try {
            // Attempt to delete the test
            $stmt = $pdo->prepare("DELETE FROM lab_tests WHERE id = ?");
            $stmt->execute([$test_id_to_delete]);

            if ($stmt->rowCount() > 0) {
                $message = "Lab test deleted successfully.";
                $message_type = "success";
            } else {
                $message = "Failed to delete lab test or test not found.";
                $message_type = "warning";
            }
        } catch (PDOException $e) {
            // Check for foreign key constraint violation
            if ($e->getCode() == '23000') { // SQLSTATE for integrity constraint violation
                $message = "Error: Cannot delete lab test because there are existing lab orders linked to it. You must delete linked orders first.";
            } else {
                $message = "Error deleting lab test: " . $e->getMessage();
            }
            $message_type = "danger";
        }
    } else {
        $message = "Invalid lab test ID provided.";
        $message_type = "danger";
    }
}

// Fetch all lab tests
$lab_tests = [];
try {
    $stmt = $pdo->query("SELECT id, test_name, description, created_at FROM lab_tests ORDER BY test_name ASC");
    $lab_tests = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching lab tests: " . $e->getMessage();
    $message_type = "danger";
}

?>

<div class="row justify-content-center mt-4">
    <div class="col-md-10 col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Manage Lab Test</h2>
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left me-2"></i> Back
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end mb-3">
            <a href="add_lab_test.php" class="btn btn-primary"><i class="fas fa-plus-circle me-2"></i> Add New Lab Test</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Available Lab Tests</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($lab_tests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Test Name</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lab_tests as $test): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                        <td><?php echo htmlspecialchars($test['description']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($test['created_at']))); ?></td>
                                        <td>
                                            <a href="edit_lab_test.php?test_id=<?php echo htmlspecialchars($test['id']); ?>" class="btn btn-sm btn-warning me-1" title="Edit Test"><i class="fas fa-edit"></i></a>
                                            <form action="manage_lab_tests.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete the lab test \'<?php echo htmlspecialchars($test['test_name']); ?>\'? This action cannot be undone if there are no associated orders.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($test['id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Test"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No lab tests defined yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>