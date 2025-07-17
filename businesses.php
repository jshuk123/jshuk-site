<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/config.php';

// Simple function to get business logo
function getBusinessLogoUrl($file_path, $business_name = '') {
    if (empty($file_path)) {
        return '/images/jshuk-logo.png';
    }
    
    if (strpos($file_path, 'http') === 0) {
        return $file_path;
    }
    
    if (strpos($file_path, '/') === 0) {
        return $file_path;
    }
    
    return '/uploads/' . $file_path;
}

// Function to generate star rating HTML
function generateStars($rating) {
    if (!$rating || $rating == 0) {
        return '<span class="text-muted">No rating</span>';
    }
    
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - $rating < 1) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }
    $html .= '</span>';
    
    return $html;
}

// Function to extract location from address
function extractLocation($address) {
    if (empty($address)) {
        return 'Location not specified';
    }
    
    // Try to extract city/area from address
    $address_parts = explode(',', $address);
    if (count($address_parts) > 1) {
        // Take the last part (usually city/area)
        $location = trim(end($address_parts));
        return $location;
    }
    
    return $address;
}

$page_css = "businesses.css";
include 'includes/header_main.php';

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';

// Enhanced query to get businesses with location and rating data
try {
    $query = "SELECT b.*, c.name as category_name, u.subscription_tier,
                     COALESCE(b.location, b.address) as business_location,
                     COALESCE(AVG(r.rating), 0) as average_rating,
                     COUNT(r.id) as review_count
              FROM businesses b 
              LEFT JOIN business_categories c ON b.category_id = c.id 
              LEFT JOIN users u ON b.user_id = u.id
              LEFT JOIN reviews r ON b.id = r.business_id 
              WHERE b.status = 'active'";

    $params = [];

    // Apply category filter
    if (!empty($category_filter)) {
        $query .= " AND b.category_id = ?";
        $params[] = $category_filter;
    }

    // Apply search filter
    if (!empty($search_query)) {
        $query .= " AND (b.business_name LIKE ? OR b.description LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $query .= " GROUP BY b.id ORDER BY b.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll();
    
    // Process location data for each business
    foreach ($businesses as &$business) {
        $business['business_location'] = extractLocation($business['business_location']);
    }

    // Get categories for filter
    $categories_stmt = $pdo->prepare("SELECT * FROM business_categories ORDER BY name");
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    $businesses = [];
    $categories = [];
}
?>

<div class="container mt-4">
    <h1>Browse Jewish Businesses</h1>
    
    <!-- Simple Search Form -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" class="d-flex gap-2">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="search" class="form-control" placeholder="Search businesses..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/businesses_simple.php" class="btn btn-outline-secondary">Reset</a>
            </form>
        </div>
    </div>

    <!-- Businesses List -->
    <div class="row">
        <?php if (empty($businesses)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h4>No businesses found</h4>
                    <p>Try adjusting your search criteria or <a href="/users/post_business.php">add your business</a>!</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($businesses as $business): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= getBusinessLogoUrl('', $business['business_name']) ?>" 
                                     alt="<?= htmlspecialchars($business['business_name']) ?>" 
                                     class="rounded me-3" 
                                     style="width: 50px; height: 50px; object-fit: cover;">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <a href="/business.php?id=<?= $business['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($business['business_name']) ?>
                                        </a>
                                    </h5>
                                    <small class="text-muted"><?= htmlspecialchars($business['category_name'] ?? 'Uncategorized') ?></small>
                                </div>
                            </div>
                            
                            <!-- Location Information -->
                            <p class="card-location mb-2">
                                <i class="fas fa-map-marker-alt text-muted me-1"></i> 
                                <?= htmlspecialchars($business['business_location'] ?? 'Location not specified') ?>
                            </p>
                            
                            <!-- Star Rating and Review Count -->
                            <div class="card-rating mb-3">
                                <?= generateStars($business['average_rating']) ?>
                                <?php if ($business['review_count'] > 0): ?>
                                    <span class="review-count text-muted ms-1">(<?= $business['review_count'] ?> reviews)</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($business['description'])): ?>
                                <p class="card-text"><?= htmlspecialchars(substr($business['description'], 0, 100)) ?>...</p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($business['subscription_tier'] === 'premium_plus'): ?>
                                    <span class="badge bg-warning text-dark">Elite</span>
                                <?php elseif ($business['subscription_tier'] === 'premium'): ?>
                                    <span class="badge bg-primary">Premium</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Basic</span>
                                <?php endif; ?>
                                
                                <a href="/business.php?id=<?= $business['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Stats -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-light">
                <strong>Total businesses found:</strong> <?= count($businesses) ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer_main.php'; ?> 