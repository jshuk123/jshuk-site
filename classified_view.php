<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config/config.php';

// Get classified ID
$classified_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$classified_id) {
    header('Location: /classifieds.php');
    exit;
}

try {
    // Fetch classified details with category and user info
    $stmt = $pdo->prepare("
        SELECT c.*, cc.name as category_name, cc.slug as category_slug, cc.icon as category_icon,
               u.username as user_name, u.email as user_email
        FROM classifieds c
        LEFT JOIN classifieds_categories cc ON c.category_id = cc.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = ? AND c.is_active = 1
    ");
    $stmt->execute([$classified_id]);
    $classified = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$classified) {
        header('Location: /classifieds.php');
        exit;
    }
    
    // Fetch any requests for this item (if user is the owner)
    $requests = [];
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $classified['user_id']) {
        $stmt = $pdo->prepare("
            SELECT fsr.*, u.username as requester_username
            FROM free_stuff_requests fsr
            LEFT JOIN users u ON fsr.requester_id = u.id
            WHERE fsr.classified_id = ?
            ORDER BY fsr.requested_at DESC
        ");
        $stmt->execute([$classified_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Classified View Error: " . $e->getMessage());
    header('Location: /classifieds.php');
    exit;
}

$pageTitle = htmlspecialchars($classified['title']) . " | JShuk Classifieds";
$page_css = "classified_view.css";
$metaDescription = htmlspecialchars(mb_strimwidth($classified['description'], 0, 160, '...'));
include 'includes/header_main.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/classifieds.php">Classifieds</a></li>
                    <?php if ($classified['category_name']): ?>
                        <li class="breadcrumb-item">
                            <a href="/classifieds.php?category=<?= $classified['category_slug'] ?>">
                                <?= htmlspecialchars($classified['category_name']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($classified['title']) ?></li>
                </ol>
            </nav>
            
            <!-- Item Image -->
            <div class="item-image-container mb-4">
                <?php if ($classified['image_path']): ?>
                    <img src="<?= htmlspecialchars($classified['image_path']) ?>" 
                         alt="<?= htmlspecialchars($classified['title']) ?>"
                         class="item-image"
                         onerror="this.src='/images/placeholder.png';">
                <?php else: ?>
                    <img src="/images/placeholder.png" alt="No image available" class="item-image">
                <?php endif; ?>
                
                <!-- Badges -->
                <div class="item-badges">
                    <?php if ($classified['price'] == 0): ?>
                        <span class="badge-free">♻️ Free</span>
                    <?php endif; ?>
                    <?php if ($classified['is_chessed']): ?>
                        <span class="badge-chessed">💝 Chessed</span>
                    <?php endif; ?>
                    <?php if ($classified['is_bundle']): ?>
                        <span class="badge-bundle">📦 Bundle</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Item Details -->
            <div class="item-details">
                <h1 class="item-title"><?= htmlspecialchars($classified['title']) ?></h1>
                
                <div class="item-price">
                    <?php if ($classified['price'] > 0): ?>
                        <span class="price-amount">£<?= number_format($classified['price'], 2) ?></span>
                    <?php else: ?>
                        <span class="price-free">♻️ Free</span>
                    <?php endif; ?>
                </div>
                
                <!-- Category and Location -->
                <div class="item-meta">
                    <?php if ($classified['category_name']): ?>
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span><?= htmlspecialchars($classified['category_name']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($classified['location']): ?>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($classified['location']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Posted <?= date('M j, Y', strtotime($classified['created_at'])) ?></span>
                    </div>
                </div>
                
                <!-- Free Stuff Specific Info -->
                <?php if ($classified['price'] == 0): ?>
                    <div class="free-stuff-info">
                        <h3>📦 Pickup Information</h3>
                        
                        <?php if ($classified['pickup_method']): ?>
                            <div class="info-item">
                                <strong>Pickup Method:</strong>
                                <?php
                                switch($classified['pickup_method']) {
                                    case 'porch_pickup': echo 'Porch Pickup'; break;
                                    case 'contact_arrange': echo 'Contact to Arrange'; break;
                                    case 'collection_code': echo 'Collection Code'; break;
                                    default: echo 'Contact Seller';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($classified['collection_deadline']): ?>
                            <div class="info-item">
                                <strong>Collection Deadline:</strong>
                                <?= date('M j, Y \a\t g:i A', strtotime($classified['collection_deadline'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($classified['status']): ?>
                            <div class="info-item">
                                <strong>Status:</strong>
                                <span class="status-badge status-<?= $classified['status'] ?>">
                                    <?= ucfirst($classified['status']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($classified['pickup_code']): ?>
                            <div class="info-item">
                                <strong>Pickup Code:</strong>
                                <code class="pickup-code"><?= htmlspecialchars($classified['pickup_code']) ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Description -->
                <div class="item-description">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($classified['description'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sidebar">
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($classified['price'] == 0): ?>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $classified['user_id']): ?>
                            <button class="btn btn-success btn-lg w-100 mb-3" onclick="requestItem()">
                                <i class="fas fa-gift"></i> Request This Item
                            </button>
                        <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $classified['user_id']): ?>
                            <button class="btn btn-warning btn-lg w-100 mb-3" onclick="markAsTaken()">
                                <i class="fas fa-check"></i> Mark as Taken
                            </button>
                        <?php else: ?>
                            <a href="/auth/login.php" class="btn btn-success btn-lg w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Login to Request
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-primary btn-lg w-100 mb-3" onclick="contactSeller()">
                            <i class="fas fa-envelope"></i> Contact Seller
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-secondary w-100 mb-3" onclick="shareItem()">
                        <i class="fas fa-share"></i> Share
                    </button>
                </div>
                
                <!-- Seller Info -->
                <div class="seller-info">
                    <h4>Seller Information</h4>
                    <?php if ($classified['is_anonymous']): ?>
                        <p><em>Listed anonymously</em></p>
                    <?php else: ?>
                        <p><strong><?= htmlspecialchars($classified['user_name'] ?? 'Unknown') ?></strong></p>
                    <?php endif; ?>
                    
                    <?php if ($classified['price'] == 0 && $classified['contact_method'] && $classified['contact_info']): ?>
                        <div class="contact-info">
                            <strong>Contact via:</strong>
                            <?php
                            switch($classified['contact_method']) {
                                case 'whatsapp': echo 'WhatsApp'; break;
                                case 'email': echo 'Email'; break;
                                case 'phone': echo 'Phone'; break;
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Similar Items -->
                <div class="similar-items">
                    <h4>Similar Items</h4>
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT c.*, cc.name as category_name
                            FROM classifieds c
                            LEFT JOIN classifieds_categories cc ON c.category_id = cc.id
                            WHERE c.category_id = ? AND c.id != ? AND c.is_active = 1
                            ORDER BY c.created_at DESC
                            LIMIT 3
                        ");
                        $stmt->execute([$classified['category_id'], $classified_id]);
                        $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($similar): ?>
                            <div class="similar-list">
                                <?php foreach ($similar as $item): ?>
                                    <a href="classified_view.php?id=<?= $item['id'] ?>" class="similar-item">
                                        <div class="similar-image">
                                            <?php if ($item['image_path']): ?>
                                                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
                                            <?php else: ?>
                                                <img src="/images/placeholder.png" alt="">
                                            <?php endif; ?>
                                        </div>
                                        <div class="similar-details">
                                            <h5><?= htmlspecialchars($item['title']) ?></h5>
                                            <p class="similar-price">
                                                <?= ($item['price'] > 0) ? '£' . number_format($item['price'], 2) : '♻️ Free' ?>
                                            </p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No similar items found.</p>
                        <?php endif;
                    } catch (PDOException $e) {
                        echo '<p class="text-muted">Unable to load similar items.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Requests Section (for item owner) -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $classified['user_id'] && $classified['price'] == 0 && !empty($requests)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="requests-section">
                    <h3>📋 Item Requests</h3>
                    <div class="requests-list">
                        <?php foreach ($requests as $request): ?>
                            <div class="request-item">
                                <div class="request-header">
                                    <h5><?= htmlspecialchars($request['requester_name']) ?></h5>
                                    <span class="request-date"><?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?></span>
                                </div>
                                <div class="request-details">
                                    <p><strong>Contact:</strong> <?= htmlspecialchars($request['requester_contact']) ?></p>
                                    <?php if ($request['message']): ?>
                                        <p><strong>Message:</strong> <?= htmlspecialchars($request['message']) ?></p>
                                    <?php endif; ?>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge status-<?= $request['status'] ?>">
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="request-actions">
                                    <?php if ($request['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-success" onclick="approveRequest(<?= $request['id'] ?>)">
                                            Approve
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?= $request['id'] ?>)">
                                            Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Request Item Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request This Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="requestForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="requester_name" class="form-label">Your Name *</label>
                        <input type="text" class="form-control" id="requester_name" name="requester_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="requester_contact" class="form-label">Contact Information *</label>
                        <input type="text" class="form-control" id="requester_contact" name="requester_contact" 
                               placeholder="WhatsApp number, email, or phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message (Optional)</label>
                        <textarea class="form-control" id="message" name="message" rows="3" 
                                  placeholder="Tell the seller why you'd like this item..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function requestItem() {
    const modal = new bootstrap.Modal(document.getElementById('requestModal'));
    modal.show();
}

function contactSeller() {
    // Implement contact seller functionality
    alert('Contact seller functionality will be implemented here.');
}

function shareItem() {
    if (navigator.share) {
        navigator.share({
            title: '<?= addslashes($classified['title']) ?>',
            text: 'Check out this item on JShuk!',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

function markAsTaken() {
    if (confirm('Mark this item as taken?')) {
        // Implement mark as taken functionality
        fetch('/actions/mark_item_taken.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                classified_id: <?= $classified_id ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function approveRequest(requestId) {
    if (confirm('Approve this request?')) {
        // Implement approve request functionality
        console.log('Approve request:', requestId);
    }
}

function rejectRequest(requestId) {
    if (confirm('Reject this request?')) {
        // Implement reject request functionality
        console.log('Reject request:', requestId);
    }
}

// Handle request form submission
document.getElementById('requestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('classified_id', <?= $classified_id ?>);
    
    fetch('/actions/request_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Request sent successfully!');
            bootstrap.Modal.getInstance(document.getElementById('requestModal')).hide();
        } else {
            alert('Error: ' + data.message);
        }
    });
});
</script>

<style>
.item-image-container {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.item-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

.item-badges {
    position: absolute;
    top: 1rem;
    left: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.badge-free, .badge-chessed, .badge-bundle {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.875rem;
}

.badge-free {
    background: #28a745;
    color: white;
}

.badge-chessed {
    background: #e91e63;
    color: white;
}

.badge-bundle {
    background: #ff9800;
    color: white;
}

.item-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a3353;
    margin-bottom: 1rem;
}

.item-price {
    margin-bottom: 1.5rem;
}

.price-amount {
    font-size: 2rem;
    font-weight: 700;
    color: #ffd700;
}

.price-free {
    font-size: 2rem;
    font-weight: 700;
    color: #28a745;
}

.item-meta {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
}

.meta-item i {
    color: #ffd700;
}

.free-stuff-info {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-item {
    margin-bottom: 1rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-available {
    background: #d4edda;
    color: #155724;
}

.status-pending_pickup {
    background: #fff3cd;
    color: #856404;
}

.status-claimed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-expired {
    background: #f8d7da;
    color: #721c24;
}

.pickup-code {
    background: #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    font-weight: 600;
}

.item-description {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sidebar {
    position: sticky;
    top: 2rem;
}

.action-buttons {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.seller-info, .similar-items {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.similar-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.2s ease;
}

.similar-item:hover {
    background: #f8f9fa;
    color: inherit;
}

.similar-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.similar-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.similar-details h5 {
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.similar-price {
    font-weight: 600;
    color: #ffd700;
    margin: 0;
}

.requests-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.request-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.request-date {
    font-size: 0.875rem;
    color: #6c757d;
}

.request-actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .item-meta {
        flex-direction: column;
        gap: 1rem;
    }
    
    .item-title {
        font-size: 1.5rem;
    }
    
    .price-amount, .price-free {
        font-size: 1.5rem;
    }
}
</style>

<?php include 'includes/footer_main.php'; ?> 