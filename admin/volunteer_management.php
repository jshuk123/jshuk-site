<?php
/**
 * Volunteer Management - Admin Panel
 * Manage volunteer opportunities, approve submissions, and view statistics
 */

require_once '../config/config.php';
require_once '../includes/volunteer_functions.php';

// Check admin access
if (!isAdmin()) {
    redirect('/admin/admin_login.php');
}

$user_id = getCurrentUserId();
$success_message = '';
$error_message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $opportunity_id = intval($_POST['opportunity_id'] ?? 0);
        
        switch ($action) {
            case 'approve':
                if (approveVolunteerOpportunity($opportunity_id, $user_id)) {
                    $success_message = 'Opportunity approved successfully.';
                } else {
                    $error_message = 'Error approving opportunity.';
                }
                break;
                
            case 'reject':
                if (rejectVolunteerOpportunity($opportunity_id, $user_id)) {
                    $success_message = 'Opportunity rejected successfully.';
                } else {
                    $error_message = 'Error rejecting opportunity.';
                }
                break;
                
            case 'mark_filled':
                if (markOpportunityFilled($opportunity_id)) {
                    $success_message = 'Opportunity marked as filled.';
                } else {
                    $error_message = 'Error updating opportunity status.';
                }
                break;
                
            case 'mark_expired':
                if (markOpportunityExpired($opportunity_id)) {
                    $success_message = 'Opportunity marked as expired.';
                } else {
                    $error_message = 'Error updating opportunity status.';
                }
                break;
                
            case 'delete':
                if (deleteVolunteerOpportunity($opportunity_id)) {
                    $success_message = 'Opportunity deleted successfully.';
                } else {
                    $error_message = 'Error deleting opportunity.';
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$location_filter = $_GET['location'] ?? '';
$search_filter = $_GET['search'] ?? '';

// Get opportunities for admin
$opportunities = getAdminVolunteerOpportunities($status_filter, $location_filter, $search_filter);

// Get statistics
$stats = getVolunteerStatistics();

// Get locations for filter
$locations = getVolunteerLocations();

// Page title
$page_title = "Volunteer Management - JShuk Admin";

// Include admin header
include 'admin_header.php';
?>

<!-- Page Header -->
<div class="page-header bg-primary text-white py-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fa fa-hands-helping"></i> Volunteer Management
                </h1>
                <p class="lead mb-0">Manage volunteer opportunities and community engagement</p>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="/volunteer.php" class="btn btn-outline-light" target="_blank">
                    <i class="fa fa-external-link-alt"></i> View Public Hub
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<section class="stats-section py-4 bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fa fa-list-alt fa-2x mb-2"></i>
                        <h3 class="mb-1"><?php echo $stats['total_opportunities']; ?></h3>
                        <small>Total Opportunities</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fa fa-clock fa-2x mb-2"></i>
                        <h3 class="mb-1"><?php echo $stats['pending_approval']; ?></h3>
                        <small>Pending Approval</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fa fa-check-circle fa-2x mb-2"></i>
                        <h3 class="mb-1"><?php echo $stats['active_opportunities']; ?></h3>
                        <small>Active</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fa fa-users fa-2x mb-2"></i>
                        <h3 class="mb-1"><?php echo $stats['total_interests']; ?></h3>
                        <small>Total Interests</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filters -->
<section class="filters-section py-3 bg-white border-bottom">
    <div class="container-fluid">
        <form method="GET" action="" class="row align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="filled" <?php echo $status_filter === 'filled' ? 'selected' : ''; ?>>Filled</option>
                    <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="location" class="form-label">Location</label>
                <select class="form-control" id="location" name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo h($location['location']); ?>" 
                                <?php echo $location_filter === $location['location'] ? 'selected' : ''; ?>>
                            <?php echo h($location['location']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo h($search_filter); ?>" 
                       placeholder="Search by title, description, or tags">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Opportunities Table -->
<section class="opportunities-section py-4">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fa fa-list"></i> Volunteer Opportunities
                </h4>
                <span class="badge badge-primary"><?php echo count($opportunities); ?> opportunities</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($opportunities)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No opportunities found</h5>
                        <p class="text-muted">Try adjusting your filters or check back later.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Posted By</th>
                                    <th>Status</th>
                                    <th>Views</th>
                                    <th>Interests</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($opportunities as $opportunity): ?>
                                    <tr>
                                        <td>
                                            <div class="opportunity-title">
                                                <strong><?php echo h($opportunity['title']); ?></strong>
                                                <?php if ($opportunity['urgent']): ?>
                                                    <span class="badge badge-danger ml-2">Urgent</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?php echo h(truncateText($opportunity['summary'], 80)); ?></small>
                                        </td>
                                        <td>
                                            <i class="fa fa-map-marker-alt text-primary"></i>
                                            <?php echo h($opportunity['location']); ?>
                                        </td>
                                        <td>
                                            <?php echo h($opportunity['posted_by_name'] ?? 'Anonymous'); ?>
                                            <br>
                                            <small class="text-muted"><?php echo h($opportunity['posted_by_email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <?php echo getVolunteerStatusBadge($opportunity['status'], $opportunity['urgent']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-light">
                                                <i class="fa fa-eye"></i> <?php echo $opportunity['views_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <i class="fa fa-users"></i> <?php echo $opportunity['interests_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo formatRelativeDate($opportunity['created_at']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-toggle="modal" data-target="#viewModal<?php echo $opportunity['id']; ?>">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($opportunity['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="approveOpportunity(<?php echo $opportunity['id']; ?>)">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="rejectOpportunity(<?php echo $opportunity['id']; ?>)">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($opportunity['status'] === 'active'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="markFilled(<?php echo $opportunity['id']; ?>)">
                                                        <i class="fa fa-check-double"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        onclick="editOpportunity(<?php echo $opportunity['id']; ?>)">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteOpportunity(<?php echo $opportunity['id']; ?>)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
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
</section>

<!-- View Modals -->
<?php foreach ($opportunities as $opportunity): ?>
    <div class="modal fade" id="viewModal<?php echo $opportunity['id']; ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-eye"></i> View Opportunity
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h4><?php echo h($opportunity['title']); ?></h4>
                    <p class="text-muted"><?php echo h($opportunity['summary']); ?></p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Location:</strong> <?php echo h($opportunity['location']); ?><br>
                            <strong>Frequency:</strong> <?php echo formatVolunteerFrequency($opportunity['frequency']); ?><br>
                            <strong>Posted By:</strong> <?php echo h($opportunity['posted_by_name'] ?? 'Anonymous'); ?><br>
                            <strong>Contact:</strong> <?php echo h($opportunity['contact_method']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> <?php echo ucfirst($opportunity['status']); ?><br>
                            <strong>Views:</strong> <?php echo $opportunity['views_count']; ?><br>
                            <strong>Interests:</strong> <?php echo $opportunity['interests_count']; ?><br>
                            <strong>Posted:</strong> <?php echo formatDate($opportunity['created_at']); ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Description:</h6>
                    <p><?php echo nl2br(h($opportunity['description'])); ?></p>
                    
                    <?php 
                    $tags = json_decode($opportunity['tags'], true) ?? [];
                    if (!empty($tags)): ?>
                        <h6>Tags:</h6>
                        <div class="mb-3">
                            <?php foreach ($tags as $tag): ?>
                                <span class="badge badge-light mr-1">#<?php echo h($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <a href="/volunteer_detail.php?slug=<?php echo h($opportunity['slug']); ?>" 
                       class="btn btn-primary" target="_blank">
                        <i class="fa fa-external-link-alt"></i> View Public Page
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Action Forms -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="opportunity_id" id="opportunityId">
</form>

<!-- Volunteer Management CSS -->
<link rel="stylesheet" href="/css/admin/volunteer_management.css">

<!-- JavaScript -->
<script>
function approveOpportunity(id) {
    if (confirm('Are you sure you want to approve this opportunity?')) {
        document.getElementById('actionType').value = 'approve';
        document.getElementById('opportunityId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function rejectOpportunity(id) {
    if (confirm('Are you sure you want to reject this opportunity?')) {
        document.getElementById('actionType').value = 'reject';
        document.getElementById('opportunityId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function markFilled(id) {
    if (confirm('Mark this opportunity as filled?')) {
        document.getElementById('actionType').value = 'mark_filled';
        document.getElementById('opportunityId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function markExpired(id) {
    if (confirm('Mark this opportunity as expired?')) {
        document.getElementById('actionType').value = 'mark_expired';
        document.getElementById('opportunityId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function editOpportunity(id) {
    // Redirect to edit page (to be created)
    window.location.href = '/admin/edit_volunteer.php?id=' + id;
}

function deleteOpportunity(id) {
    if (confirm('Are you sure you want to delete this opportunity? This action cannot be undone.')) {
        document.getElementById('actionType').value = 'delete';
        document.getElementById('opportunityId').value = id;
        document.getElementById('actionForm').submit();
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php include 'admin_footer.php'; ?> 