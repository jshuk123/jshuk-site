<?php
require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Initialize variables
$posts = [];
$categories = [];
$locations = [];
$stats = ['total_posts' => 0, 'reunited_count' => 0];

// Get filter parameters
$post_type = $_GET['type'] ?? '';
$category = $_GET['category'] ?? '';
$location = $_GET['location'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$show_recent_only = isset($_GET['recent_only']) ? true : false;
$show_unresolved_only = isset($_GET['unresolved_only']) ? true : false;

try {
    if (isset($pdo) && $pdo) {
        // Load categories and locations
        $stmt = $pdo->query("SELECT name, icon FROM lostfound_categories WHERE is_active = 1 ORDER BY sort_order, name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT name, area FROM lostfound_locations WHERE is_active = 1 ORDER BY sort_order, name");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build query for posts
        $where_conditions = ["status != 'archived'"];
        $params = [];
        
        if ($post_type) {
            $where_conditions[] = "post_type = ?";
            $params[] = $post_type;
        }
        
        if ($category) {
            $where_conditions[] = "category = ?";
            $params[] = $category;
        }
        
        if ($location) {
            $where_conditions[] = "location = ?";
            $params[] = $location;
        }
        
        if ($search) {
            $where_conditions[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
            $search_param = "%{$search}%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if ($date_from) {
            $where_conditions[] = "date_lost_found >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where_conditions[] = "date_lost_found <= ?";
            $params[] = $date_to;
        }
        
        if ($show_recent_only) {
            $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        }
        
        if ($show_unresolved_only) {
            $where_conditions[] = "status = 'active'";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Get posts
        $query = "
            SELECT id, post_type, title, category, location, date_lost_found, 
                   description, image_paths, is_blurred, contact_phone, contact_email, 
                   contact_whatsapp, is_anonymous, hide_contact_until_verified, 
                   status, created_at
            FROM lostfound_posts 
            WHERE {$where_clause}
            ORDER BY created_at DESC
            LIMIT 50
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_posts WHERE status != 'archived'");
        $stats['total_posts'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_posts WHERE status = 'reunited'");
        $stats['reunited_count'] = $stmt->fetchColumn();
        
    }
} catch (PDOException $e) {
    if (APP_DEBUG) {
        error_log("Lost & Found query error: " . $e->getMessage());
    }
}

$pageTitle = "Lost & Found | JShuk - Community Lost & Found Board";
$page_css = "lostfound.css";
$metaDescription = "Post or search for lost items with full halachic sensitivity. Reuniting people with their belongings — one mitzvah at a time.";
$metaKeywords = "lost and found, jewish community, halachic lost found, lost items, found items, community help";

include 'includes/header_main.php';
?>

<!-- HERO SECTION -->
<section class="hero-section bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">
                    Lost Something? Found Something? Let the Community Help.
                </h1>
                <p class="lead mb-4">
                    Post or search for lost items with full halachic sensitivity. Reuniting people with their belongings — one mitzvah at a time.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="/post_lostfound.php" class="btn btn-warning btn-lg">
                        <i class="fas fa-plus me-2"></i>Post an Item
                    </a>
                    <a href="#filter-section" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-search me-2"></i>Browse Items
                    </a>
                </div>
                
                <!-- Mitzvah Counter -->
                <div class="mt-4 p-3 bg-white bg-opacity-10 rounded">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h4 mb-1">📦 <?= $stats['total_posts'] ?></div>
                            <small>Items Posted</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-1">✅ <?= $stats['reunited_count'] ?></div>
                            <small>Items Returned</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FILTER BAR -->
<section id="filter-section" class="py-4 bg-light">
    <div class="container">
        <form method="GET" action="" class="lostfound-filter-form">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search items..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                
                <!-- Post Type -->
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="lost" <?= $post_type === 'lost' ? 'selected' : '' ?>>Lost</option>
                        <option value="found" <?= $post_type === 'found' ? 'selected' : '' ?>>Found</option>
                    </select>
                </div>
                
                <!-- Category -->
                <div class="col-md-2">
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                    <?= $category === $cat['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Location -->
                <div class="col-md-2">
                    <select class="form-select" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc['name']) ?>" 
                                    <?= $location === $loc['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Date Range -->
                <div class="col-md-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" class="form-control" name="date_from" 
                                   placeholder="From" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control" name="date_to" 
                                   placeholder="To" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Toggle Filters -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="recent_only" id="recent_only" 
                               <?= $show_recent_only ? 'checked' : '' ?>>
                        <label class="form-check-label" for="recent_only">
                            Only show items posted in last 7 days
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="unresolved_only" id="unresolved_only" 
                               <?= $show_unresolved_only ? 'checked' : '' ?>>
                        <label class="form-check-label" for="unresolved_only">
                            Only show unresolved items
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Filter Actions -->
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Apply Filters
                    </button>
                    <a href="/lostfound.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear All
                    </a>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- LISTINGS SECTION -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Recent Items</h2>
                
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No items found</h4>
                        <p class="text-muted">Try adjusting your filters or be the first to post an item!</p>
                        <a href="/post_lostfound.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Post an Item
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="card lostfound-card h-100 shadow-sm">
                                    <!-- Status Badge -->
                                    <div class="card-badge">
                                        <?php if ($post['status'] === 'reunited'): ?>
                                            <span class="badge bg-success">✅ Reunited</span>
                                        <?php elseif ($post['post_type'] === 'lost'): ?>
                                            <span class="badge bg-danger">🔴 Lost</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">🔵 Found</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Image (if available) -->
                                    <?php if ($post['image_paths']): ?>
                                        <div class="card-img-top-container">
                                            <?php 
                                            $images = json_decode($post['image_paths'], true) ?: explode(',', $post['image_paths']);
                                            $first_image = is_array($images) ? $images[0] : $images;
                                            ?>
                                            <img src="<?= htmlspecialchars($first_image) ?>" 
                                                 class="card-img-top <?= $post['is_blurred'] ? 'blur-image' : '' ?>" 
                                                 alt="Item image">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                                        
                                        <div class="card-meta mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                                <span class="text-muted"><?= htmlspecialchars($post['location']) ?></span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <?php 
                                                $category_icon = 'fas fa-question-circle';
                                                foreach ($categories as $cat) {
                                                    if ($cat['name'] === $post['category']) {
                                                        $category_icon = $cat['icon'];
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <i class="<?= $category_icon ?> text-muted me-2"></i>
                                                <span class="text-muted"><?= htmlspecialchars($post['category']) ?></span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar text-muted me-2"></i>
                                                <span class="text-muted"><?= date('M j, Y', strtotime($post['date_lost_found'])) ?></span>
                                            </div>
                                        </div>
                                        
                                        <p class="card-text">
                                            <?= htmlspecialchars(substr($post['description'], 0, 200)) ?>
                                            <?= strlen($post['description']) > 200 ? '...' : '' ?>
                                        </p>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    onclick="openClaimModal(<?= $post['id'] ?>)">
                                                <i class="fas fa-handshake me-1"></i>I think this is mine
                                            </button>
                                            
                                            <!-- Contact Info (if not hidden) -->
                                            <?php if (!$post['hide_contact_until_verified']): ?>
                                                <div class="contact-buttons">
                                                    <?php if ($post['contact_whatsapp']): ?>
                                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $post['contact_whatsapp']) ?>" 
                                                           class="btn btn-success btn-sm" target="_blank">
                                                            <i class="fab fa-whatsapp"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($post['contact_email']): ?>
                                                        <a href="mailto:<?= htmlspecialchars($post['contact_email']) ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-envelope"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($post['contact_phone']): ?>
                                                        <a href="tel:<?= htmlspecialchars($post['contact_phone']) ?>" 
                                                           class="btn btn-secondary btn-sm">
                                                            <i class="fas fa-phone"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">Contact hidden until verified</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Load More Button -->
                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary" onclick="loadMoreItems()">
                            <i class="fas fa-plus me-2"></i>Load More Items
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Claim Modal -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-labelledby="claimModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="claimModalLabel">Claim This Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="claimForm" method="POST" action="/actions/submit_claim.php">
                <div class="modal-body">
                    <input type="hidden" name="post_id" id="claimPostId">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="claimant_name" class="form-label">Your Name *</label>
                                <input type="text" class="form-control" id="claimant_name" name="claimant_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="claim_date" class="form-label">When did you lose it? *</label>
                                <input type="date" class="form-control" id="claim_date" name="claim_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="claim_description" class="form-label">Describe the item in detail *</label>
                        <textarea class="form-control" id="claim_description" name="claim_description" rows="3" 
                                  placeholder="Please provide a detailed description of the item..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="simanim" class="form-label">Simanim (Identifying Signs) *</label>
                        <textarea class="form-control" id="simanim" name="simanim" rows="3" 
                                  placeholder="Describe unique identifying features, marks, or characteristics that only you would know..." required></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle text-info"></i>
                            Simanim are unique identifying signs that help verify ownership according to halacha.
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_email" class="form-label">Your Email *</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">Your Phone (optional)</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Sticky Button -->
<div class="d-md-none position-fixed bottom-0 end-0 m-3" style="z-index: 1000;">
    <a href="/post_lostfound.php" class="btn btn-warning btn-lg rounded-circle shadow">
        <i class="fas fa-plus"></i>
    </a>
</div>

<script>
function openClaimModal(postId) {
    document.getElementById('claimPostId').value = postId;
    new bootstrap.Modal(document.getElementById('claimModal')).show();
}

function loadMoreItems() {
    // TODO: Implement AJAX loading of more items
    alert('Load more functionality coming soon!');
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('.lostfound-filter-form');
    const filterInputs = filterForm.querySelectorAll('select, input[type="text"], input[type="date"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', () => {
            filterForm.submit();
        });
    });
});
</script>

<?php include 'includes/footer_main.php'; ?> 