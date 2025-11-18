<?php
// PHP Setup: These lines handle page configuration and database connection.
$title = "My SMART Health Card";
require_once 'includes/db_connection.php';
require_once 'includes/header.php'; // Includes the starting HTML tags and links to stylesheets (where the wallpaper CSS should live)
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
    background-image: url('/banners/images/How\ its\ work.jpg'); 
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-position: center center;
    background-color: #f4f7f9; 
}

.card {
    background-color: rgba(255, 255, 255, 0.9);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.step-block .illustration {
    max-width: 150px; /* Limits the size of the diagram images */
    height: auto;
    margin-bottom: 20px;
}

.info-card {
    background-image: url('/banners/images/how\ it\ works\ 1.jpg'); 
    background-size: cover;
    
    background-repeat: no-repeat;
    background-position: center center;
    background-color: #ffffff; /* Example: White fallback */
}

.info-card .card-body {
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.75); /* 75% opaque white layer */
    border-radius: 0 0 0.5rem 0.5rem;
}

.info-card .card-header {
    background-color: var(--bs-primary, #0d6efd) !important; /* Uses Bootstrap primary color */
}
  </style>
</head>

<body>
  <main class="page-content">
    <div class="container-fluid">
      <div class="row justify-content-center mt-4">
        <div class="col-md-10 col-lg-9">
          <h1 class="text-center mb-5">How Electronic Health Records Work</h1>

          <section class="info-card card shadow-lg mb-5">
            <header class="card-header bg-primary text-white">
              <h2 class="mb-0">What is a SMART Health Card?</h2>
            </header>
            <div class="card-body">
              <p>A **SMART Health Card** is a secure, verifiable record of your health information, such as vaccination history or lab test results. It's designed to give you control over your personal health data.</p>
              <p>It uses a **secure QR code** containing essential, verifiable clinical information like your name and date of birth. It **does not** include sensitive identifiers like your social security number or address.</p>
              <p>You receive the card from trusted healthcare providers and can store it digitally or as a printed copy. The QR code is scanned by a verifier app which cryptographically checks the information for tampering and source validity, prioritizing your privacy and security.</p>
            </div>
          </section>
        </div>
      </div>

      <section class="workflow-section pt-5" id="getSaveShareGraphic">
        <div class="container">
          <h1 class="text-center mb-5">How SMART Health Cards Work</h1>

          <div class="row justify-content-center pt-5 pb-3">

            <article class="col-12 col-lg-4 step-block">
              <div class="card bg-white h-100 p-4 shadow-sm">
                <img src="./banners/images/how it works 1.jpg" class="img-fluid mx-auto d-block illustration" alt="Illustration of signing a paper SMART Health Card">
                <h2>Get it</h2>
                <p>You receive a paper or digital SMART Health Card from an organization with your clinical data (pharmacy, doctor, registry). If you haven't received one, you can usually request it through their website or a compatible app.</p>
                <p><a href="faq.html#How-can-I-get-a-SMART-Health-Card">Learn more about getting a SMART Health Card</a></p>
              </div>
            </article>

            <article class="col-12 col-lg-4 step-block">
              <div class="card bg-white h-100 p-4 shadow-sm">
                <img src="./banners/images/Digital servey.jpg" class="img-fluid mx-auto d-block illustration" alt="Illustration of interacting with a digital SMART Health Card on a mobile device">
                <h2>Save it</h2>
                <p>You can store your SMART Health Card as a digital file on your devices or keep a printed copy. Parents or caregivers can also keep cards for others.</p>
                <p><a href="faq.html#benefits">Learn more about the benefits of having your clinical information on hand</a></p>
              </div>
            </article>

            <article class="col-12 col-lg-4 step-block">
              <div class="card bg-white h-100 p-4 shadow-sm">
                <img src="./banners/images/How its work.jpg" class="img-fluid mx-auto d-block illustration" alt="Illustration of presenting a SMART Health Card to a person behind a desk">
                <h2>Share it</h2>
                <p>You choose when to share your card, such as for travel or school registration. Sharing is done by letting someone scan the QR code (on paper or phone screen) or by sending the file/through an app.</p>
                <p><a href="faq.html#sharingInformation">Learn more about sharing a SMART Health Card</a></p>
              </div>
            </article>

          </div>
          <div class="row py-3">
            <div class="col-12">
              <hr class="hr-yellow">
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>
</body>
</html>
<?php require_once 'includes/footer.php'; ?>