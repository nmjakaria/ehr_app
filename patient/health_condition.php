<?php
// patient/health_condition.php
$title = "Health Condition";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$patient_id = null; // Initialize patient_id

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


// Function to calculate BMI and return message
function calculateBMI($height_cm, $weight_kg)
{
    if ($height_cm <= 0 || $weight_kg <= 0) {
        return ['bmi' => 0, 'message' => 'Invalid input for BMI calculation.'];
    }
    $height_m = $height_cm / 100; // Convert cm to meters
    $bmi = $weight_kg / ($height_m * $height_m);
    $bmi = round($bmi, 2); // Round to 2 decimal places

    $message = '';
    if ($bmi < 18.5) {
        $message = 'Underweight';
    } elseif ($bmi >= 18.5 && $bmi < 24.9) {
        $message = 'Normal weight';
    } elseif ($bmi >= 25 && $bmi < 29.9) {
        $message = 'Overweight';
    } else {
        $message = 'Obesity';
    }
    return ['bmi' => $bmi, 'message' => $message];
}


// Handle form submission for health condition update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $height_cm = filter_var($_POST['height_cm'], FILTER_VALIDATE_FLOAT);
    $weight_kg = filter_var($_POST['weight_kg'], FILTER_VALIDATE_FLOAT);
    $blood_sugar_mgdl = filter_var($_POST['blood_sugar_mgdl'], FILTER_VALIDATE_FLOAT);
    $blood_pressure_systolic = filter_var($_POST['blood_pressure_systolic'], FILTER_VALIDATE_INT);
    $blood_pressure_diastolic = filter_var($_POST['blood_pressure_diastolic'], FILTER_VALIDATE_INT);

    // Validate inputs
    if (
        $height_cm === false || $weight_kg === false || $blood_sugar_mgdl === false || $blood_pressure_systolic === false || $blood_pressure_diastolic === false ||
        $height_cm <= 0 || $weight_kg <= 0 || $blood_sugar_mgdl < 0 || $blood_pressure_systolic <= 0 || $blood_pressure_diastolic <= 0
    ) {
        $message = "Please enter valid numeric values for all health metrics.";
        $message_type = "danger";
    } else {
        $bmi_calc = calculateBMI($height_cm, $weight_kg);
        $bmi_message = $bmi_calc['message'];

        try {
            $stmt = $pdo->prepare("INSERT INTO health_conditions (patient_id, height_cm, weight_kg, blood_sugar_mgdl, blood_pressure_systolic, blood_pressure_diastolic, bmi_message)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$patient_id, $height_cm, $weight_kg, $blood_sugar_mgdl, $blood_pressure_systolic, $blood_pressure_diastolic, $bmi_message]);

            $message = "Health condition updated successfully!";
            $message_type = "success";

            // Redirect to prevent form re-submission on refresh
            // header("Location: health_condition.php?success=1")

        } catch (PDOException $e) {
            $message = "Error updating health condition: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Display messages from redirection
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Health condition updated successfully!";
    $message_type = "success";
}

// Fetch current (latest) health condition
$current_health_condition = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM health_conditions WHERE patient_id = ? ORDER BY date_recorded DESC, created_at DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $current_health_condition = $stmt->fetch();
} catch (PDOException $e) {
    $message = "Error fetching current health condition: " . $e->getMessage();
    $message_type = "danger";
}

// Fetch all previous health conditions
$previous_health_conditions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM health_conditions WHERE patient_id = ? ORDER BY date_recorded DESC, created_at DESC");
    $stmt->execute([$patient_id]);
    $previous_health_conditions = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching previous health conditions: " . $e->getMessage();
    $message_type = "danger";
}

?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <h2 class="mb-4">My Health Condition</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Current Health Overview</h4>
                </div>
                <div class="card-body">
                    <?php if ($current_health_condition): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date Recorded:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($current_health_condition['date_recorded']))); ?></p>
                                <p><strong>Height:</strong> <?php echo htmlspecialchars($current_health_condition['height_cm']); ?> cm</p>
                                <p><strong>Weight:</strong> <?php echo htmlspecialchars($current_health_condition['weight_kg']); ?> kg</p>
                                <?php
                                $bmi_data = calculateBMI($current_health_condition['height_cm'], $current_health_condition['weight_kg']);
                                ?>
                                <p><strong>BMI:</strong> <?php echo htmlspecialchars($bmi_data['bmi']); ?> (<?php echo htmlspecialchars($bmi_data['message']); ?>)</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Blood Sugar:</strong> <?php echo htmlspecialchars($current_health_condition['blood_sugar_mgdl']); ?> mg/dL</p>
                                <p><strong>Blood Pressure:</strong> <?php echo htmlspecialchars($current_health_condition['blood_pressure_systolic']); ?>/<?php echo htmlspecialchars($current_health_condition['blood_pressure_diastolic']); ?> mmHg</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No health condition data recorded yet. Please use the form below to add your first entry.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Update Health Condition</h4>
                </div>
                <div class="card-body">
                    <form action="health_condition.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="height_cm" class="form-label">Height (cm)</label>
                                <input type="number" class="form-control" id="height_cm" name="height_cm" required value="<?php echo htmlspecialchars($current_health_condition['height_cm'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="weight_kg" class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" id="weight_kg" name="weight_kg" required value="<?php echo htmlspecialchars($current_health_condition['weight_kg'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="blood_sugar_mgdl" class="form-label">Blood Sugar (mg/dL)</label>
                                <input type="number" step="0.01" class="form-control" id="blood_sugar_mgdl" name="blood_sugar_mgdl" value="<?php echo htmlspecialchars($current_health_condition['blood_sugar_mgdl'] ?? ''); ?>">
                                <small class="form-text text-muted">e.g., 90 for fasting, 140 for post-meal</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="blood_pressure_systolic" class="form-label">Blood Pressure (Systolic - top number)</label>
                                <input type="number" class="form-control" id="blood_pressure_systolic" name="blood_pressure_systolic" value="<?php echo htmlspecialchars($current_health_condition['blood_pressure_systolic'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="blood_pressure_diastolic" class="form-label">Blood Pressure (Diastolic - bottom number)</label>
                            <input type="number" class="form-control" id="blood_pressure_diastolic" name="blood_pressure_diastolic" value="<?php echo htmlspecialchars($current_health_condition['blood_pressure_diastolic'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-success w-100">Record Health Condition</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Previous Health Records</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($previous_health_conditions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Height (cm)</th>
                                        <th>Weight (kg)</th>
                                        <th>BMI</th>
                                        <th>Blood Sugar (mg/dL)</th>
                                        <th>BP (mmHg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($previous_health_conditions as $record):
                                        $bmi_data = calculateBMI($record['height_cm'], $record['weight_kg']);
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['date_recorded']))); ?></td>
                                            <td><?php echo htmlspecialchars($record['height_cm']); ?></td>
                                            <td><?php echo htmlspecialchars($record['weight_kg']); ?></td>
                                            <td><?php echo htmlspecialchars($bmi_data['bmi']); ?> (<?php echo htmlspecialchars($bmi_data['message']); ?>)</td>
                                            <td><?php echo htmlspecialchars($record['blood_sugar_mgdl'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($record['blood_pressure_systolic'] ?? 'N/A'); ?>/<?php echo htmlspecialchars($record['blood_pressure_diastolic'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No previous health records found.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>