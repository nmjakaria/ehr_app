<?php
// doctor/add_allergy.php
$title = "Add Allergy";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: ../login.php");
    exit();
}

$doctor_user_id = $_SESSION['user_id'];
$doctor_id = null;
$message = '';
$message_type = '';

// Fetch doctor_id from the doctors table
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$doctor_user_id]);
    $doctor_data = $stmt->fetch();
    if ($doctor_data) {
        $doctor_id = $doctor_data['id'];
    } else {
        die("Doctor record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching doctor ID: " . $e->getMessage());
}

// Get patient_id from GET request
$patient_id = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT);

// Redirect if no patient_id is provided
if (!$patient_id) {
    header("Location: my_patients.php?message=" . urlencode("No patient selected to add allergy.") . "&message_type=danger");
    exit();
}

// Fetch patient's full name for display
$patient_full_name = 'N/A';
try {
    $stmt = $pdo->prepare("SELECT full_name FROM users u JOIN patients p ON u.id = p.user_id WHERE p.id = ?");
    $stmt->execute([$patient_id]);
    $patient_name_data = $stmt->fetch();
    if ($patient_name_data) {
        $patient_full_name = $patient_name_data['full_name'];
    } else {
        header("Location: my_patients.php?message=" . urlencode("Patient not found.") . "&message_type=danger");
        exit();
    }
} catch (PDOException $e) {
    $message = "Error fetching patient name: " . $e->getMessage();
    $message_type = "danger";
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $allergen_name = trim($_POST['allergen_name']);
    $reaction = trim($_POST['reaction']);
    $severity = trim($_POST['severity']);
    $diagnosis_date = trim($_POST['diagnosis_date']);
    $notes = trim($_POST['notes']);

    // Basic validation
    if (empty($allergen_name) || empty($reaction)) {
        $message = "Allergen Name and Reaction are required.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO allergies (patient_id, doctor_id, allergen_name, reaction, severity, diagnosis_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$patient_id, $doctor_id, $allergen_name, $reaction, $severity, $diagnosis_date, $notes]);

            $message = "Allergy to '<strong>" . htmlspecialchars($allergen_name) . "</strong>' added successfully for " . htmlspecialchars($patient_full_name) . "!";
            $message_type = "success";

            // Clear form fields on success
            $_POST = array(); // Clear for fresh input

        } catch (PDOException $e) {
            $message = "Error adding allergy: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h2 class="mb-4">Add Allergy for <?php echo htmlspecialchars($patient_full_name); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Allergy Details</h4>
            </div>
            <div class="card-body">
                <form action="add_allergy.php?patient_id=<?php echo htmlspecialchars($patient_id); ?>" method="POST">
                    <div class="mb-3">
                        <label for="allergen_name" class="form-label">Allergen Name</label>
                        <input type="text" class="form-control" id="allergen_name" name="allergen_name" required value="<?php echo htmlspecialchars($_POST['allergen_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="reaction" class="form-label">Reaction</label>
                        <textarea class="form-control" id="reaction" name="reaction" rows="3" required><?php echo htmlspecialchars($_POST['reaction'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="severity" class="form-label">Severity</label>
                        <select class="form-select" id="severity" name="severity" required>
                            <option value="mild" <?php echo (($_POST['severity'] ?? 'mild') == 'mild') ? 'selected' : ''; ?>>Mild</option>
                            <option value="moderate" <?php echo (($_POST['severity'] ?? '') == 'moderate') ? 'selected' : ''; ?>>Moderate</option>
                            <option value="severe" <?php echo (($_POST['severity'] ?? '') == 'severe') ? 'selected' : ''; ?>>Severe</option>
                            <option value="life-threatening" <?php echo (($_POST['severity'] ?? '') == 'life-threatening') ? 'selected' : ''; ?>>Life-Threatening</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="diagnosis_date" class="form-label">Diagnosis Date (Optional)</label>
                        <input type="date" class="form-control" id="diagnosis_date" name="diagnosis_date" value="<?php echo htmlspecialchars($_POST['diagnosis_date'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Add Allergy</button>
                    <a href="patient_dashboard.php?patient_id=<?php echo htmlspecialchars($patient_id); ?>" class="btn btn-secondary w-100 mt-2">Back to Patient Dashboard</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>