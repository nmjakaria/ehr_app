<?php
// includes/header.php

// Ensure config.php is included first if it contains critical settings
require_once 'config.php';

// Start a PHP session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine if a user is logged in and their type
$is_logged_in = isset($_SESSION['user_id']);
$user_type = $_SESSION['user_type'] ?? '';
$user_full_name = $_SESSION['full_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EHR System - <?php echo $title ?? 'Electronic Health Record'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/ehr_app/assets/css/style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/ehr_app/index.php">
                <i class="fas fa-notes-medical"></i> EHR System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="/ehr_app/index.php">Home</a>
                    </li>
                    <?php if ($is_logged_in): // Only show these if logged in ?>
                        <?php if ($user_type == 'doctor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/doctor/dashboard.php">Doctor Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/doctor/access_patient_data.php">SCAN</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/doctor/manage_appointments.php">Appointments</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/doctor/give_prescription_walkin.php">Prescriptions</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/doctor/my_patients.php">My Patients</a>
                            </li>
                        <?php elseif ($user_type == 'patient'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/patient/dashboard.php">Patient Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/patient/my_appointments.php">Appointment</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/patient/my_prescriptions.php">Prescription</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/patient/view_lab_results.php">Lab Reports</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/patient/health_condition.php">Health Condition</a>
                            </li>
                        <?php elseif ($user_type == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/admin/dashboard.php">Admin Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/admin/manage_doctors.php">Doctor</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/admin/manage_patients.php">Patient</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/admin/manage_appointments.php">Appointment</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/ehr_app/admin/manage_lab_results.php">Manage Lab Results</a>
                            </li>
                            <?php endif; ?>
                    <?php endif; // End of if ($is_logged_in) ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($user_full_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <?php if ($user_type == 'doctor'): ?>
                                    <li><a class="dropdown-item" href="/ehr_app/doctor/profile.php">Profile</a></li>
                                <?php elseif ($user_type == 'patient'): ?>
                                    <li><a class="dropdown-item" href="/ehr_app/patient/profile.php">Profile</a></li>
                                <?php elseif ($user_type == 'admin'): ?>
                                    <li><a class="dropdown-item" href="/ehr_app/admin/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="/ehr_app/admin/manage_lab_tests.php">Manage Lab Test</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="/ehr_app/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light me-2" href="/ehr_app/doctor/login.php"><i class="fas fa-user-md"></i> Doctor Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light me-2" href="/ehr_app/patient/login.php"><i class="fas fa-user-injured"></i> Patient Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light" href="/ehr_app/admin/login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/ehr_app/assets/js/script.js"></script>

</body>

</html>