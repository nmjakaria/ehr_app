<?php
// patient/my_smart_health_card.php
$title = "My SMART Health Card";
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

?>

<div class="row justify-content-center mt-4">
    <div class="col-md-10 col-lg-9">
        <h2 class="mb-4 text-center">How to Electronic Health Record Work</h2>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">What is a SMART Health Card?</h4>
            </div>
            <div class="card-body">
                <p>A **SMART Health Card** is a secure, verifiable record of your health information, such as vaccination history or lab test results. It's designed to give you control over your personal health data.</p>
                <p>It can be presented digitally on your phone or printed on paper. At its core, it uses a **secure QR code** that contains essential, verifiable clinical information like your name, date of birth, and details about your vaccinations or test results. It **does not** include sensitive identifiers like your social security number or address.</p>
                <p>You typically receive a SMART Health Card from trusted healthcare providers (like your doctor's office, pharmacy, or public health agency). You can then store it in digital wallets (e.g., Apple Health, Google Pay) or keep a printed copy.</p>
                <p>When you need to share your health status (e.g., at an event, workplace, or another healthcare provider), you present the QR code. A verifier uses a special app to scan the code, which cryptographically checks that the information hasn't been tampered with and that it came from a trusted source. Only the necessary information is displayed to the verifier.</p>
                <p class="mb-0">This system prioritizes your privacy and security by ensuring data control, minimal data sharing, and tamper-proof verification.</p>
            </div>
        </div>


    </div>
</div>
<section class="subSection pt-5" id="getSaveShareGraphic">
  <div class="container">
    <div class="row">
      <div class="col-12 col-lg-12 text-left">

        <!-- Heading -->
        <h1>
          How SMART Health Cards work
        </h1>

      </div>
    </div> <!-- / .row -->

    <div class="row justify-content-center pt-5 pb-3">
      <div class="col-12 col-lg-4 text-left">
        <div class="row align-items-center bg-white pt-4 pb-4 pr-3">
          <div class="col-12 order-1">
            <!-- Image -->
            <img src="/Assets/Images/MIC21224_Illustration1_v01_DS_2021_06_28.png" class="img-fluid ml-3 mx-auto d-block" alt="Illustration of signing a paper SMART Health Card">
          </div>
          <div class="col-12 order-2">
            <!-- Heading -->
            <h2>
              Get it
            </h2>
            <!-- Text -->
            <p class="fs-lg mb-7 mb-md-9">
              You might receive a paper or digital SMART Health Card from any organization that has your clinical information, such as a pharmacy, doctor's office, or state immunization registry. If you haven't received one, you may be able to request it through that organization's website or a compatible app.
            </p>
            <p>
              <a href="faq.html#How-can-I-get-a-SMART-Health-Card">Learn more about getting a <span class="chevronText">SMART Health Card </span></a>
            </p>
          </div>
        </div> <!-- / .row -->
      </div>
      <div class="col-12 col-lg-4 text-left">
        <div class="row align-items-center bg-white pt-4 pb-4 pr-3">
          <div class="col-12 order-1">
            <!-- Image -->
            <img src="/Assets/Images/MIC21224_Illustration2_v01_DS_2021_06_28.png" class="img-fluid ml-3 mx-auto d-block" alt="Illustration of interacting with a digital SMART Health Card on a mobile device">
          </div>
          <div class="col-12 order-2">
            <!-- Heading -->
            <h2>
              Save it
            </h2>
            <!-- Text -->
            <p class="fs-lg mb-7 mb-md-9">
              You can keep a SMART Health Card as a digital file on your phone, computer or anywhere you store digital information. You can also save a paper SMART Health Card and make copies for safe-keeping. If you are a parent or a caregiver, you can keep SMART Health Cards for others, just as you do with other clinical information.
            </p>
            <p>
              <a href="faq.html#benefits">Learn more about the benefits of having your clinical information on hand </a>
            </p>
          </div>
        </div> <!-- / .row -->
      </div>
      <div class="col-12 col-lg-4 text-left">
        <div class="row align-items-center bg-white pt-4 pb-4 pr-3">
          <div class="col-12 order-1">
            <!-- Image -->
            <img src="/Assets/Images/MIC21224_Illustration3_v01_DS_2021_06_28.png" class="img-fluid ml-3 mx-auto d-block" alt="Illustration of presenting a SMART Health Card to a person behind a desk">
          </div>
          <div class="col-12 order-2">
            <!-- Heading -->
            <h2>
              Share it
            </h2>
            <!-- Text -->
            <p class="fs-lg mb-7 mb-md-9">
              You can share a SMART Health Card with others if you choose. For example, you might share it to show your vaccine status for school registration or travel. You share a SMART Health Card by letting someone scan the 2D barcode (QR code) on your paper or phone screen. You may also send it as a file or through a phone app.
            </p>
            <p>
              <a href="faq.html#sharingInformation">Learn more about sharing a <span class="chevronText">SMART Health Card</span></a>
            </p>
          </div>
        </div> <!-- / .row -->
      </div>
    </div> <!-- / .row -->

    <div class="row py-3">
      <div class="col-12">
          <hr class="hr-yellow">
      </div>
    </div>
  </div> <!-- / .container -->
</section>

<?php require_once 'includes/footer.php'; ?>