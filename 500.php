<?php
require_once 'config/config.php';
$pageTitle = "Error 500 - Server Error";
include 'includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-lg border-0 rounded-lg text-center p-4">
                <div class="card-body">
                    <i class="fas fa-exclamation-triangle fa-5x text-danger mb-4"></i>
                    <h1 class="display-5 fw-bold mb-3">Oops! Something went wrong</h1>
                    <p class="lead text-muted mb-4">We're experiencing some technical difficulties on our end. Please try again in a little while.</p>
                    <a href="/" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i> Go to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer_main.php'; ?>