<?php
// patient/upload_lab_report.php
$title = "Upload Lab Report";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$patient_id = null;
$message = '';
$message_type = '';

// Fetch patient_id from the patients table
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $patient_data = $stmt->fetch();
    if ($patient_data) {
        $patient_id = $patient_data['id'];
    } else {
        die("Patient record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching patient ID: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $patient_id) {
    $test_name = trim($_POST['test_name'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $image_path = null;

    // Validate inputs
    if (empty($test_name)) {
        $message = "Test name is required.";
        $message_type = "danger";
    } else {
        // Handle file upload
        if (isset($_FILES['lab_report_image']) && $_FILES['lab_report_image']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "../uploads/lab_reports/"; // Directory to store uploaded images
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
            }

            $file_name = uniqid('lab_') . '_' . basename($_FILES['lab_report_image']['name']);
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if image file is an actual image or fake image
            $check = getimagesize($_FILES['lab_report_image']['tmp_name']);
            if ($check !== false) {
                 // Allow certain file formats
                if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "pdf") { // Allow PDF for documents
                    $message = "Sorry, only JPG, JPEG, PNG, GIF & PDF files are allowed.";
                    $message_type = "danger";
                } else {
                    // Check file size (e.g., max 5MB)
                    if ($_FILES['lab_report_image']['size'] > 5000000) {
                        $message = "Sorry, your file is too large (max 5MB).";
                        $message_type = "danger";
                    } else {
                        if (move_uploaded_file($_FILES['lab_report_image']['tmp_name'], $target_file)) {
                            $image_path = 'uploads/lab_reports/' . $file_name; // Path to store in DB
                        } else {
                            $message = "Sorry, there was an error uploading your file.";
                            $message_type = "danger";
                        }
                    }
                }
            } else {
                $message = "File is not an image or PDF.";
                $message_type = "danger";
            }
        } elseif (isset($_FILES['lab_report_image']) && $_FILES['lab_report_image']['error'] != UPLOAD_ERR_NO_FILE) {
            // Handle other upload errors
            $message = "File upload error: " . $_FILES['lab_report_image']['error'];
            $message_type = "danger";
        }

        // If no file upload error, proceed with saving to DB
        if ($message_type !== "danger") {
            try {
                $stmt = $pdo->prepare("INSERT INTO patient_uploaded_lab_reports (patient_id, test_name, notes, image_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$patient_id, $test_name, $notes, $image_path]);

                $message = "Lab report added successfully!";
                $message_type = "success";
                // Optionally clear form fields or redirect
                // header("Location: view_lab_results.php"); exit();
            } catch (PDOException $e) {
                $message = "Error adding lab report: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}
?>
<div class="row justify-content-center mt-4">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Upload Your Lab Report</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                Add New Lab Report
            </div>
            <div class="card-body">
                <form action="upload_lab_report.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="test_name" class="form-label">Test Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="test_name" name="test_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes/Comments</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="lab_report_image" class="form-label">Upload Lab Report Image/Document (JPG, PNG, GIF, PDF - Max 5MB)</label>
                        <input type="file" class="form-control" id="lab_report_image" name="lab_report_image" accept="image/*,.pdf">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Lab Report</button>
                    <a href="view_lab_results.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>