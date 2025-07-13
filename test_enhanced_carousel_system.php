<?php
/**
 * Enhanced Carousel System Test Page
 * JShuk Advanced Carousel Management System
 * Phase 7: Comprehensive Testing
 */

require_once 'config/config.php';
require_once 'includes/enhanced_carousel_functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Test different scenarios
$testScenarios = [
    'homepage' => ['zone' => 'homepage', 'location' => null],
    'london' => ['zone' => 'homepage', 'location' => 'london'],
    'manchester' => ['zone' => 'homepage', 'location' => 'manchester'],
    'businesses' => ['zone' => 'businesses', 'location' => null],
    'post-business' => ['zone' => 'post-business', 'location' => null]
];

// Get carousel stats
$stats = getCarouselStats($pdo);
$performance = getCarouselPerformance($pdo, 30);
$expiringSlides = getExpiringSlides($pdo, 7);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Carousel System Test - JShuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />
    <style>
        body { background: #f8f9fa; }
        .test-section { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .carousel-test { border: 2px dashed #dee2e6; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .stats-card { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border-radius: 10px; padding: 20px; }
        .feature-badge { background: linear-gradient(45deg, #ff6b6b, #ff8e53); color: white; }
        .test-result { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .test-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .test-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .test-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .api-test { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .api-response { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="test-section">
                <h1 class="text-center mb-4">
                    🎠 Enhanced Carousel System Test
                </h1>
                <p class="text-center text-muted">
                    Comprehensive testing of the JShuk Advanced Carousel Management System
                </p>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="row">
        <div class="col-md-3">
            <div class="stats-card">
                <h3><?= $stats['total_slides'] ?? 0 ?></h3>
                <p>Total Slides</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><?= $stats['active_slides'] ?? 0 ?></h3>
                <p>Active Slides</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><?= $stats['sponsored_slides'] ?? 0 ?></h3>
                <p>Sponsored Slides</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><?= $stats['overall_ctr'] ?? 0 ?>%</h3>
                <p>Overall CTR</p>
            </div>
        </div>
    </div>

    <!-- Feature Tests -->
    <div class="row">
        <div class="col-12">
            <div class="test-section">
                <h3><i class="fas fa-cogs me-2"></i>System Features Test</h3>
                
                <?php
                // Test database connectivity
                try {
                    $pdo->query("SELECT 1");
                    echo '<div class="test-result test-success">✅ Database connection successful</div>';
                } catch (Exception $e) {
                    echo '<div class="test-result test-error">❌ Database connection failed: ' . $e->getMessage() . '</div>';
                }
                
                // Test enhanced carousel functions
                try {
                    $slides = getCarouselSlides($pdo, 'homepage', 5);
                    echo '<div class="test-result test-success">✅ Enhanced carousel functions loaded successfully</div>';
                    echo '<div class="test-result test-success">✅ Found ' . count($slides) . ' slides for homepage</div>';
                } catch (Exception $e) {
                    echo '<div class="test-result test-error">❌ Enhanced carousel functions failed: ' . $e->getMessage() . '</div>';
                }
                
                // Test location detection
                $userLocation = getUserLocation($pdo);
                echo '<div class="test-result test-success">✅ User location detected: ' . htmlspecialchars($userLocation) . '</div>';
                
                // Test analytics logging
                try {
                    $result = logCarouselEvent($pdo, 1, 'impression');
                    if ($result) {
                        echo '<div class="test-result test-success">✅ Analytics logging working</div>';
                    } else {
                        echo '<div class="test-result test-warning">⚠️ Analytics logging returned false</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="test-result test-error">❌ Analytics logging failed: ' . $e->getMessage() . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- API Tests -->
    <div class="row">
        <div class="col-12">
            <div class="test-section">
                <h3><i class="fas fa-plug me-2"></i>API Endpoint Tests</h3>
                
                <div class="api-test">
                    <h5>Test Analytics API</h5>
                    <button class="btn btn-primary btn-sm" onclick="testAnalyticsAPI()">Test Analytics Endpoint</button>
                    <div id="analytics-api-result" class="api-response mt-2" style="display: none;"></div>
                </div>
                
                <div class="api-test">
                    <h5>Test Performance API</h5>
                    <button class="btn btn-primary btn-sm" onclick="testPerformanceAPI()">Test Performance Endpoint</button>
                    <div id="performance-api-result" class="api-response mt-2" style="display: none;"></div>
                </div>
                
                <div class="api-test">
                    <h5>Test Slides API</h5>
                    <button class="btn btn-primary btn-sm" onclick="testSlidesAPI()">Test Slides Endpoint</button>
                    <div id="slides-api-result" class="api-response mt-2" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carousel Tests -->
    <div class="row">
        <div class="col-12">
            <div class="test-section">
                <h3><i class="fas fa-images me-2"></i>Carousel Display Tests</h3>
                
                <?php foreach ($testScenarios as $scenario => $config): ?>
                    <div class="carousel-test">
                        <h5>
                            <span class="badge feature-badge me-2"><?= ucfirst($scenario) ?></span>
                            Zone: <?= $config['zone'] ?> | Location: <?= $config['location'] ?? 'Auto-detect' ?>
                        </h5>
                        
                        <?php
                        $slides = getCarouselSlides($pdo, $config['zone'], 5, $config['location']);
                        if (!empty($slides)) {
                            echo '<div class="test-result test-success">✅ Found ' . count($slides) . ' slides</div>';
                            
                            // Display carousel
                            $zone = $config['zone'];
                            $location = $config['location'];
                            include 'sections/enhanced_carousel.php';
                        } else {
                            echo '<div class="test-result test-warning">⚠️ No slides found for this scenario</div>';
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Performance Data -->
    <div class="row">
        <div class="col-12">
            <div class="test-section">
                <h3><i class="fas fa-chart-bar me-2"></i>Performance Analytics</h3>
                
                <?php if (!empty($performance)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Slide</th>
                                    <th>Location</th>
                                    <th>Impressions</th>
                                    <th>Clicks</th>
                                    <th>CTR</th>
                                    <th>Sponsored</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performance as $perf): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($perf['title']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($perf['location']) ?></span></td>
                                        <td><?= number_format($perf['impressions']) ?></td>
                                        <td><?= number_format($perf['clicks']) ?></td>
                                        <td><?= $perf['ctr_percentage'] ?>%</td>
                                        <td>
                                            <?php if ($perf['sponsored']): ?>
                                                <span class="badge feature-badge">Sponsored</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Regular</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="test-result test-warning">⚠️ No performance data available yet</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Expiring Slides -->
    <?php if (!empty($expiringSlides)): ?>
    <div class="row">
        <div class="col-12">
            <div class="test-section">
                <h3><i class="fas fa-clock me-2"></i>Expiring Slides (Next 7 Days)</h3>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>End Date</th>
                                <th>Days Left</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringSlides as $slide): ?>
                                <tr>
                                    <td><?= htmlspecialchars($slide['title']) ?></td>
                                    <td><?= date('M j, Y', strtotime($slide['end_date'])) ?></td>
                                    <td>
                                        <?php 
                                        $daysLeft = (strtotime($slide['end_date']) - time()) / (60 * 60 * 24);
                                        echo round($daysLeft);
                                        ?> days
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($slide['location']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- System Information -->
    <div class="row">
        <div class="col-12">
            <div class="test-section">
                <h3><i class="fas fa-info-circle me-2"></i>System Information</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Database Tables</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>carousel_slides</li>
                            <li><i class="fas fa-check text-success me-2"></i>carousel_analytics</li>
                            <li><i class="fas fa-check text-success me-2"></i>carousel_analytics_summary</li>
                            <li><i class="fas fa-check text-success me-2"></i>location_mappings</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Key Features</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Location-based targeting</li>
                            <li><i class="fas fa-check text-success me-2"></i>Analytics tracking</li>
                            <li><i class="fas fa-check text-success me-2"></i>Scheduling system</li>
                            <li><i class="fas fa-check text-success me-2"></i>Sponsored content</li>
                            <li><i class="fas fa-check text-success me-2"></i>Multi-zone support</li>
                            <li><i class="fas fa-check text-success me-2"></i>Priority management</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h5>Quick Links</h5>
                    <a href="admin/enhanced_carousel_manager.php" class="btn btn-primary me-2">
                        <i class="fas fa-cog me-1"></i>Admin Panel
                    </a>
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="fas fa-home me-1"></i>Homepage
                    </a>
                    <a href="api/carousel-analytics.php?action=stats" class="btn btn-info me-2" target="_blank">
                        <i class="fas fa-chart-line me-1"></i>API Stats
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
<script>
// API Test Functions
async function testAnalyticsAPI() {
    const resultDiv = document.getElementById('analytics-api-result');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = 'Testing...';
    
    try {
        const response = await fetch('/api/carousel-analytics.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                slide_id: 1,
                event_type: 'impression'
            })
        });
        
        const data = await response.json();
        resultDiv.innerHTML = 'Response: ' + JSON.stringify(data, null, 2);
        resultDiv.className = 'api-response mt-2 ' + (data.success ? 'test-success' : 'test-error');
    } catch (error) {
        resultDiv.innerHTML = 'Error: ' + error.message;
        resultDiv.className = 'api-response mt-2 test-error';
    }
}

async function testPerformanceAPI() {
    const resultDiv = document.getElementById('performance-api-result');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = 'Testing...';
    
    try {
        const response = await fetch('/api/carousel-analytics.php?action=performance&days=30');
        const data = await response.json();
        resultDiv.innerHTML = 'Response: ' + JSON.stringify(data, null, 2);
        resultDiv.className = 'api-response mt-2 ' + (data.success ? 'test-success' : 'test-error');
    } catch (error) {
        resultDiv.innerHTML = 'Error: ' + error.message;
        resultDiv.className = 'api-response mt-2 test-error';
    }
}

async function testSlidesAPI() {
    const resultDiv = document.getElementById('slides-api-result');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = 'Testing...';
    
    try {
        const response = await fetch('/api/carousel-analytics.php?action=slides&zone=homepage&limit=5');
        const data = await response.json();
        resultDiv.innerHTML = 'Response: ' + JSON.stringify(data, null, 2);
        resultDiv.className = 'api-response mt-2 ' + (data.success ? 'test-success' : 'test-error');
    } catch (error) {
        resultDiv.innerHTML = 'Error: ' + error.message;
        resultDiv.className = 'api-response mt-2 test-error';
    }
}

// Auto-refresh performance data every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);

console.log('🎠 Enhanced Carousel System Test Page Loaded');
console.log('📊 Testing all features of the advanced carousel system');
</script>

</body>
</html> 