<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

// Get all categories with business counts
$stmt = $pdo->query("
    SELECT 
        c.*,
        COUNT(b.id) as business_count
    FROM business_categories c
    LEFT JOIN businesses b ON c.id = b.category_id AND b.status = 'active'
    GROUP BY c.id
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll();

$pageTitle = "Business Categories";
$page_css = "categories.css";
include 'includes/header_main.php';
?>

<div class="container-fluid px-4 mt-4">
    <div class="row">
        <!-- Left Sidebar Ad -->
        <div class="col-lg-2">
            <?php include $_SERVER['DOCUMENT_ROOT'].'/partials/ads/sidebar_ads.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="row mb-4">
                <div class="col">
                    <h1 class="mb-0">Business Categories</h1>
                    <p class="text-muted">Browse businesses by category</p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($categories as $category): ?>

                    <?php
                    // Set default description
                    $description = 'Browse businesses in this category.';
                    switch (strtolower($category['name'])) {
                        case 'home services':
                            $description = 'Find trusted plumbers, electricians, handymen, and HVAC experts for all your home needs.';
                            break;
                        case 'health & beauty':
                            $description = 'Discover doctors, therapists, gyms, and wellness experts who cater to your lifestyle.';
                            break;
                        case 'food & beverages':
                            $description = 'Explore the best kosher restaurants, caterers, and grocery stores in your area.';
                            break;
                        case 'legal & financial services':
                            $description = 'Connect with lawyers, accountants, and financial advisors you can trust.';
                            break;
                        case 'education & training':
                            $description = 'Find tutors for GCSE, A level, and bar/bat mitzvah prep for all ages.';
                            break;
                    }
                    ?>

                    <div class="col-md-6">
                        <a href="category.php?category_id=<?php echo $category['id']; ?>" 
                           class="category-card text-decoration-none"
                           title="<?php echo htmlspecialchars($description); ?>">

                            <div class="card border-0 shadow-sm hover-shadow h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon-circle me-3">
                                        <?php
                                        // Use the icon from database (admin panel) or fallback to default
                                        $icon = $category['icon'] ?: 'fa-folder';
                                        $iconColor = 'text-primary'; // Default color
                                        ?>
                                        <i class="fa-solid <?= htmlspecialchars($icon) ?> fa-2x" style="color: #ffd000;"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1 text-dark">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </h5>
                                        <p class="card-text text-muted mb-0">
                                            <?php echo $category['business_count']; ?>
                                            <?php echo $category['business_count'] === 1 ? 'business' : 'businesses'; ?>
                                        </p>
                                        <p class="text-muted small"><?php echo $description; ?></p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Sidebar Ad -->
        <div class="col-lg-2">
            <?php include $_SERVER['DOCUMENT_ROOT'].'/partials/ads/sidebar_ads.php'; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer_main.php'; ?>
