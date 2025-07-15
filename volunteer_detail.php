<?php
/**
 * Volunteer Opportunity Detail Page
 * View full details of a volunteer opportunity and express interest
 */

require_once 'config/config.php';
require_once 'includes/volunteer_functions.php';

// Get opportunity ID or slug from URL
$identifier = $_GET['id'] ?? $_GET['slug'] ?? '';

if (empty($identifier)) {
    redirect('/volunteer.php');
}

// Get opportunity details
$opportunity = getVolunteerOpportunity($identifier);

if (!$opportunity) {
    http_response_code(404);
    include '404.php';
    exit;
}

// Increment view count
if ($pdo) {
    $update_sql = "UPDATE volunteer_opportunities SET views_count = views_count + 1 WHERE id = :id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->bindParam(':id', $opportunity['id']);
    $update_stmt->execute();
}

$user_id = getCurrentUserId();
$success_message = '';
$error_message = '';

// Handle interest expression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['express_interest'])) {
    if (!isLoggedIn()) {
        redirect('/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $message = trim($_POST['message'] ?? '');
        
        if (expressInterest($opportunity['id'], $user_id, $message)) {
            $success_message = 'Thank you for your interest! The opportunity poster will be notified and may contact you soon.';
        } else {
            $error_message = 'You have already expressed interest in this opportunity.';
        }
    }
}

// Parse tags
$tags = json_decode($opportunity['tags'], true) ?? [];
$preferred_times = json_decode($opportunity['preferred_times'], true) ?? [];

// SEO Meta
$page_title = h($opportunity['title']) . " - JShuk Volunteer Hub";
$page_description = h($opportunity['summary']);
$page_keywords = implode(', ', $tags);

// Include header
include 'includes/header_main.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-2">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/volunteer.php">Volunteer Hub</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo h($opportunity['title']); ?></li>
        </ol>
    </div>
</nav>

