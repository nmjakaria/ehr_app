<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// doctor/access_patient_data.php
$title = "Access Patient Data";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Check if the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';
$patient_data_retrieved = false;
$patient_info = null;
$health_conditions = [];
$prescriptions = [];
$lab_orders = []; // To store lab reports
$patient_id_for_prescription = null; // To pass to the new prescription page

// Handle token submission via GET or POST
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!empty($token)) {
    try {
        // Validate the token: check if it exists, is linked to a patient, and is not expired
        $stmt = $pdo->prepare("SELECT pat.patient_id, pat.expires_at, u.full_name AS patient_full_name,
                                      u.email, u.mobile, p.date_of_birth, p.gender, p.blood_group
                               FROM qr_access_tokens pat  -- Changed to qr_access_tokens
                               JOIN patients p ON pat.patient_id = p.id
                               JOIN users u ON p.user_id = u.id
                               WHERE pat.token = ? "); //AND pat.expires_at > NOW() AND pat.is_used = True"
        $stmt->execute([$token]);
        $access_data = $stmt->fetch();

        if ($access_data) {
            $patient_id_for_prescription = $access_data['patient_id'];
            $patient_data_retrieved = true;
            $patient_info = $access_data; // Contains full_name, email, dob, etc.

            // Fetch patient's health conditions
            $stmt_health = $pdo->prepare("SELECT * FROM health_conditions WHERE patient_id = ? ORDER BY date_recorded DESC LIMIT 5"); // Last 5 entries
            $stmt_health->execute([$patient_id_for_prescription]);
            $health_conditions = $stmt_health->fetchAll();

            // Fetch patient's prescriptions
            $stmt_presc = $pdo->prepare("SELECT pr.id, pr.diagnosis, pr.medications, pr.instructions, pr.prescription_date, u.full_name AS doctor_name, d.specialty
                                         FROM prescriptions pr
                                         JOIN doctors d ON pr.doctor_id = d.id
                                         JOIN users u ON d.user_id = u.id
                                         WHERE pr.patient_id = ?
                                         ORDER BY pr.prescription_date DESC LIMIT 5"); // Last 5 entries
            $stmt_presc->execute([$patient_id_for_prescription]);
            $prescriptions = $stmt_presc->fetchAll();

            // Fetch lab reports
            $stmt_lab = $pdo->prepare("SELECT
                                        lo.id AS order_id,
                                        lt.test_name,
                                        lo.order_date,
                                        lo.status,
                                        lr.result_data,
                                        lr.result_date,
                                        lr.notes AS result_notes,
                                        d_u.full_name AS doctor_name
                                    FROM
                                        lab_orders lo
                                    JOIN
                                        lab_tests lt ON lo.test_id = lt.id
                                    JOIN
                                        doctors d ON lo.doctor_id = d.id
                                    JOIN
                                        users d_u ON d.user_id = d_u.id
                                    LEFT JOIN
                                        lab_results lr ON lo.id = lr.order_id
                                    WHERE
                                        lo.patient_id = ?
                                    ORDER BY
                                        lo.order_date DESC");
            $stmt_lab->execute([$patient_id_for_prescription]);
            $lab_orders = $stmt_lab->fetchAll(PDO::FETCH_ASSOC);

            // Mark the token as used
            $stmt_used = $pdo->prepare("UPDATE qr_access_tokens SET is_used = TRUE WHERE token = ?");
            $stmt_used->execute([$token]);

            $message = "Patient data accessed successfully! This token was valid until " . (new DateTime($access_data['expires_at']))->format('M d, Y h:i A') . ".";
            $message_type = "success";
        } else {
            $message = "Invalid or expired access token. Please ask the patient to generate a new one.";
            $message_type = "danger";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = "danger";
    }
} elseif (isset($_POST['submit_token'])) {
    $message = "Please enter a token to access patient data.";
    $message_type = "danger";
}

// Function to calculate BMI (repeated for self-containment, but could be in an includes file)
function calculateBMI($height_cm, $weight_kg)
{
    if ($height_cm <= 0 || $weight_kg <= 0) {
        return ['bmi' => 0, 'message' => 'Invalid input.'];
    }
    $height_m = $height_cm / 100;
    $bmi = $weight_kg / ($height_m * $height_m);
    $bmi = round($bmi, 2);

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

?>

<div class="row justify-content-center mt-5">
    <div class="col-md-10 col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Access Patient Data</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Access Patient Data</h4>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="accessMethodTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="scan-tab" data-bs-toggle="tab" data-bs-target="#scan" type="button" role="tab" aria-controls="scan" aria-selected="true">Scan QR Code</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab" aria-controls="manual" aria-selected="false">Enter Manually</button>
                    </li>
                </ul>
                <div class="tab-content" id="accessMethodTabContent">
                    <div class="tab-pane fade show active" id="scan" role="tabpanel" aria-labelledby="scan-tab">
                        <div id="qr-reader" style="width:100%"></div>
                        <div id="qr-reader-results"></div>
                        <p class="text-muted mt-3">Grant camera access to scan the patient's QR code.</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary mt-2" id="startScannerBtn">Start Scanner</button>
                            <button class="btn btn-danger mt-2" id="stopScannerBtn" style="display:none;">Stop Scanner</button>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                        <form action="access_patient_data.php" method="POST" id="manualAccessForm" target="_blank">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="token" id="manualTokenInput" placeholder="Paste access token or URL here" required value="<?php echo htmlspecialchars($token); ?>">
                                <button class="btn btn-info" type="submit" name="submit_token">Access Data</button>
                            </div>
                            <small class="form-text text-muted">Ask the patient to generate and provide the access token or the full link.</small>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($patient_data_retrieved): ?>
            <div class="card shadow-sm mb-4 mt-5">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Patient Profile: <?php echo htmlspecialchars($patient_info['patient_full_name']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info['email']); ?></p>
                            <p><strong>Mobile:</strong> <?php echo htmlspecialchars($patient_info['mobile']); ?></p>
                            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($patient_info['date_of_birth']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient_info['gender']); ?></p>
                            <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient_info['blood_group']); ?></p>
                        </div>
                    </div>
                    <a href="give_prescription_registered.php?patient_id=<?php echo htmlspecialchars($patient_id_for_prescription); ?>" class="btn btn-primary mt-3">
                        <i class="fas fa-plus-circle me-2"></i> Give New Prescription for <?php echo htmlspecialchars($patient_info['patient_full_name']); ?>
                    </a>
                    <a href="order_lab_test.php?patient_id=<?php echo htmlspecialchars($patient_id_for_prescription); ?>" class="btn btn-warning mt-3 ms-2">
                        <i class="fas fa-microscope me-2"></i> Order Lab Test for <?php echo htmlspecialchars($patient_info['patient_full_name']); ?>
                    </a>
                </div>
            </div>

            <div class="card shadow-sm mb-4 mt-4">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Recent Health Conditions</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($health_conditions)): ?>
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
                                    <?php foreach ($health_conditions as $record):
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
                        <p class="text-muted">No recent health condition data available for this patient.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4 mt-4">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">Recent Prescriptions</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($prescriptions)): ?>
                        <?php foreach ($prescriptions as $prescription): ?>
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Prescription #<?php echo htmlspecialchars($prescription['id']); ?>
                                        <small class="float-end">Issued: <?php echo htmlspecialchars(date('M d, Y', strtotime($prescription['prescription_date']))); ?> by Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?> (<?php echo htmlspecialchars($prescription['specialty']); ?>)</small>
                                    </h6>
                                    <p class="card-text"><strong>Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($prescription['diagnosis'])); ?></p>
                                    <strong>Medications:</strong>
                                    <ul class="list-group list-group-flush mb-2">
                                        <?php
                                        $medications = json_decode($prescription['medications'], true);
                                        if (is_array($medications)):
                                            foreach ($medications as $med): ?>
                                                <li class="list-group-item bg-light d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($med['name'] ?? ''); ?></strong>
                                                        <?php if (!empty($med['dosage'])): ?>
                                                            <small class="text-muted ms-2">(<?php echo htmlspecialchars($med['dosage']); ?>)</small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="badge bg-info rounded-pill"><?php echo htmlspecialchars($med['frequency'] ?? ''); ?></span>
                                                </li>
                                            <?php endforeach;
                                        else: ?>
                                            <li class="list-group-item bg-light text-muted">No specific medications listed.</li>
                                        <?php endif; ?>
                                    </ul>
                                    <p class="card-text"><strong>Instructions:</strong> <?php echo nl2br(htmlspecialchars($prescription['instructions'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No recent prescriptions available for this patient.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4 mt-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Lab Reports</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($lab_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Test Name</th>
                                        <th>Ordered By</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                        <th>Results</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lab_orders as $order): ?>
                                        <tr>
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
                                                <?php if ($order['status'] == 'completed' && !empty($order['result_data'])): ?>
                                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewResultsModal_<?php echo htmlspecialchars($order['order_id']); ?>">
                                                        View Results
                                                    </button>

                                                    <div class="modal fade" id="viewResultsModal_<?php echo htmlspecialchars($order['order_id']); ?>" tabindex="-1" aria-labelledby="viewResultsModalLabel_<?php echo htmlspecialchars($order['order_id']); ?>" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title" id="viewResultsModalLabel_<?php echo htmlspecialchars($order['order_id']); ?>">Lab Results for <?php echo htmlspecialchars($order['test_name']); ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p><strong>Test:</strong> <?php echo htmlspecialchars($order['test_name']); ?></p>
                                                                    <p><strong>Ordered Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($order['order_date']))); ?></p>
                                                                    <?php if (!empty($order['result_date'])): ?>
                                                                        <p><strong>Result Date:</strong> <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($order['result_date']))); ?></p>
                                                                    <?php endif; ?>
                                                                    <p><strong>Ordered By:</strong> Dr. <?php echo htmlspecialchars($order['doctor_name']); ?></p>

                                                                    <h6>Result Data:</h6>
                                                                    <pre class="bg-light p-3 rounded"><code><?php
                                                                                                            // Attempt to pretty print JSON, otherwise display raw
                                                                                                            $decoded_results = json_decode($order['result_data'], true);
                                                                                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                                                                                echo htmlspecialchars(json_encode($decoded_results, JSON_PRETTY_PRINT));
                                                                                                            } else {
                                                                                                                echo htmlspecialchars($order['result_data']); // Fallback if not valid JSON
                                                                                                            }
                                                                                                            ?></code></pre>

                                                                    <?php if (!empty($order['result_notes'])): ?>
                                                                        <h6>Notes:</h6>
                                                                        <p><?php echo nl2br(htmlspecialchars($order['result_notes'])); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php elseif ($order['status'] == 'pending'): ?>
                                                    <span class="text-muted">Results Pending</span>
                                                <?php else: ?>
                                                    <span class="text-danger">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No lab orders found for this patient.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const qrCodeScannerDiv = document.getElementById('qr-reader');
        const qrCodeResultDiv = document.getElementById('qr-reader-results');
        const startScannerBtn = document.getElementById('startScannerBtn');
        const stopScannerBtn = document.getElementById('stopScannerBtn');
        const manualTokenInput = document.getElementById('manualTokenInput');
        const manualAccessForm = document.getElementById('manualAccessForm');

        let html5QrCode = null;

        function onScanSuccess(decodedText, decodedResult) {
            // Handle the scanned code, e.g., send it to your server
            console.log(`Code matched = ${decodedText}`, decodedResult);
            qrCodeResultDiv.innerHTML = `Scanning successful! Processing data...`;
            qrCodeResultDiv.className = 'alert alert-success mt-2';

            // Attempt to extract token from URL, or use directly if it's just the token
            let token = '';
            try {
                const url = new URL(decodedText);
                const tokenParam = url.searchParams.get('token');
                if (tokenParam) {
                    token = tokenParam;
                }
            } catch (e) {
                // Not a URL, so maybe it's just the token string itself
                token = decodedText;
            }

            if (token) {
                manualTokenInput.value = token; // Populate the manual input field
                manualAccessForm.submit(); // Automatically submit the form
                // Stop the scanner after successful scan
                if (html5QrCode && html5QrCode.isscanning) {
                    html5QrCode.stop().then(() => {
                        console.log("QR Code scanner stopped.");
                        startScannerBtn.style.display = 'block';
                        stopScannerBtn.style.display = 'none';
                    }).catch(err => {
                        console.error("Error stopping QR Code scanner:", err);
                    });
                }
            } else {
                qrCodeResultDiv.innerHTML = `Scanned content is not a valid token or URL: ${decodedText}`;
                qrCodeResultDiv.className = 'alert alert-warning mt-2';
            }
        }

        function onScanError(errorMessage) {
            // console.warn(`QR Code scanning error = ${errorMessage}`);
            // You can update UI to show scanning status or errors
        }

        startScannerBtn.addEventListener('click', () => {
            // Ensure previous instance is stopped if any
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }

            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start({
                    facingMode: "environment"
                }, // Prefer rear camera
                {
                    fps: 10, // frames per second
                    qrbox: {
                        width: 250,
                        height: 250
                    } // Size of the QR scanning box
                },
                onScanSuccess,
                onScanError
            ).then(() => {
                qrCodeResultDiv.innerHTML = '<span class="text-info">Scanner started. Point your camera at the QR code.</span>';
                qrCodeResultDiv.className = 'alert alert-info mt-2';
                startScannerBtn.style.display = 'none';
                stopScannerBtn.style.display = 'block';
            }).catch(err => {
                qrCodeResultDiv.innerHTML = `<span class="text-danger">Error starting scanner: ${err}</span>`;
                qrCodeResultDiv.className = 'alert alert-danger mt-2';
                console.error("Error starting QR Code scanner:", err);
            });
        });

        stopScannerBtn.addEventListener('click', () => {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    qrCodeResultDiv.innerHTML = '<span class="text-muted">Scanner stopped.</span>';
                    qrCodeResultDiv.className = 'alert alert-secondary mt-2';
                    startScannerBtn.style.display = 'block';
                    stopScannerBtn.style.display = 'none';
                }).catch(err => {
                    console.error("Error stopping QR Code scanner:", err);
                });
            }
        });

        // Handle tab switching: stop scanner when tab is changed away from 'Scan QR Code'
        const accessMethodTab = document.getElementById('accessMethodTab');
        if (accessMethodTab) {
            accessMethodTab.addEventListener('hide.bs.tab', function(event) {
                // Check if the tab being hidden is the scan tab
                if (event.target.id === 'scan-tab' && html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        console.log("Scanner stopped due to tab switch.");
                        startScannerBtn.style.display = 'block';
                        stopScannerBtn.style.display = 'none';
                        qrCodeResultDiv.innerHTML = ''; // Clear message
                        qrCodeResultDiv.className = '';
                    }).catch(err => {
                        console.error("Error stopping scanner on tab switch:", err);
                    });
                }
            });
        }

        // If a token was already submitted (e.g., from QR scan on page load), keep results visible
        <?php if ($patient_data_retrieved): ?>
            // This means patient data was already fetched by token in GET param on page load
            // We should ensure the scanner is stopped and not attempting to scan
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }
            startScannerBtn.style.display = 'block'; // Ensure button is available to restart if needed
            stopScannerBtn.style.display = 'none';
        <?php endif; ?>
    });
</script>
<?php require_once '../includes/footer.php'; ?>