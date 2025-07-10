<?php
/**
 * Testimonials & Star Ratings System Setup Script
 * Initializes the complete feedback system
 * Version: 1.2
 */

require_once '../config/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>JShuk Testimonials System Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container py-5'>
        <div class='row justify-content-center'>
            <div class='col-lg-8'>
                <div class='card shadow'>
                    <div class='card-header bg-primary text-white'>
                        <h3 class='mb-0'>
                            <i class='fas fa-star me-2'></i>
                            JShuk Testimonials & Star Ratings System Setup
                        </h3>
                    </div>
                    <div class='card-body'>";

try {
    echo "<h4>Step 1: Database Schema Updates</h4>";
    
    // Check if tables exist
    $tables_to_check = ['reviews', 'testimonials', 'reviews_log'];
    $existing_tables = [];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetch()) {
                $existing_tables[] = $table;
            }
        } catch (Exception $e) {
            // Table doesn't exist or other error
            continue;
        }
    }
    
    if (empty($existing_tables)) {
        echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong>Database tables not found!</strong><br>
            Please run the SQL schema file first: <code>sql/testimonials_star_ratings_system.sql</code>
        </div>";
        echo "<a href='../sql/testimonials_star_ratings_system.sql' class='btn btn-primary' target='_blank'>
            <i class='fas fa-download me-2'></i>Download SQL Schema
        </a>";
        exit;
    }
    
    echo "<div class='alert alert-success'>
        <i class='fas fa-check-circle me-2'></i>
        <strong>Database tables found:</strong> " . implode(', ', $existing_tables) . "
    </div>";
    
    echo "<h4>Step 2: Directory Structure</h4>";
    
    // Create upload directories
    $directories = [
        '../uploads/testimonials',
        '../logs'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0777, true)) {
                echo "<div class='alert alert-success'>
                    <i class='fas fa-check-circle me-2'></i>
                    Created directory: <code>$dir</code>
                </div>";
            } else {
                echo "<div class='alert alert-danger'>
                    <i class='fas fa-times-circle me-2'></i>
                    Failed to create directory: <code>$dir</code>
                </div>";
            }
        } else {
            echo "<div class='alert alert-info'>
                <i class='fas fa-info-circle me-2'></i>
                Directory exists: <code>$dir</code>
            </div>";
        }
    }
    
    echo "<h4>Step 3: System Verification</h4>";
    
    // Check subscription plans table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'subscription_plans'");
        $subscription_plans_exists = $stmt->fetch();
    } catch (Exception $e) {
        $subscription_plans_exists = false;
    }
    
    if ($subscription_plans_exists) {
        // Check subscription plans
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_plans");
        $stmt->execute();
        $plan_count = $stmt->fetchColumn();
        
        echo "<div class='alert alert-info'>
            <i class='fas fa-info-circle me-2'></i>
            <strong>Subscription Plans:</strong> $plan_count plans found
        </div>";
    } else {
        echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong>Subscription Plans:</strong> subscription_plans table not found. This is required for tier-based testimonials.
        </div>";
    }
    
    // Check businesses with subscription tiers
    try {
        $stmt = $pdo->prepare("SELECT subscription_tier, COUNT(*) as count FROM businesses GROUP BY subscription_tier");
        $stmt->execute();
        $business_tiers = $stmt->fetchAll();
        
        echo "<div class='alert alert-info'>
            <i class='fas fa-info-circle me-2'></i>
            <strong>Business Subscription Tiers:</strong><br>";
        foreach ($business_tiers as $tier) {
            echo "- " . ucfirst($tier['subscription_tier']) . ": " . $tier['count'] . " businesses<br>";
        }
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong>Business Subscription Tiers:</strong> Could not retrieve data - " . htmlspecialchars($e->getMessage()) . "
        </div>";
    }
    
    // Check existing reviews and testimonials
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews");
        $stmt->execute();
        $review_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials");
        $stmt->execute();
        $testimonial_count = $stmt->fetchColumn();
        
        echo "<div class='alert alert-info'>
            <i class='fas fa-info-circle me-2'></i>
            <strong>Existing Data:</strong><br>
            - Reviews: $review_count<br>
            - Testimonials: $testimonial_count
        </div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong>Existing Data:</strong> Could not retrieve data - " . htmlspecialchars($e->getMessage()) . "
        </div>";
    }
    
    echo "<h4>Step 4: File Verification</h4>";
    
    $required_files = [
        '../submit_review.php',
        '../users/manage_reviews.php',
        '../admin/reviews.php',
        '../actions/get_business_testimonials.php',
        '../actions/update_testimonial_status.php',
        '../actions/toggle_testimonial_featured.php',
        '../partials/star_rating.php',
        '../partials/testimonial_card.php',
        '../partials/review_form.php'
    ];
    
    $missing_files = [];
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "<div class='alert alert-success'>
                <i class='fas fa-check-circle me-2'></i>
                File exists: <code>$file</code>
            </div>";
        } else {
            $missing_files[] = $file;
            echo "<div class='alert alert-danger'>
                <i class='fas fa-times-circle me-2'></i>
                Missing file: <code>$file</code>
            </div>";
        }
    }
    
    if (!empty($missing_files)) {
        echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong>Missing Files:</strong> Please ensure all required files are created.
        </div>";
    }
    
    echo "<h4>Step 5: System Status</h4>";
    
    if (empty($missing_files)) {
        echo "<div class='alert alert-success'>
            <i class='fas fa-check-circle me-2'></i>
            <strong>✅ System Ready!</strong><br>
            The testimonials and star ratings system is properly configured and ready to use.
        </div>";
        
        echo "<div class='row mt-4'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-body text-center'>
                        <i class='fas fa-users fa-3x text-primary mb-3'></i>
                        <h5>Business Management</h5>
                        <p>Business owners can manage testimonials</p>
                        <a href='../users/manage_reviews.php' class='btn btn-primary' target='_blank'>
                            Manage Reviews
                        </a>
                    </div>
                </div>
            </div>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-body text-center'>
                        <i class='fas fa-shield-alt fa-3x text-success mb-3'></i>
                        <h5>Admin Panel</h5>
                        <p>Admins can manage all reviews</p>
                        <a href='../admin/reviews.php' class='btn btn-success' target='_blank'>
                            Admin Panel
                        </a>
                    </div>
                </div>
            </div>
        </div>";
        
        echo "<div class='mt-4'>
            <h5>Next Steps:</h5>
            <ol>
                <li>Add the review form to business pages using: <code>&lt;?php include 'partials/review_form.php'; ?&gt;</code></li>
                <li>Display testimonials using: <code>&lt;?php include 'partials/testimonial_card.php'; ?&gt;</code></li>
                <li>Show star ratings using: <code>&lt;?php include 'partials/star_rating.php'; ?&gt;</code></li>
                <li>Test the system by submitting reviews and managing testimonials</li>
            </ol>
        </div>";
        
    } else {
        echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong>⚠️ System Incomplete</strong><br>
            Please create the missing files before using the system.
        </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
        <i class='fas fa-times-circle me-2'></i>
        <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "</div>
                </div>
            </div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 