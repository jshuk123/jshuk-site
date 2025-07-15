<?php
/**
 * Volunteer Hub - Main Page
 * Browse and filter volunteer opportunities in the Jewish community
 */

require_once 'config/config.php';
require_once 'includes/volunteer_functions.php';

// Get filters from URL parameters
$filters = [
    'location' => $_GET['location'] ?? '',
    'frequency' => $_GET['frequency'] ?? '',
    'urgent' => isset($_GET['urgent']) ? (bool)$_GET['urgent'] : null,
    'search' => $_GET['search'] ?? '',
    'tags' => !empty($_GET['tags']) ? explode(',', $_GET['tags']) : []
];

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Get volunteer opportunities
$opportunities = getVolunteerOpportunities($filters, $limit, $offset);

// Get filter options
$locations = getVolunteerLocations();
$popular_tags = getPopularVolunteerTags(15);
$volunteer_types = getVolunteerTypes();

// SEO Meta
$page_title = "Volunteer Hub - Find & Share Chesed Opportunities | JShuk";
$page_description = "Connect with Jewish community volunteer opportunities. Find ways to help others and make a difference through acts of chesed. Browse tutoring, elderly care, food delivery, and more.";
$page_keywords = "volunteer, chesed, Jewish community, tutoring, elderly care, food delivery, community service, mitzvah";

// Include header
include 'includes/header_main.php';
?>

