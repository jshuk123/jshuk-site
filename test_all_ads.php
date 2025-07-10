<?php
/**
 * Comprehensive Ad System Test Page
 * Tests all ad zones: header, sidebar, footer
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

$pageTitle = "Ad System Test";
include 'includes/header_main.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-5">🎯 Ad System Test Page</h1>
            <p class="text-center text-muted mb-4">This page tests all ad zones to ensure they're working correctly.</p>
        </div>
    </div>

    <div class="row">
        <!-- Left Sidebar Ad -->
        <div class="col-lg-2">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Left Sidebar Ad</h6>
                </div>
                <div class="card-body">
                    <?php include $_SERVER['DOCUMENT_ROOT'].'/partials/ads/sidebar_ads.php'; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Main Content Area</h6>
                </div>
                <div class="card-body">
                    <h3>Ad System Status</h3>
                    
                    <?php
                    // Test database connection
                    try {
                        $test = $pdo->query("SELECT 1");
                        echo "<div class='alert alert-success'>✅ Database connection: OK</div>";
                    } catch (Exception $e) {
                        echo "<div class='alert alert-danger'>❌ Database connection failed: " . $e->getMessage() . "</div>";
                    }
                    
                    // Check ads table
                    try {
                        $stmt = $pdo->query("SHOW TABLES LIKE 'ads'");
                        if ($stmt->rowCount() > 0) {
                            echo "<div class='alert alert-success'>✅ Ads table exists</div>";
                            
                            // Count total ads
                            $stmt = $pdo->query("SELECT COUNT(*) FROM ads");
                            $total = $stmt->fetchColumn();
                            echo "<div class='alert alert-info'>📊 Total ads in database: $total</div>";
                            
                            // Check for ads by zone
                            $zones = ['header', 'sidebar', 'footer', 'carousel', 'inline'];
                            $now = date('Y-m-d');
                            
                            foreach ($zones as $zone) {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM ads WHERE zone = ? AND status = 'active' AND start_date <= ? AND end_date >= ?");
                                $stmt->execute([$zone, $now, $now]);
                                $count = $stmt->fetchColumn();
                                echo "<div class='alert alert-" . ($count > 0 ? 'success' : 'warning') . "'>";
                                echo "🎯 $zone zone: $count active ads";
                                echo "</div>";
                            }
                        } else {
                            echo "<div class='alert alert-danger'>❌ Ads table does not exist</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='alert alert-danger'>❌ Error checking ads table: " . $e->getMessage() . "</div>";
                    }
                    ?>
                    
                    <h4 class="mt-4">Test Instructions</h4>
                    <ul>
                        <li><strong>Header Ad:</strong> Should appear at the top of the page (above this content)</li>
                        <li><strong>Left Sidebar Ad:</strong> Should appear in the left column</li>
                        <li><strong>Right Sidebar Ad:</strong> Should appear in the right column</li>
                        <li><strong>Footer Ad:</strong> Should appear at the bottom of the page</li>
                    </ul>
                    
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary">Test Homepage</a>
                        <a href="classifieds.php" class="btn btn-secondary">Test Classifieds</a>
                        <a href="recruitment.php" class="btn btn-info">Test Recruitment</a>
                        <a href="categories.php" class="btn btn-success">Test Categories</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar Ad -->
        <div class="col-lg-2">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Right Sidebar Ad</h6>
                </div>
                <div class="card-body">
                    <?php include $_SERVER['DOCUMENT_ROOT'].'/partials/ads/sidebar_ads.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer_main.php'; ?> 