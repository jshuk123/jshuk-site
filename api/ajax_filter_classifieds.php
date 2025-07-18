<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/config.php';

// Set JSON header for AJAX response
header('Content-Type: application/json');

try {
    // Get filter parameters from POST data
    $category_filter = $_POST['category'] ?? '';
    $search_query = $_POST['search'] ?? '';
    $current_sort_value = $_POST['sort'] ?? 'newest';
    $free_only = $_POST['free_only'] ?? '';
    $location_filter = $_POST['location'] ?? '';

    // Build the main query
    $query = "SELECT c.*, cc.name as category_name, cc.slug as category_slug, cc.icon as category_icon,
                     u.username as user_name
              FROM classifieds c
              LEFT JOIN classifieds_categories cc ON c.category_id = cc.id
              LEFT JOIN users u ON c.user_id = u.id
              WHERE c.is_active = 1";

    $params = [];

    // Apply category filter
    if (!empty($category_filter)) {
        $query .= " AND cc.slug = ?";
        $params[] = $category_filter;
    }

    // Apply search filter
    if (!empty($search_query)) {
        $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
        $search_param = "%$search_query%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Apply free items filter
    if ($free_only === '1') {
        $query .= " AND c.price = 0";
    }

    // Apply location filter
    if (!empty($location_filter)) {
        $query .= " AND c.location LIKE ?";
        $params[] = "%$location_filter%";
    }

    // Apply sorting
    switch ($current_sort_value) {
        case 'price_low_high':
            $query .= " ORDER BY c.price ASC, c.created_at DESC";
            break;
        case 'price_high_low':
            $query .= " ORDER BY c.price DESC, c.created_at DESC";
            break;
        case 'oldest':
            $query .= " ORDER BY c.created_at ASC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY c.created_at DESC";
            break;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $classifieds = $stmt->fetchAll();

    // Calculate result numbers for display
    $total_classifieds = count($classifieds);
    $start_result_number = $total_classifieds > 0 ? 1 : 0;
    $end_result_number = $total_classifieds;

    // Generate HTML for results
    ob_start();
    
    if (empty($classifieds)): ?>
        <div class="empty-state text-center py-5">
            <div class="empty-state-icon mb-3">
                <i class="fas fa-tags fa-3x text-muted"></i>
            </div>
            <h3>No Classifieds Found</h3>
            <p class="text-muted">Try adjusting your search criteria or be the first to post a classified ad!</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/submit_classified.php" class="btn-jshuk-primary">Post a Classified</a>
            <?php else: ?>
                <a href="/auth/login.php" class="btn-jshuk-primary">Login to Post</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($classifieds as $c): ?>
            <div class="classified-card-wrapper">
                <div class="classified-card">
                    <div class="classified-image">
                        <a href="/classified_view.php?id=<?= $c['id'] ?>">
                            <?php if ($c['image_path']): ?>
                                <img src="<?= htmlspecialchars($c['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($c['title']) ?>"
                                     onerror="this.src='/images/placeholder.png';">
                            <?php else: ?>
                                <img src="/images/placeholder.png" alt="No image available">
                            <?php endif; ?>
                        </a>
                        <?php if ($c['price'] == 0): ?>
                            <span class="badge-free">♻️ Free</span>
                        <?php endif; ?>
                        <?php if ($c['is_chessed']): ?>
                            <span class="badge-chessed">💝 Chessed</span>
                        <?php endif; ?>
                        <?php if ($c['is_bundle']): ?>
                            <span class="badge-bundle">📦 Bundle</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="classified-content">
                        <div class="classified-header">
                            <h3 class="classified-title">
                                <a href="/classified_view.php?id=<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['title']) ?>
                                </a>
                            </h3>
                            <div class="classified-price">
                                <?= ($c['price'] > 0) ? '£' . number_format($c['price'], 2) : '♻️ Free' ?>
                            </div>
                        </div>
                        
                        <?php if ($c['category_name']): ?>
                            <div class="classified-category">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars($c['category_name']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($c['location']): ?>
                            <div class="classified-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($c['location']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="classified-description">
                            <?= htmlspecialchars(mb_strimwidth($c['description'], 0, 100, '...')) ?>
                        </div>
                        
                        <div class="classified-meta">
                            <span class="classified-date">
                                <i class="fas fa-clock"></i>
                                <?= date('M j, Y', strtotime($c['created_at'])) ?>
                            </span>
                            <?php if ($c['price'] == 0 && $c['pickup_method']): ?>
                                <span class="classified-pickup">
                                    <i class="fas fa-handshake"></i>
                                    <?php
                                    switch($c['pickup_method']) {
                                        case 'porch_pickup': echo 'Porch Pickup'; break;
                                        case 'contact_arrange': echo 'Contact to Arrange'; break;
                                        case 'collection_code': echo 'Collection Code'; break;
                                        default: echo 'Contact Seller';
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($c['price'] == 0 && $c['status']): ?>
                                <span class="classified-status status-<?= $c['status'] ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= ucfirst($c['status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="classified-actions">
                            <?php if ($c['price'] == 0): ?>
                                <a href="/classified_view.php?id=<?= $c['id'] ?>" class="btn-view btn-request">
                                    <span>Request This Item</span>
                                    <i class="fas fa-gift"></i>
                                </a>
                            <?php else: ?>
                                <a href="/classified_view.php?id=<?= $c['id'] ?>" class="btn-view">
                                    <span>View Details</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php
    $results_html = ob_get_clean();

    // Return JSON response
    echo json_encode([
        'success' => true,
        'results_html' => $results_html,
        'total_classifieds' => $total_classifieds,
        'start_result_number' => $start_result_number,
        'end_result_number' => $end_result_number,
        'filters_applied' => [
            'category' => $category_filter,
            'search' => $search_query,
            'sort' => $current_sort_value,
            'free_only' => $free_only,
            'location' => $location_filter
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 