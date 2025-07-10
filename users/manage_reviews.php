<?php
/**
 * Business Testimonial Management
 * Allows business owners to moderate testimonials based on subscription tier
 * Version: 1.2
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../includes/subscription_functions.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's businesses
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name,
           (SELECT COUNT(*) FROM testimonials t WHERE t.business_id = b.id AND t.status = 'approved') as approved_count,
           (SELECT COUNT(*) FROM testimonials t WHERE t.business_id = b.id AND t.status = 'pending') as pending_count
    FROM businesses b
    LEFT JOIN business_categories c ON b.category_id = c.id
    WHERE b.user_id = ? AND b.status = 'active'
    ORDER BY b.business_name
");
$stmt->execute([$user_id]);
$businesses = $stmt->fetchAll();

// Get user's subscription info
$stmt = $pdo->prepare("
    SELECT s.*, p.name as plan_name, p.testimonial_limit
    FROM user_subscriptions s
    JOIN subscription_plans p ON s.plan_id = p.id
    WHERE s.user_id = ? 
    AND s.status IN ('active', 'trialing')
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$subscription = $stmt->fetch();

// Set default limits
$testimonial_limit = $subscription['testimonial_limit'] ?? 0;
$plan_name = $subscription['plan_name'] ?? 'Basic';

$pageTitle = "Manage Reviews & Testimonials | JShuk";
include '../includes/header_main.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-star me-2"></i>
                            Manage Reviews & Testimonials
                        </h4>
                        <div class="subscription-badge">
                            <span class="badge bg-light text-dark">
                                <?php echo htmlspecialchars($plan_name); ?> Plan
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Subscription Info -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Your Plan: <?php echo htmlspecialchars($plan_name); ?></h6>
                        <p class="mb-0">
                            <?php if ($testimonial_limit === null): ?>
                                <strong>Unlimited testimonials</strong> - You can approve as many testimonials as you want.
                            <?php else: ?>
                                <strong><?php echo $testimonial_limit; ?> testimonials maximum</strong> - You can approve up to <?php echo $testimonial_limit; ?> testimonials per business.
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Business Selection -->
                    <div class="mb-4">
                        <label for="business-select" class="form-label fw-bold">Select Business:</label>
                        <select id="business-select" class="form-select" onchange="loadTestimonials()">
                            <option value="">Choose a business...</option>
                            <?php foreach ($businesses as $business): ?>
                                <option value="<?php echo $business['id']; ?>">
                                    <?php echo htmlspecialchars($business['business_name']); ?>
                                    (<?php echo $business['approved_count']; ?>/<?php echo $testimonial_limit === null ? '∞' : $testimonial_limit; ?> approved)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Testimonials Container -->
                    <div id="testimonials-container">
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5>Select a Business</h5>
                            <p class="text-muted">Choose a business above to manage its testimonials</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonial Management Modal -->
<div class="modal fade" id="testimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="testimonialModalBody">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
let currentBusinessId = null;
let testimonialLimit = <?php echo $testimonial_limit === null ? 'null' : $testimonial_limit; ?>;

function loadTestimonials() {
    const businessId = document.getElementById('business-select').value;
    if (!businessId) {
        document.getElementById('testimonials-container').innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h5>Select a Business</h5>
                <p class="text-muted">Choose a business above to manage its testimonials</p>
            </div>
        `;
        return;
    }

    currentBusinessId = businessId;
    
    // Show loading
    document.getElementById('testimonials-container').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading testimonials...</p>
        </div>
    `;

    // Fetch testimonials
    fetch(`/actions/get_business_testimonials.php?business_id=${businessId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTestimonials(data.testimonials, data.stats);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load testimonials');
        });
}

function displayTestimonials(testimonials, stats) {
    const container = document.getElementById('testimonials-container');
    
    let html = `
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Testimonial Stats</h5>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-primary fw-bold">${stats.pending}</div>
                                <small class="text-muted">Pending</small>
                            </div>
                            <div class="col-4">
                                <div class="text-success fw-bold">${stats.approved}</div>
                                <small class="text-muted">Approved</small>
                            </div>
                            <div class="col-4">
                                <div class="text-secondary fw-bold">${stats.hidden}</div>
                                <small class="text-muted">Hidden</small>
                            </div>
                        </div>
                        ${testimonialLimit !== null ? `
                            <div class="mt-3">
                                <small class="text-muted">
                                    ${stats.approved}/${testimonialLimit} testimonials used
                                </small>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h5>Testimonials</h5>
    `;

    if (testimonials.length === 0) {
        html += `
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                <p class="text-muted">No testimonials found for this business</p>
            </div>
        `;
    } else {
        testimonials.forEach(testimonial => {
            const statusBadge = getStatusBadge(testimonial.status);
            const featuredBadge = testimonial.featured ? '<span class="badge bg-warning ms-2">Featured</span>' : '';
            
            html += `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">${testimonial.name || 'Anonymous'}</h6>
                                <div class="text-muted small">
                                    ${formatDate(testimonial.submitted_at)}
                                    ${statusBadge}
                                    ${featuredBadge}
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewTestimonial(${testimonial.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="approveTestimonial(${testimonial.id})" 
                                        ${testimonial.status === 'approved' ? 'disabled' : ''}>
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="toggleFeatured(${testimonial.id})"
                                        ${testimonial.status !== 'approved' ? 'disabled' : ''}>
                                    <i class="fas fa-star"></i>
                                </button>
                                <button class="btn btn-outline-secondary" onclick="hideTestimonial(${testimonial.id})"
                                        ${testimonial.status === 'hidden' ? 'disabled' : ''}>
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <p class="mb-2">${testimonial.testimonial}</p>
                        ${testimonial.rating ? `
                            <div class="text-warning">
                                ${generateStars(testimonial.rating)}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
    }

    html += `
            </div>
        </div>
    `;

    container.innerHTML = html;
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'approved': '<span class="badge bg-success">Approved</span>',
        'hidden': '<span class="badge bg-secondary">Hidden</span>'
    };
    return badges[status] || '';
}

function generateStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    return stars;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString();
}

function showError(message) {
    document.getElementById('testimonials-container').innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
}

// Testimonial management functions
function approveTestimonial(testimonialId) {
    if (confirm('Approve this testimonial?')) {
        updateTestimonialStatus(testimonialId, 'approved');
    }
}

function hideTestimonial(testimonialId) {
    if (confirm('Hide this testimonial?')) {
        updateTestimonialStatus(testimonialId, 'hidden');
    }
}

function toggleFeatured(testimonialId) {
    fetch('/actions/toggle_testimonial_featured.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            testimonial_id: testimonialId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTestimonials(); // Reload to show updated state
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update testimonial');
    });
}

function updateTestimonialStatus(testimonialId, status) {
    fetch('/actions/update_testimonial_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            testimonial_id: testimonialId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTestimonials(); // Reload to show updated state
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update testimonial');
    });
}

function viewTestimonial(testimonialId) {
    fetch(`/actions/get_testimonial_details.php?id=${testimonialId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('testimonialModalBody').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('testimonialModal')).show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load testimonial details');
        });
}
</script>

<?php include '../includes/footer_main.php'; ?> 