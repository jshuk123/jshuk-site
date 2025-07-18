<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/config.php';

// Set JSON header for AJAX response
header('Content-Type: application/json');

// Helper functions
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

function generateStars($rating) {
    $rating = floatval($rating);
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
    
    $stars = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fas fa-star text-warning"></i>';
    }
    if ($hasHalfStar) {
        $stars .= '<i class="fas fa-star-half-alt text-warning"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '<i class="far fa-star text-warning"></i>';
    }
    
    return '<span class="stars">' . $stars . '</span>';
}

function extractLocation($address) {
    if (empty($address)) return '';
    
    // Common London areas
    $areas = ['hendon', 'golders green', 'stanmore', 'edgware', 'finchley', 'barnet', 'manchester', 'london', 'stamford-hill'];
    
    $address_lower = strtolower($address);
    foreach ($areas as $area) {
        if (strpos($address_lower, $area) !== false) {
            return ucwords($area);
        }
    }
    
    return $address;
}

function checked($current_values, $value) {
    if (is_array($current_values)) {
        return in_array($value, $current_values) ? 'checked' : '';
    }
    return '';
}

function selected($current, $value) {
    return $current == $value ? 'selected' : '';
}

try {
    // Get filter parameters from POST data
    $category_filter = $_POST['category'] ?? '';
    $search_query = $_POST['search'] ?? '';
    $current_sort_value = $_POST['sort'] ?? 'newest';
    $location_filters = $_POST['locations'] ?? [];
    $rating_filter = $_POST['rating'] ?? '';

    // Enhanced query to get businesses with location and rating data
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

    // Apply location filters
    if (!empty($location_filters) && is_array($location_filters)) {
        $location_conditions = [];
        foreach ($location_filters as $location) {
            $location_conditions[] = "COALESCE(b.location, b.address) LIKE ?";
            $params[] = "%$location%";
        }
        $query .= " AND (" . implode(" OR ", $location_conditions) . ")";
    }

    $query .= " GROUP BY b.id";
    
    // Apply rating filter
    if (!empty($rating_filter) && is_numeric($rating_filter)) {
        $query .= " HAVING average_rating >= ?";
        $params[] = $rating_filter;
    }
    
    // Apply sorting
    switch ($current_sort_value) {
        case 'reviews':
            $query .= " ORDER BY review_count DESC, b.business_name ASC";
            break;
        case 'alphabetical':
            $query .= " ORDER BY b.business_name ASC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY b.created_at DESC";
            break;
    }

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

    // Calculate result numbers for display
    $total_businesses = count($businesses);
    $start_result_number = $total_businesses > 0 ? 1 : 0;
    $end_result_number = $total_businesses;

    // Generate HTML for results
    ob_start();
    
    if (empty($businesses)): ?>
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
    <?php endif;
    
    $results_html = ob_get_clean();
    
    // Generate updated filter sidebar HTML
    ob_start(); ?>
    <form method="GET" class="filter-form">
        <!-- Existing Filters -->
        <div class="filter-block">
            <h4>Search & Category</h4>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <select name="category" id="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Search businesses..." 
                       value="<?= htmlspecialchars($search_query) ?>">
            </div>
        </div>

        <!-- Location Filter -->
        <div class="filter-block">
            <h4>Filter by Location</h4>
            <div class="filter-options">
                <label class="filter-option">
                    <input type="checkbox" name="locations[]" value="hendon" <?= checked($location_filters, 'hendon'); ?>>
                    <span>Hendon</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" name="locations[]" value="golders green" <?= checked($location_filters, 'golders green'); ?>>
                    <span>Golders Green</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" name="locations[]" value="stanmore" <?= checked($location_filters, 'stanmore'); ?>>
                    <span>Stanmore</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" name="locations[]" value="edgware" <?= checked($location_filters, 'edgware'); ?>>
                    <span>Edgware</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" name="locations[]" value="finchley" <?= checked($location_filters, 'finchley'); ?>>
                    <span>Finchley</span>
                </label>
                <label class="filter-option">
                    <input type="checkbox" name="locations[]" value="barnet" <?= checked($location_filters, 'barnet'); ?>>
                    <span>Barnet</span>
                </label>
            </div>
        </div>

        <!-- Rating Filter -->
        <div class="filter-block">
            <h4>Filter by Rating</h4>
            <div class="filter-options">
                <label class="filter-option">
                    <input type="radio" name="rating" value="5" <?= $rating_filter == '5' ? 'checked' : '' ?>>
                    <span>★★★★★ 5 stars & up</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="rating" value="4" <?= $rating_filter == '4' ? 'checked' : '' ?>>
                    <span>★★★★☆ 4 stars & up</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="rating" value="3" <?= $rating_filter == '3' ? 'checked' : '' ?>>
                    <span>★★★☆☆ 3 stars & up</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="rating" value="2" <?= $rating_filter == '2' ? 'checked' : '' ?>>
                    <span>★★☆☆☆ 2 stars & up</span>
                </label>
            </div>
        </div>

        <!-- Preserve sort parameter -->
        <input type="hidden" name="sort" value="<?= htmlspecialchars($current_sort_value) ?>">
        
        <!-- Action Buttons -->
        <div class="filter-actions">
            <button type="submit" class="btn-jshuk-primary">Apply Filters</button>
            <a href="/businesses.php" class="btn btn-outline-secondary">Reset All</a>
        </div>
    </form>
    <?php
    $sidebar_html = ob_get_clean();
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'results_html' => $results_html,
        'sidebar_html' => $sidebar_html,
        'total_businesses' => $total_businesses,
        'start_result_number' => $start_result_number,
        'end_result_number' => $end_result_number,
        'filters' => [
            'category' => $category_filter,
            'search' => $search_query,
            'sort' => $current_sort_value,
            'locations' => $location_filters,
            'rating' => $rating_filter
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 