<!-- Opportunity Header -->
<section class="opportunity-header py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="opportunity-meta mb-3">
                    <?php echo getVolunteerStatusBadge($opportunity['status'], $opportunity['urgent']); ?>
                    <span class="text-muted ml-2">
                        <i class="fa fa-eye"></i> <?php echo $opportunity['views_count'] + 1; ?> views
                    </span>
                    <span class="text-muted ml-2">
                        <i class="fa fa-users"></i> <?php echo $opportunity['interests_count']; ?> interested
                    </span>
                </div>
                
                <h1 class="opportunity-title mb-3"><?php echo h($opportunity['title']); ?></h1>
                
                <div class="opportunity-summary mb-4">
                    <p class="lead text-muted"><?php echo h($opportunity['summary']); ?></p>
                </div>
                
                <div class="opportunity-tags mb-4">
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge badge-light mr-2 mb-2">
                            #<?php echo h($tag); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="opportunity-actions">
                    <?php if (isLoggedIn()): ?>
                        <?php if ($opportunity['posted_by'] == $user_id): ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> This is your opportunity
                            </div>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary btn-lg btn-block mb-3" 
                                    data-toggle="modal" data-target="#interestModal">
                                <i class="fa fa-heart"></i> I'm Interested
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                           class="btn btn-primary btn-lg btn-block mb-3">
                            <i class="fa fa-sign-in-alt"></i> Login to Express Interest
                        </a>
                    <?php endif; ?>
                    
                    <a href="/volunteer.php" class="btn btn-outline-secondary btn-block">
                        <i class="fa fa-arrow-left"></i> Back to Opportunities
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Opportunity Details -->
<section class="opportunity-details py-4 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fa fa-info-circle"></i> Opportunity Details
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="opportunity-description mb-4">
                            <?php echo nl2br(h($opportunity['description'])); ?>
                        </div>
                        
                        <div class="opportunity-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <strong><i class="fa fa-map-marker-alt text-primary"></i> Location:</strong>
                                        <span class="ml-2"><?php echo h($opportunity['location']); ?></span>
                                    </div>
                                    
                                    <div class="info-item mb-3">
                                        <strong><i class="fa fa-clock text-primary"></i> Frequency:</strong>
                                        <span class="ml-2"><?php echo formatVolunteerFrequency($opportunity['frequency']); ?></span>
                                    </div>
                                    
                                    <?php if ($opportunity['date_needed']): ?>
                                        <div class="info-item mb-3">
                                            <strong><i class="fa fa-calendar text-primary"></i> Date Needed:</strong>
                                            <span class="ml-2"><?php echo formatDate($opportunity['date_needed']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($opportunity['time_needed']): ?>
                                        <div class="info-item mb-3">
                                            <strong><i class="fa fa-clock text-primary"></i> Time Needed:</strong>
                                            <span class="ml-2"><?php echo date('g:i A', strtotime($opportunity['time_needed'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <?php if ($opportunity['chessed_hours'] > 0): ?>
                                        <div class="info-item mb-3">
                                            <strong><i class="fa fa-hourglass-half text-primary"></i> Estimated Hours:</strong>
                                            <span class="ml-2"><?php echo $opportunity['chessed_hours']; ?> hours</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($preferred_times)): ?>
                                        <div class="info-item mb-3">
                                            <strong><i class="fa fa-calendar-check text-primary"></i> Preferred Times:</strong>
                                            <div class="ml-2 mt-1">
                                                <?php foreach ($preferred_times as $time): ?>
                                                    <span class="badge badge-light mr-1"><?php echo ucfirst($time); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-item mb-3">
                                        <strong><i class="fa fa-user text-primary"></i> Posted By:</strong>
                                        <span class="ml-2"><?php echo h($opportunity['posted_by_name'] ?? 'Anonymous'); ?></span>
                                    </div>
                                    
                                    <div class="info-item mb-3">
                                        <strong><i class="fa fa-calendar-plus text-primary"></i> Posted:</strong>
                                        <span class="ml-2"><?php echo formatRelativeDate($opportunity['created_at']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <?php if ($opportunity['contact_method'] !== 'internal' && !empty($opportunity['contact_info'])): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-phone"></i> Contact Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="contact-info">
                                <strong>Contact Method:</strong> <?php echo ucfirst($opportunity['contact_method']); ?><br>
                                <strong>Contact:</strong> <?php echo h($opportunity['contact_info']); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Similar Opportunities -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fa fa-lightbulb"></i> Similar Opportunities
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $similar_opportunities = getVolunteerOpportunities(['location' => $opportunity['location']], 3);
                        $similar_opportunities = array_filter($similar_opportunities, function($opp) use ($opportunity) {
                            return $opp['id'] !== $opportunity['id'];
                        });
                        ?>
                        
                        <?php if (empty($similar_opportunities)): ?>
                            <p class="text-muted mb-0">No similar opportunities found.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($similar_opportunities, 0, 3) as $similar): ?>
                                <div class="similar-item mb-3">
                                    <h6 class="mb-1">
                                        <a href="/volunteer_detail.php?slug=<?php echo h($similar['slug']); ?>" class="text-primary">
                                            <?php echo h($similar['title']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fa fa-map-marker-alt"></i> <?php echo h($similar['location']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fa fa-chart-bar"></i> Opportunity Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="stat-item text-center mb-3">
                            <h4 class="text-primary mb-1"><?php echo $opportunity['views_count'] + 1; ?></h4>
                            <small class="text-muted">Views</small>
                        </div>
                        <div class="stat-item text-center mb-3">
                            <h4 class="text-success mb-1"><?php echo $opportunity['interests_count']; ?></h4>
                            <small class="text-muted">Interested</small>
                        </div>
                        <?php if ($opportunity['chessed_hours'] > 0): ?>
                            <div class="stat-item text-center">
                                <h4 class="text-warning mb-1"><?php echo $opportunity['chessed_hours']; ?></h4>
                                <small class="text-muted">Hours</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Share -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fa fa-share-alt"></i> Share This Opportunity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="share-buttons">
                            <a href="https://wa.me/?text=<?php echo urlencode('Check out this volunteer opportunity: ' . $opportunity['title'] . ' - ' . $_SERVER['REQUEST_URI']); ?>" 
                               class="btn btn-success btn-sm btn-block mb-2" target="_blank">
                                <i class="fa fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode('Volunteer Opportunity: ' . $opportunity['title']); ?>&body=<?php echo urlencode('Check out this volunteer opportunity: ' . $_SERVER['REQUEST_URI']); ?>" 
                               class="btn btn-primary btn-sm btn-block mb-2">
                                <i class="fa fa-envelope"></i> Email
                            </a>
                            <button type="button" class="btn btn-info btn-sm btn-block" onclick="copyToClipboard()">
                                <i class="fa fa-link"></i> Copy Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Interest Modal -->
<?php if (isLoggedIn() && $opportunity['posted_by'] != $user_id): ?>
<div class="modal fade" id="interestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-heart"></i> Express Interest
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p>You're expressing interest in: <strong><?php echo h($opportunity['title']); ?></strong></p>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message (Optional)</label>
                        <textarea class="form-control" id="message" name="message" rows="4" 
                                  placeholder="Tell them why you're interested or ask any questions..."></textarea>
                        <small class="form-text text-muted">This message will be sent to the opportunity poster</small>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="express_interest" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-heart"></i> Express Interest
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Success/Error Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed" 
         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
        <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show position-fixed" 
         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- JSON-LD Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "VolunteerOpportunity",
  "name": "<?php echo addslashes($opportunity['title']); ?>",
  "description": "<?php echo addslashes($opportunity['description']); ?>",
  "location": {
    "@type": "Place",
    "name": "<?php echo addslashes($opportunity['location']); ?>"
  },
  "url": "<?php echo $_SERVER['REQUEST_URI']; ?>",
  "datePosted": "<?php echo $opportunity['created_at']; ?>",
  "validThrough": "<?php echo $opportunity['expires_at'] ?: date('Y-m-d', strtotime('+30 days')); ?>",
  "numberOfPositions": 1,
  "employmentType": "VOLUNTEER"
}
</script>

<!-- Volunteer Detail CSS -->
<link rel="stylesheet" href="/css/pages/volunteer_detail.css">

<!-- JavaScript -->
<script>
function copyToClipboard() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        // Show success message
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            <i class="fa fa-check-circle"></i> Link copied to clipboard!
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        document.body.appendChild(alert);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            alert.remove();
        }, 3000);
    });
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert.position-fixed');
        alerts.forEach(alert => {
            alert.remove();
        });
    }, 5000);
});
</script>

<?php include 'includes/footer_main.php'; ?> 