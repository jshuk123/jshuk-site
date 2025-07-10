<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's subscription info
$stmt = $pdo->prepare("
    SELECT s.*, p.name as plan_name, p.image_limit, p.testimonial_limit,
           p.whatsapp_features, p.newsletter_features, p.price
    FROM user_subscriptions s
    JOIN subscription_plans p ON s.plan_id = p.id
    WHERE s.user_id = ? 
    AND s.status IN ('active', 'trialing')
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$subscription = $stmt->fetch();

// Set default limits for basic plan
$image_limit = 1;
$testimonial_limit = 0;
$can_add_phone = false;
$can_add_address = false;
$can_add_whatsapp = false;
$extended_description = false;
$plan_name = 'Basic';
$is_trial = false;

// Update limits based on subscription
if ($subscription) {
    $image_limit = $subscription['image_limit'] === null ? 'Unlimited' : $subscription['image_limit'];
    $testimonial_limit = $subscription['testimonial_limit'] === null ? 'Unlimited' : $subscription['testimonial_limit'];
    $plan_name = $subscription['plan_name'];
    $is_trial = $subscription['status'] === 'trialing';
    
    // Premium and Premium Plus features
    if ($plan_name === 'Premium' || $plan_name === 'Premium Plus') {
        $can_add_phone = true;
        $can_add_address = true;
        $can_add_whatsapp = true;
        $extended_description = true;
    }
}

// Get user's businesses
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name 
    FROM businesses b 
    LEFT JOIN business_categories c ON b.category_id = c.id 
    WHERE b.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$businesses = $stmt->fetchAll();

// Fetch main image for each business
$img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
foreach ($businesses as &$business) {
    $img_stmt->execute([$business['id']]);
    $main_image_path = $img_stmt->fetchColumn();
    $business['main_image'] = $main_image_path ? $main_image_path : '/images/default-business.jpg';
}
unset($business);

$pageTitle = "My Profile";
$page_css = "profile.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($user['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars('/uploads/profiles/' . $user['profile_image']); ?>" 
                                 alt="Profile" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        <?php endif; ?>
                        <h3 class="mt-3"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h5>Contact Information</h5>
                        <p><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <?php if ($user['phone']): ?>
                            <p><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <h5>Account Details</h5>
                        <p><i class="fas fa-calendar me-2"></i> Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                        <p><i class="fas fa-check-circle me-2"></i> Email Verified</p>
                    </div>
                    
                    <div class="text-center">
                        <a href="/users/complete_profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Add this section to show subscription info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Subscription Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Current Plan: <?php echo htmlspecialchars($plan_name); ?></h6>
                            <?php if ($is_trial): ?>
                                <p class="text-info">
                                    <i class="fas fa-clock"></i> Trial Period Active
                                    (Ends: <?php echo date('F j, Y', strtotime($subscription['trial_end'])); ?>)
                                </p>
                            <?php endif; ?>
                            
                            <ul class="list-unstyled">
                                <li><i class="fas fa-image"></i> Images: <?php echo $image_limit; ?></li>
                                <li><i class="fas fa-comment"></i> Testimonials: <?php echo $testimonial_limit; ?></li>
                                <?php if ($can_add_phone): ?>
                                    <li><i class="fas fa-phone"></i> Phone number display enabled</li>
                                <?php endif; ?>
                                <?php if ($can_add_address): ?>
                                    <li><i class="fas fa-map-marker-alt"></i> Business address display enabled</li>
                                <?php endif; ?>
                                <?php if ($can_add_whatsapp): ?>
                                    <li><i class="fab fa-whatsapp"></i> WhatsApp integration enabled</li>
                                <?php endif; ?>
                                <?php if ($extended_description): ?>
                                    <li><i class="fas fa-align-left"></i> Extended business description</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="/payment/subscription.php" class="btn btn-primary">
                                <?php if ($subscription): ?>
                                    Manage Subscription
                                <?php else: ?>
                                    Upgrade Plan
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User's Businesses -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">My Businesses</h4>
                        <a href="/users/post_business.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Business
                        </a>
                    </div>
                    
                    <?php if (empty($businesses)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-store fa-3x text-muted mb-3"></i>
                            <h5>No Businesses Yet</h5>
                            <p class="text-muted">Start by adding your first business listing</p>
                            <a href="/users/post_business.php" class="btn btn-primary">
                                Add Your First Business
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($businesses as $business): ?>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <img src="<?php echo htmlspecialchars($business['main_image']); ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($business['business_name']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($business['business_name']); ?></h5>
                                            <p class="card-text text-muted">
                                                <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($business['category_name']); ?>
                                            </p>
                                            <p class="card-text"><?php echo substr(htmlspecialchars($business['description']), 0, 100); ?>...</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?php echo $business['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($business['status']); ?>
                                                </span>
                                                <a href="/business.php?id=<?php echo $business['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Management -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Testimonials Management</h4>
                        <div>
                            <?php if ($testimonial_limit === null): ?>
                                <span class="badge bg-success">Unlimited Testimonials</span>
                            <?php else: ?>
                                <span class="badge bg-primary">
                                    <?php 
                                    // Get total testimonials count
                                    $stmt = $pdo->prepare("
                                        SELECT COUNT(*) FROM testimonials t
                                        JOIN businesses b ON t.business_id = b.id
                                        WHERE b.user_id = ?
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $total_testimonials = $stmt->fetchColumn();
                                    echo "{$total_testimonials}/{$testimonial_limit} Testimonials Used";
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    // Get all testimonials for user's businesses
                    $stmt = $pdo->prepare("
                        SELECT t.*, b.business_name 
                        FROM testimonials t
                        JOIN businesses b ON t.business_id = b.id
                        WHERE b.user_id = ?
                        ORDER BY t.created_at DESC
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $testimonials = $stmt->fetchAll();
                    ?>

                    <?php if (empty($testimonials)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
                            <h5>No Testimonials Yet</h5>
                            <p class="text-muted">Add testimonials to showcase your client feedback</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Business</th>
                                        <th>Client</th>
                                        <th>Rating</th>
                                        <th>Content</th>
                                        <th>Featured</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($testimonials as $testimonial): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($testimonial['business_name']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($testimonial['author_name']); ?>
                                                <?php if ($testimonial['author_title']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($testimonial['author_title']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="rating">
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;">
                                                    <?php echo htmlspecialchars($testimonial['content']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($testimonial['is_featured']): ?>
                                                    <span class="badge bg-success">Featured</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Not Featured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($testimonial['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editTestimonial(<?php echo htmlspecialchars(json_encode($testimonial)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteTestimonial(<?php echo $testimonial['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Testimonial Modal -->
<div class="modal fade" id="editTestimonialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTestimonialForm" action="/actions/edit_testimonial.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="testimonial_id" id="edit_testimonial_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Client Name*</label>
                        <input type="text" class="form-control" name="author_name" id="edit_author_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Client Title/Position</label>
                        <input type="text" class="form-control" name="author_title" id="edit_author_title">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rating*</label>
                        <div class="rating-input">
                            <div class="star-rating">
                                <?php for($i = 5; $i >= 1; $i--): ?>
                                    <i class="fas fa-star star-selectable" data-rating="<?php echo $i; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="edit_rating" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Testimonial Content*</label>
                        <textarea class="form-control" name="content" id="edit_content" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Client Photo</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <div id="current_image" class="mt-2"></div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured">
                            <label class="form-check-label" for="edit_is_featured">
                                Feature this testimonial
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Testimonial Modal -->
<div class="modal fade" id="deleteTestimonialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this testimonial? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="/actions/delete_testimonial.php" method="POST" style="display: inline;">
                    <input type="hidden" name="testimonial_id" id="delete_testimonial_id">
                    <button type="submit" class="btn btn-danger">Delete Testimonial</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editTestimonial(testimonial) {
    // Populate modal fields
    document.getElementById('edit_testimonial_id').value = testimonial.id;
    document.getElementById('edit_author_name').value = testimonial.author_name;
    document.getElementById('edit_author_title').value = testimonial.author_title || '';
    document.getElementById('edit_rating').value = testimonial.rating;
    document.getElementById('edit_content').value = testimonial.content;
    document.getElementById('edit_is_featured').checked = testimonial.is_featured == 1;
    
    // Update star rating display
    updateStars(testimonial.rating);
    
    // Show current image if exists
    const currentImageDiv = document.getElementById('current_image');
    if (testimonial.image_path) {
        currentImageDiv.innerHTML = `
            <img src="${testimonial.image_path}" alt="Current image" class="img-thumbnail" style="height: 100px;">
            <div class="mt-2">
                <small class="text-muted">Current image (upload new to replace)</small>
            </div>
        `;
    } else {
        currentImageDiv.innerHTML = '';
    }
    
    // Show modal
    new bootstrap.Modal(document.getElementById('editTestimonialModal')).show();
}

function deleteTestimonial(id) {
    document.getElementById('delete_testimonial_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteTestimonialModal')).show();
}

// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const starContainer = document.querySelector('#editTestimonialModal .star-rating');
    const stars = starContainer.querySelectorAll('.star-selectable');
    const ratingInput = document.getElementById('edit_rating');
    
    function updateStars(rating) {
        stars.forEach(star => {
            const starRating = parseInt(star.dataset.rating);
            if (starRating <= rating) {
                star.classList.add('text-warning');
            } else {
                star.classList.remove('text-warning');
            }
        });
        ratingInput.value = rating;
    }
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            updateStars(rating);
        });
        
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.dataset.rating);
            updateStars(rating);
        });
    });
    
    starContainer.addEventListener('mouseleave', function() {
        updateStars(ratingInput.value);
    });
});
</script>

<?php include '../includes/footer_main.php'; ?> 