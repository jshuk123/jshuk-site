<?php
/**
 * Lost & Found Page
 * Community corner page for lost and found items
 */

require_once 'config/config.php';
require_once 'includes/community_corner_functions.php';

$pageTitle = "Lost & Found | JShuk Community";
$page_css = "pages/lost_and_found.css";
$metaDescription = "Find lost items or report found items in the Jewish community. Connect with neighbors to reunite people with their belongings.";
$metaKeywords = "lost and found, jewish community, lost items, found items, community help";

include 'includes/header_main.php';

// Get lost and found items
$lostFoundItems = getCommunityCornerItemsByType('lost_found', 20);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header text-center mb-5">
                <h1 class="display-4">Lost & Found</h1>
                <p class="lead">Help reunite people with their lost items</p>
                <div class="header-emoji">🎒</div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card action-card">
                        <div class="card-body text-center">
                            <div class="action-icon">🔍</div>
                            <h5>Lost Something?</h5>
                            <p>Search through reported found items or post about your lost item.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lostItemModal">
                                Report Lost Item
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card action-card">
                        <div class="card-body text-center">
                            <div class="action-icon">📝</div>
                            <h5>Found Something?</h5>
                            <p>Help someone find their lost item by reporting what you found.</p>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#foundItemModal">
                                Report Found Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Items -->
            <div class="section-title">
                <h2>Recent Lost & Found Items</h2>
                <p>Latest community reports</p>
            </div>
            
            <?php if (!empty($lostFoundItems)): ?>
                <div class="row">
                    <?php foreach ($lostFoundItems as $item): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="item-card">
                                <div class="item-emoji"><?= htmlspecialchars($item['emoji']) ?></div>
                                <div class="item-content">
                                    <h5><?= htmlspecialchars($item['title']) ?></h5>
                                    <p><?= htmlspecialchars($item['body_text']) ?></p>
                                    <div class="item-meta">
                                        <small class="text-muted">
                                            Posted: <?= date('M j, Y', strtotime($item['date_added'])) ?>
                                        </small>
                                        <?php if ($item['views_count'] > 0): ?>
                                            <small class="text-muted ms-2">
                                                <?= $item['views_count'] ?> views
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['link_url']): ?>
                                        <a href="<?= htmlspecialchars($item['link_url']) ?>" class="btn btn-outline-primary btn-sm mt-2">
                                            <?= htmlspecialchars($item['link_text']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="empty-state">
                        <div class="empty-icon">🎒</div>
                        <h3>No Lost & Found Items Yet</h3>
                        <p>Be the first to report a lost or found item in your community.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lostItemModal">
                            Report an Item
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Community Guidelines -->
            <div class="guidelines-section mt-5">
                <div class="card">
                    <div class="card-header">
                        <h4>Community Guidelines</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>When Reporting Lost Items:</h5>
                                <ul>
                                    <li>Include specific details (color, brand, location)</li>
                                    <li>Provide contact information</li>
                                    <li>Be patient and check back regularly</li>
                                    <li>Update when item is found</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>When Reporting Found Items:</h5>
                                <ul>
                                    <li>Describe the item accurately</li>
                                    <li>Mention where and when you found it</li>
                                    <li>Keep the item safe until claimed</li>
                                    <li>Verify ownership before returning</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lost Item Modal -->
<div class="modal fade" id="lostItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Lost Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="lostItemForm">
                    <div class="mb-3">
                        <label for="itemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="itemName" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="itemDescription" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lostLocation" class="form-label">Lost Location</label>
                                <input type="text" class="form-control" id="lostLocation" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lostDate" class="form-label">Date Lost</label>
                                <input type="date" class="form-control" id="lostDate" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="contactInfo" class="form-label">Contact Information</label>
                        <input type="text" class="form-control" id="contactInfo" placeholder="Phone or email" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitLostItem()">Submit Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Found Item Modal -->
<div class="modal fade" id="foundItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Found Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="foundItemForm">
                    <div class="mb-3">
                        <label for="foundItemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="foundItemName" required>
                    </div>
                    <div class="mb-3">
                        <label for="foundItemDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="foundItemDescription" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="foundLocation" class="form-label">Found Location</label>
                                <input type="text" class="form-control" id="foundLocation" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="foundDate" class="form-label">Date Found</label>
                                <input type="date" class="form-control" id="foundDate" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="foundContactInfo" class="form-label">Your Contact Information</label>
                        <input type="text" class="form-control" id="foundContactInfo" placeholder="Phone or email" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitFoundItem()">Submit Report</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitLostItem() {
    const formData = {
        itemName: document.getElementById('itemName').value,
        description: document.getElementById('itemDescription').value,
        location: document.getElementById('lostLocation').value,
        date: document.getElementById('lostDate').value,
        contact: document.getElementById('contactInfo').value
    };
    
    // Here you would typically send to your backend
    alert('Thank you for your report! We\'ll help you find your item.');
    bootstrap.Modal.getInstance(document.getElementById('lostItemModal')).hide();
}

function submitFoundItem() {
    const formData = {
        itemName: document.getElementById('foundItemName').value,
        description: document.getElementById('foundItemDescription').value,
        location: document.getElementById('foundLocation').value,
        date: document.getElementById('foundDate').value,
        contact: document.getElementById('foundContactInfo').value
    };
    
    // Here you would typically send to your backend
    alert('Thank you for your report! We\'ll help reunite the item with its owner.');
    bootstrap.Modal.getInstance(document.getElementById('foundItemModal')).hide();
}
</script>

<?php include 'includes/footer_main.php'; ?> 