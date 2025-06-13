<?php
// admin/manage_lab_results.php
$title = "Manage Lab Orders & Results";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submission for updating lab order status and results
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $result_data = trim($_POST['result_data'] ?? '');
    $result_notes = trim($_POST['result_notes'] ?? '');

    // Basic validation
    if (!$order_id || !in_array($status, ['pending', 'completed', 'cancelled'])) {
        $message = "Invalid input for order update.";
        $message_type = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Update the lab_orders table status
            $stmt_order = $pdo->prepare("UPDATE lab_orders SET status = ? WHERE id = ?");
            $stmt_order->execute([$status, $order_id]);

            // 2. If status is 'completed' or 'cancelled', manage lab_results entry
            if ($status == 'completed') {
                // Check if a result already exists for this order
                $stmt_check_result = $pdo->prepare("SELECT id FROM lab_results WHERE order_id = ?");
                $stmt_check_result->execute([$order_id]);
                $existing_result = $stmt_check_result->fetch();

                if ($existing_result) {
                    // Update existing result
                    $stmt_result = $pdo->prepare("UPDATE lab_results SET result_data = ?, result_date = NOW(), notes = ? WHERE order_id = ?");
                    $stmt_result->execute([$result_data, $result_notes, $order_id]);
                } else {
                    // Insert new result
                    $stmt_result = $pdo->prepare("INSERT INTO lab_results (order_id, result_data, result_date, notes) VALUES (?, ?, NOW(), ?)");
                    $stmt_result->execute([$order_id, $result_data, $result_notes]);
                }
                $message = "Lab order ID " . $order_id . " marked as COMPLETED and results saved.";
                $message_type = "success";
            } elseif ($status == 'cancelled') {
                // If cancelled, remove any associated results (optional, but good for clean-up)
                $stmt_delete_result = $pdo->prepare("DELETE FROM lab_results WHERE order_id = ?");
                $stmt_delete_result->execute([$order_id]);
                $message = "Lab order ID " . $order_id . " marked as CANCELLED.";
                $message_type = "warning";
            } else {
                // If status is pending again or unknown, just update order status, no result changes
                $message = "Lab order ID " . $order_id . " status updated to PENDING.";
                $message_type = "info";
            }

            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Database error updating lab order: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Fetch all lab orders with patient and doctor details
$lab_orders = [];
try {
    $stmt = $pdo->query("SELECT
                            lo.id AS order_id,
                            lo.order_date,
                            lo.status,
                            lo.notes AS order_notes,
                            p_u.full_name AS patient_name,
                            p_u.username AS patient_username,
                            d_u.full_name AS doctor_name,
                            lt.test_name,
                            lr.result_data,
                            lr.result_date,
                            lr.notes AS result_notes
                        FROM
                            lab_orders lo
                        JOIN
                            patients p ON lo.patient_id = p.id
                        JOIN
                            users p_u ON p.user_id = p_u.id
                        JOIN
                            doctors d ON lo.doctor_id = d.id
                        JOIN
                            users d_u ON d.user_id = d_u.id
                        JOIN
                            lab_tests lt ON lo.test_id = lt.id
                        LEFT JOIN
                            lab_results lr ON lo.id = lr.order_id
                        ORDER BY
                            lo.order_date DESC");
    $lab_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching lab orders: " . $e->getMessage();
    $message_type = "danger";
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-11 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Manage Lab Orders & Results</h2>
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

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">All Lab Orders</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($lab_orders)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped caption-top">
                            <caption>List of all lab orders. Click "Manage" to update status or add results.</caption>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Patient</th>
                                    <th>Test Name</th>
                                    <th>Ordered By</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lab_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['patient_name']); ?> (<?php echo htmlspecialchars($order['patient_username']); ?>)</td>
                                        <td><?php echo htmlspecialchars($order['test_name']); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($order['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($order['order_date']))); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($order['status']) {
                                                case 'pending':
                                                    $status_class = 'badge bg-warning text-dark';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'badge bg-success';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'badge bg-danger';
                                                    break;
                                                default:
                                                    $status_class = 'badge bg-secondary';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#manageOrderModal_<?php echo htmlspecialchars($order['order_id']); ?>">
                                                Manage
                                            </button>

                                            <div class="modal fade" id="manageOrderModal_<?php echo htmlspecialchars($order['order_id']); ?>" tabindex="-1" aria-labelledby="manageOrderModalLabel_<?php echo htmlspecialchars($order['order_id']); ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-info text-white">
                                                            <h5 class="modal-title" id="manageOrderModalLabel_<?php echo htmlspecialchars($order['order_id']); ?>">Manage Lab Order #<?php echo htmlspecialchars($order['order_id']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="POST">
                                                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                                            <input type="hidden" name="update_order" value="1">
                                                            <div class="modal-body">
                                                                <p><strong>Patient:</strong> <?php echo htmlspecialchars($order['patient_name']); ?> (<?php echo htmlspecialchars($order['patient_username']); ?>)</p>
                                                                <p><strong>Test:</strong> <?php echo htmlspecialchars($order['test_name']); ?></p>
                                                                <p><strong>Ordered By:</strong> Dr. <?php echo htmlspecialchars($order['doctor_name']); ?> on <?php echo htmlspecialchars(date('M d, Y', strtotime($order['order_date']))); ?></p>
                                                                <?php if (!empty($order['order_notes'])): ?>
                                                                    <p><strong>Doctor's Notes:</strong> <?php echo nl2br(htmlspecialchars($order['order_notes'])); ?></p>
                                                                <?php endif; ?>

                                                                <hr>

                                                                <div class="mb-3">
                                                                    <label for="status_<?php echo htmlspecialchars($order['order_id']); ?>" class="form-label">Update Status</label>
                                                                    <select class="form-select" id="status_<?php echo htmlspecialchars($order['order_id']); ?>" name="status" required>
                                                                        <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                                        <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="result_data_<?php echo htmlspecialchars($order['order_id']); ?>" class="form-label">Lab Results Data (e.g., JSON or plain text)</label>
                                                                    <textarea class="form-control" id="result_data_<?php echo htmlspecialchars($order['order_id']); ?>" name="result_data" rows="5"><?php echo htmlspecialchars($order['result_data'] ?? ''); ?></textarea>
                                                                    <small class="form-text text-muted">For structured data, you can use JSON format (e.g., {"RBC": "4.5", "WBC": "7.2"}).</small>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="result_notes_<?php echo htmlspecialchars($order['order_id']); ?>" class="form-label">Notes on Results (Optional)</label>
                                                                    <textarea class="form-control" id="result_notes_<?php echo htmlspecialchars($order['order_id']); ?>" name="result_notes" rows="3"><?php echo htmlspecialchars($order['result_notes'] ?? ''); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No lab orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>