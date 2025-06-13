<?php
// index.php
$title = "Home"; // Set the title for this page

// Include database connection (though not strictly needed for home page, it's good practice for general includes)
require_once 'includes/db_connection.php';

// Include the header
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/assets/css/style.css" class="css">
</head>

<body>
    <section class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="display-4 mb-3">Your Health, Digitally Managed.</h1>
            <p class="lead mb-4">Secure, efficient, and user-friendly Electronic Health Record system for doctors, patients, and administrators.</p>
            <div class="btn-group btn-group-lg" role="group" aria-label="Login Options">
                <a href="doctor/login.php" class="btn btn-primary"><i class="fas fa-user-md me-2"></i> Doctor Login</a>
                <a href="patient/login.php" class="btn btn-success"><i class="fas fa-user-injured me-2"></i> Patient Login</a>
                <a href="admin/login.php" class="btn btn-info text-white"><i class="fas fa-user-shield me-2"></i> Admin Login</a>
            </div>
        </div>
    </section>

    <section class="features-section mb-5">
        <h2 class="text-center mb-4">Key Features</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body text-center">
                        <i class="fas fa-notes-medical fa-4x text-primary mb-3"></i>
                        <h5 class="card-title">Digital Prescriptions</h5>
                        <p class="card-text">Doctors can easily issue, manage, and print prescriptions. Patients can access their records anywhere.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-4x text-success mb-3"></i>
                        <h5 class="card-title">Appointment Management</h5>
                        <p class="card-text">Streamline your scheduling. Patients can book, and doctors can accept or decline appointments.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm card-feature">
                    <div class="card-body text-center">
                        <i class="fas fa-file-medical-alt fa-4x text-info mb-3"></i>
                        <h5 class="card-title">Comprehensive Health Records</h5>
                        <p class="card-text">Store medical reports, health conditions, and dose history securely for easy access.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="promo-section text-center mb-5">
        <h2 class="mb-4">Our Partners</h2>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="square-image-container"> <img src="/ehr_app/banners/images/138217258_10292830.jpg" alt="Advertisement 1" class="img-fluid">
                </div>
                <p>Your ad here!</p>
            </div>
            <div class="col-md-4 mb-3">
                <div class="square-image-container">
                    <img src="/ehr_app/banners/images/pexels-artempodrez-6823504.jpg" alt="Advertisement 2" class="img-fluid">
                </div>
                <p>Partner with us!</p>
            </div>
            <div class="col-md-4 mb-3">
                <div class="square-image-container">
                    <img src="/ehr_app/banners/images/pexels-n-voitkevich-8830657.jpg" alt="Advertisement 3" class="img-fluid">
                </div>
                <p>Healthcare solutions</p>
            </div>
        </div>
    </section>
</body>

</html>
<?php
// Include the footer
require_once 'includes/footer.php';
?>