<?php
// admin/enter_lab_results.php
$title = "Enter Lab Results";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submission for entering lab results
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    $result_data = $_POST['result_data']; // We'll assume JSON for now
    $notes = trim($_POST['notes']);

    if ($order_id) {
        try {
            // Validate JSON
            json_decode($result_data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $message = "Invalid JSON format for result data.";
                $message_type = "danger";
            } else {
                $pdo->beginTransaction();

                // Insert into lab_results table
                $stmt = $pdo->prepare("INSERT INTO lab_results (order_id, result_data, notes) VALUES (?, ?, ?)");
                $stmt->execute([$order_id, $result_data, $notes]);

                // Update lab_orders status to 'completed'
                $stmt = $pdo->prepare("UPDATE lab_orders SET status = 'completed' WHERE id = ?");
                $stmt->execute([$order_id]);

                $pdo->commit();
                $message = "Lab results entered successfully!";
                $message_type = "success";
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error entering lab results: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Invalid order ID.";
        $message_type = "danger";
    }
}

// Fetch pending lab orders with patient and test details
$pending_orders = [];
try {
    $stmt = $pdo->prepare("SELECT lo.id, u.full_name AS patient_name, lt.test_name, lo.order_date
                          FROM lab_orders lo
                          JOIN patients p ON lo.patient_id = p.id
                          JOIN users u ON p.user_id = u.id -- ADD THIS LINE
                          JOIN lab_tests lt ON lo.test_id = lt.id
                          WHERE lo.status = 'pending'
                          ORDER BY lo.order_date ASC");
    $stmt->execute();
    $pending_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching pending lab orders: " . $e->getMessage();
    $message_type = "danger";
}

?>

<div class="row justify-content-center">
    <div class="col-md-11 col-lg-10">
        <h2 class="mb-4">Enter Lab Results</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Pending Lab Orders</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($pending_orders)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Patient</th>
                                    <th>Test</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['test_name']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($order['order_date']))); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#enterResultsModal_<?php echo htmlspecialchars($order['id']); ?>">
                                                Enter Results
                                            </button>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="enterResultsModal_<?php echo htmlspecialchars($order['id']); ?>" tabindex="-1" aria-labelledby="enterResultsModalLabel_<?php echo htmlspecialchars($order['id']); ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title" id="enterResultsModalLabel_<?php echo htmlspecialchars($order['id']); ?>">Enter Results for Order ID: <?php echo htmlspecialchars($order['id']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="enter_lab_results.php" method="POST">
                                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">

                                                        <div class="mb-3">
                                                            <label for="result_data_<?php echo htmlspecialchars($order['id']); ?>" class="form-label">Result Data (JSON)</label>
                                                            <textarea class="form-control" id="result_data_<?php echo htmlspecialchars($order['id']); ?>" name="result_data" rows="5" required></textarea>
                                                            <small class="text-muted">Enter results in JSON format. Example: <code>{"WBC": 7.5, "RBC": 4.8, "Hemoglobin": 14.2}</code></small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="notes_<?php echo htmlspecialchars($order['id']); ?>" class="form-label">Notes (Optional)</label>
                                                            <textarea class="form-control" id="notes_<?php echo htmlspecialchars($order['id']); ?>" name="notes" rows="3"></textarea>
                                                        </div>

                                                        <button type="submit" class="btn btn-info w-100">Submit Results</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No pending lab orders.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>