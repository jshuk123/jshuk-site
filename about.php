<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Lax',
    ]);
    session_start();
}

$pageTitle = "About JShuk | Jewish Local Directory - How It Works & FAQ";
$page_css = "about.css";
$metaDescription = "Learn how JShuk works, find answers to frequently asked questions, and discover how we connect the Jewish community with trusted local businesses.";
$metaKeywords = "JShuk about, how it works, FAQ, Jewish community, local businesses, help";

include 'includes/header_main.php';
?>

<!-- ABOUT HERO -->
<section class="about-hero">
  <div class="container">
    <h1>About JShuk</h1>
    <p class="lead">Connecting the Jewish community with trusted local businesses since 2023</p>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works-section" data-scroll>
  <div class="container">
    <h2 class="section-title">How It Works</h2>
    <p class="section-subtitle">Get started in just three simple steps</p>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-icon">
          <i class="fa-solid fa-user-plus"></i>
        </div>
        <h3>1. Sign Up</h3>
        <p>Create your free account and join our growing community of Jewish businesses and customers.</p>
      </div>
      <div class="step-card">
        <div class="step-icon">
          <i class="fa-solid fa-store"></i>
        </div>
        <h3>2. List Your Business</h3>
        <p>Add your business details, photos, and services to showcase what makes you unique.</p>
      </div>
      <div class="step-card">
        <div class="step-icon">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h3>3. Get Discovered</h3>
        <p>Connect with local customers who are actively searching for businesses like yours.</p>
      </div>
    </div>
    <div class="section-actions">
      <a href="<?= BASE_PATH ?>auth/register.php" class="btn-jshuk-primary">Post Your Business for Free</a>
    </div>
  </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section" data-scroll id="faq">
  <div class="container">
    <h2 class="section-title">Frequently Asked Questions</h2>
    <div class="accordion" id="faqAccordion">
      <div class="accordion-item">
        <h2 class="accordion-header" id="q1">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a1" aria-expanded="false" aria-controls="a1">
            How do I post a business?
          </button>
        </h2>
        <div id="a1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Simply click "Post Your Business" above, create a free account, and fill out your business details. It takes just a few minutes to get started!
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="q2">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2" aria-expanded="false" aria-controls="a2">
            Is JShuk free to use?
          </button>
        </h2>
        <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Yes! Basic listings are completely free. We also offer premium features for businesses who want enhanced visibility and additional tools.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="q3">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3" aria-expanded="false" aria-controls="a3">
            How do I find local Jewish businesses?
          </button>
        </h2>
        <div id="a3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Use our search bar above or browse by categories. You can filter by location, service type, and more to find exactly what you need.
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="q4">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4" aria-expanded="false" aria-controls="a4">
            Are all businesses kosher-certified?
          </button>
        </h2>
        <div id="a4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            We list all Jewish-owned businesses. For kosher certification, please check with individual businesses as requirements vary.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CONTACT CTA -->
<section class="contact-cta" data-scroll>
  <div class="container">
    <div class="cta-content">
      <h2>Still Have Questions?</h2>
      <p>We're here to help! Get in touch with our support team.</p>
      <div class="cta-buttons">
        <a href="mailto:support@jshuk.com" class="btn-jshuk-primary">Email Support</a>
        <a href="<?= BASE_PATH ?>" class="btn-jshuk-outline">Back to Home</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer_main.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Initialize Bootstrap accordion
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  });
  
  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
});
</script> 