<!-- Volunteer Hub Hero Section -->
<section class="volunteer-hero bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 font-weight-bold mb-3">
                    🤝 Volunteer Hub
                </h1>
                <p class="lead mb-4">
                    Connect with Jewish community volunteer opportunities. Find ways to help others and make a difference through acts of chesed.
                </p>
                <div class="hero-stats mb-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-item">
                                <h3 class="text-warning mb-1"><?php echo count($opportunities); ?>+</h3>
                                <small>Active Opportunities</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <h3 class="text-warning mb-1">24/7</h3>
                                <small>Community Support</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <h3 class="text-warning mb-1">100%</h3>
                                <small>Jewish Values</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hero-cta">
                    <a href="/volunteer_post.php" class="btn btn-warning btn-lg mr-3">
                        <i class="fa fa-plus"></i> Post Opportunity
                    </a>
                    <a href="#opportunities" class="btn btn-outline-light btn-lg">
                        <i class="fa fa-search"></i> Browse Opportunities
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="hero-image">
                    <i class="fa fa-hands-helping" style="font-size: 8rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="volunteer-filters py-4 bg-light">
    <div class="container">
        <form method="GET" action="/volunteer.php" class="filter-form">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo h($filters['search']); ?>" 
                           placeholder="Search opportunities...">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="location" class="form-label">Location</label>
                    <select class="form-control" id="location" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo h($location['location']); ?>" 
                                    <?php echo $filters['location'] === $location['location'] ? 'selected' : ''; ?>>
                                <?php echo h($location['location']); ?> (<?php echo $location['count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="frequency" class="form-label">Frequency</label>
                    <select class="form-control" id="frequency" name="frequency">
                        <option value="">All</option>
                        <option value="one_time" <?php echo $filters['frequency'] === 'one_time' ? 'selected' : ''; ?>>One Time</option>
                        <option value="weekly" <?php echo $filters['frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo $filters['frequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="flexible" <?php echo $filters['frequency'] === 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="urgent" name="urgent" value="1" 
                               <?php echo $filters['urgent'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="urgent">
                            <i class="fa fa-exclamation-triangle text-danger"></i> Urgent Only
                        </label>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-filter"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Popular Tags -->
<section class="volunteer-tags py-3 bg-white">
    <div class="container">
        <h5 class="mb-3">Popular Categories:</h5>
        <div class="tag-cloud">
            <?php foreach ($popular_tags as $tag): ?>
                <a href="?tags=<?php echo urlencode($tag['tag']); ?>" 
                   class="badge badge-light mr-2 mb-2 p-2">
                    <?php 
                    $icon = '';
                    foreach ($volunteer_types as $type_key => $type_info) {
                        if (strpos($tag['tag'], $type_key) !== false) {
                            $icon = $type_info['icon'];
                            break;
                        }
                    }
                    ?>
                    <?php if ($icon): ?>
                        <i class="fa <?php echo $icon; ?> mr-1"></i>
                    <?php endif; ?>
                    #<?php echo h($tag['tag']); ?>
                    <span class="badge badge-secondary ml-1"><?php echo $tag['count']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Opportunities Section -->
<section id="opportunities" class="volunteer-opportunities py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Volunteer Opportunities</h2>
                    <div class="results-count">
                        <small class="text-muted">
                            Showing <?php echo count($opportunities); ?> opportunities
                        </small>
                    </div>
                </div>

                <?php if (empty($opportunities)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-search" style="font-size: 4rem; color: #ccc;"></i>
                        <h4 class="mt-3 text-muted">No opportunities found</h4>
                        <p class="text-muted">Try adjusting your filters or check back later for new opportunities.</p>
                        <a href="/volunteer.php" class="btn btn-outline-primary">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="opportunities-grid">
                        <?php foreach ($opportunities as $opportunity): ?>
                            <?php echo renderVolunteerCard($opportunity); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="/volunteer_post.php" class="btn btn-warning btn-block mb-2">
                            <i class="fa fa-plus"></i> Post Opportunity
                        </a>
                        <a href="/volunteer_profile.php" class="btn btn-outline-primary btn-block mb-2">
                            <i class="fa fa-user"></i> My Volunteer Profile
                        </a>
                        <a href="/volunteer_exchange.php" class="btn btn-outline-success btn-block">
                            <i class="fa fa-exchange-alt"></i> Chessed Exchange
                        </a>
                    </div>
                </div>

                <!-- Urgent Opportunities -->
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Urgent Needs</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $urgent_opportunities = getVolunteerOpportunities(['urgent' => true], 3);
                        if (empty($urgent_opportunities)): ?>
                            <p class="text-muted mb-0">No urgent opportunities at the moment.</p>
                        <?php else: ?>
                            <?php foreach ($urgent_opportunities as $urgent): ?>
                                <div class="urgent-item mb-3">
                                    <h6 class="mb-1">
                                        <a href="/volunteer/<?php echo h($urgent['slug']); ?>" class="text-danger">
                                            <?php echo h($urgent['title']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fa fa-map-marker-alt"></i> <?php echo h($urgent['location']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Volunteer Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-chart-bar"></i> Community Impact</h5>
                    </div>
                    <div class="card-body">
                        <div class="stat-item text-center mb-3">
                            <h4 class="text-primary mb-1"><?php echo count($opportunities); ?>+</h4>
                            <small class="text-muted">Active Opportunities</small>
                        </div>
                        <div class="stat-item text-center mb-3">
                            <h4 class="text-success mb-1">500+</h4>
                            <small class="text-muted">Hours of Chesed</small>
                        </div>
                        <div class="stat-item text-center">
                            <h4 class="text-warning mb-1">100+</h4>
                            <small class="text-muted">Volunteers</small>
                        </div>
                    </div>
                </div>

                <!-- Why Volunteer -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-heart"></i> Why Volunteer?</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                Strengthen community bonds
                            </li>
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                Fulfill mitzvot of chesed
                            </li>
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                Make a real difference
                            </li>
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                Earn badges and recognition
                            </li>
                            <li>
                                <i class="fa fa-check text-success mr-2"></i>
                                Build lasting relationships
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="volunteer-cta py-5 bg-warning">
    <div class="container text-center">
        <h3 class="mb-3">Ready to Make a Difference?</h3>
        <p class="lead mb-4">Join our community of volunteers and help strengthen the Jewish community through acts of chesed.</p>
        <div class="cta-buttons">
            <a href="/volunteer_post.php" class="btn btn-primary btn-lg mr-3">
                <i class="fa fa-plus"></i> Post an Opportunity
            </a>
            <a href="/volunteer_profile.php" class="btn btn-outline-dark btn-lg">
                <i class="fa fa-user"></i> Create Profile
            </a>
        </div>
    </div>
</section>

<!-- JSON-LD Schema for SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Volunteer Hub - JShuk",
  "description": "Connect with Jewish community volunteer opportunities. Find ways to help others and make a difference through acts of chesed.",
  "url": "https://jshuk.com/volunteer.php",
  "mainEntity": {
    "@type": "ItemList",
    "itemListElement": [
      <?php foreach (array_slice($opportunities, 0, 5) as $i => $opportunity): ?>
      {
        "@type": "ListItem",
        "position": <?php echo $i + 1; ?>,
        "item": {
          "@type": "VolunteerOpportunity",
          "name": "<?php echo addslashes($opportunity['title']); ?>",
          "description": "<?php echo addslashes($opportunity['summary']); ?>",
          "location": {
            "@type": "Place",
            "name": "<?php echo addslashes($opportunity['location']); ?>"
          },
          "url": "https://jshuk.com/volunteer/<?php echo $opportunity['slug']; ?>"
        }
      }<?php echo $i < min(4, count($opportunities) - 1) ? ',' : ''; ?>
      <?php endforeach; ?>
    ]
  }
}
</script>

<!-- Volunteer CSS -->
<link rel="stylesheet" href="/css/pages/volunteer.css">

<?php include 'includes/footer_main.php'; ?> 