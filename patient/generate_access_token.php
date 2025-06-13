<?php
// patient/generate_access_token.php
$title = "Generate QR Code";
require_once '../includes/db_connection.php';
require_once '../includes/header.php';

// Composer autoloader to load the QR code library
// Make sure you have run 'composer install' in your ehr_app directory
// if you haven't already, and that vendor/autoload.php exists.
require_once '../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Check if the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_user_id = $_SESSION['user_id'];
$patient_id = null;
$message = '';
$message_type = '';
$qr_code_data_uri = '';
$token_expires_at_display = ''; // Renamed for clarity in display
$generated_token = ''; // Store the generated token for display

// Fetch patient_id from the patients table
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$patient_user_id]);
    $patient_data = $stmt->fetch();
    if ($patient_data) {
        $patient_id = $patient_data['id'];
    } else {
        die("Patient record not found for this user.");
    }
} catch (PDOException $e) {
    die("Error fetching patient ID: " . $e->getMessage());
}

// Generate QR Code on page load (or upon user request, for now on load)
if ($patient_id) {
    // 1. Generate a secure, unique token
    $generated_token = bin2hex(random_bytes(32)); // 64 character hex string

    // 2. Define token expiry (e.g., 5 minutes from now for quick use, adjustable)
    $expiry_minutes = 5; // Default expiry
    // If you want to allow user to set expiry, you'd process a POST variable here
    // e.g., if (isset($_POST['expiry_minutes'])) { $expiry_minutes = (int)$_POST['expiry_minutes']; }
    // Ensure it's validated for min/max values.

    $expires_at = date('Y-m-d H:i:s', strtotime("+$expiry_minutes minutes"));

    try {
        // Deactivate any previous active tokens for this patient that are NOT yet expired
        // This makes sure only one QR code is active at a time for this patient.
        $stmt = $pdo->prepare("UPDATE qr_access_tokens SET is_used = TRUE WHERE patient_id = ? AND is_used = FALSE AND expires_at > NOW()");
        $stmt->execute([$patient_id]);

        // Store the new token in the database
        $stmt = $pdo->prepare("INSERT INTO qr_access_tokens (patient_id, token, expires_at, is_used) VALUES (?, ?, ?, FALSE)"); // Initially not used
        $stmt->execute([$patient_id, $generated_token, $expires_at]);

        $token_expires_at_display = date('H:i:s A, M d, Y', strtotime($expires_at)); // Format for display

        // 3. Prepare data for the QR code
        // IMPORTANT: The QR code will contain a full URL pointing to the doctor's scanning page.
        // Replace 'your_base_url' with your actual application's base URL (e.g., http://localhost/ehr_app).
        // It's crucial for the doctor's scanner to open this URL directly.
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        // Adjust dirname to correctly point to the ehr_app/doctor directory
        // If generate_access_token.php is in ehr_app/patient/, then ../doctor/scan_qr_code.php
        $qr_target_url = $base_url . dirname($_SERVER['PHP_SELF'], 2) . '/doctor/access_patient_data.php?token=' . urlencode($generated_token);

        // 4. Generate the QR code image
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG, // Output as PNG image
            'eccLevel'   => QRCode::ECC_L,    // Error Correction Level (L, M, Q, H)
            'scale'      => 8,                        // Scale of the QR code image
            'quality'    => 90,                       // Quality of the image
        ]);

        $qrcode = new QRCode($options);
        $qr_code_data_uri = $qrcode->render($qr_target_url); // QR code encodes the URL

        $message = "Your temporary QR code has been generated. It is valid for the next " . $expiry_minutes . " minutes.";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Error generating QR code: " . $e->getMessage();
        $message_type = "danger";
    } catch (Exception $e) {
        $message = "Error generating QR code: " . $e->getMessage();
        $message_type = "danger";
    }
} else {
    $message = "Patient ID not found. Unable to generate QR code.";
    $message_type = "danger";
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h2 class="mb-4 text-center">Your Secure QR Code</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">Access Token QR</h4>
            </div>
            <div class="card-body text-center">
                <?php if ($qr_code_data_uri): ?>
                    <img src="<?php echo htmlspecialchars($qr_code_data_uri); ?>" alt="QR Code" class="img-fluid mb-3" style="max-width: 250px;">
                    <p class="lead">Scan this code for quick, secure access.</p>
                    <p class="text-muted"><strong>Valid until:</strong> <?php echo htmlspecialchars($token_expires_at_display); ?></p>
                    <hr>

                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="tokenInput" value="<?php echo htmlspecialchars($generated_token); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyTokenBtn">Copy Token</button>
                    </div>
                    <small class="text-success" id="copyFeedback" style="display: none;">Copied!</small>

                    <hr>
                    <p class="text-small text-danger">For security, this QR code is temporary and contains a unique token, not your personal data. Do not share it unnecessarily. The doctor will have access to your current prescriptions, lab reports, and basic health conditions.</p>
                <?php else: ?>
                    <p class="text-danger">QR code could not be generated.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
            <button class="btn btn-info mt-3 ms-2" onclick="location.reload();">
                <i class="fas fa-sync-alt me-2"></i> Generate New QR Code
            </button>
        </div>
    </div>
</div>
<script>
    document.getElementById('copyTokenBtn').addEventListener('click', function() {
        var tokenInput = document.getElementById('tokenInput');
        var copyFeedback = document.getElementById('copyFeedback');

        // Select the text in the input field
        tokenInput.select();
        tokenInput.setSelectionRange(0, 99999); // For mobile devices

        // Copy the text to the clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            // Modern way to copy (recommended)
            navigator.clipboard.writeText(tokenInput.value).then(function() {
                copyFeedback.style.display = 'inline';
                setTimeout(function() {
                    copyFeedback.style.display = 'none';
                }, 2000); // Hide feedback after 2 seconds
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                // Fallback if writeText fails
                document.execCommand('copy');
                copyFeedback.style.display = 'inline';
                setTimeout(function() {
                    copyFeedback.style.display = 'none';
                }, 2000);
            });
        } else {
            // Fallback for older browsers (less secure/reliable)
            document.execCommand('copy');
            copyFeedback.style.display = 'inline';
            setTimeout(function() {
                copyFeedback.style.display = 'none';
            }, 2000);
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>