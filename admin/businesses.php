<?php
// Start output buffering and session management
ob_start();

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once '../config/config.php';

    // Check admin access
    function checkAdminAccess() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../auth/login.php');
            ob_end_clean();
            exit();
        }
        global $pdo;
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || $user['role'] !== 'admin') {
            header('Location: ../index.php');
            ob_end_clean();
            exit();
        }
    }
    checkAdminAccess();

    // Handle business actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $businessId = $_POST['business_id'] ?? null;
            switch ($_POST['action']) {
                case 'change_status':
                    $newStatus = $_POST['status'] ?? 'pending';
                    // Try both possible column names for status
                    try {
                        $stmt = $pdo->prepare("UPDATE businesses SET status = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $businessId]);
                    } catch (PDOException $e) {
                        $stmt = $pdo->prepare("UPDATE businesses SET biz_status = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $businessId]);
                    }
                    break;
                case 'toggle_featured':
                    $stmt = $pdo->prepare("UPDATE businesses SET is_featured = NOT COALESCE(is_featured, 0) WHERE id = ?");
                    $stmt->execute([$businessId]);
                    break;
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM business_images WHERE business_id = ?");
                    $stmt->execute([$businessId]);
                    $stmt = $pdo->prepare("DELETE FROM businesses WHERE id = ?");
                    $stmt->execute([$businessId]);
                    break;
            }
        }
    }

    // Pagination and filtering
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    $whereClause = '';
    $params = [];
    
    // Try to determine which status column exists
    $statusColumn = 'status';
    try {
        $pdo->query("SELECT status FROM businesses LIMIT 1");
    } catch (PDOException $e) {
        $statusColumn = 'biz_status';
    }
    
    if ($statusFilter !== '') {
        $whereClause = "WHERE b.$statusColumn = ?";
        $params[] = $statusFilter;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM businesses b $whereClause");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $totalPages = ceil($total / $limit);

    $query = "
        SELECT b.*, u.username, c.name as category_name,
               COALESCE(b.$statusColumn, 'active') as business_status,
               COALESCE(b.is_featured, 0) as is_featured,
               (SELECT COUNT(*) FROM reviews r WHERE r.business_id = b.id) as review_count,
               (SELECT AVG(rating) FROM reviews r WHERE r.business_id = b.id) as avg_rating
        FROM businesses b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN business_categories c ON b.category_id = c.id
        $whereClause
        ORDER BY b.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll();

    // Fetch main image for each business
    $img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
    foreach ($businesses as &$business) {
        $img_stmt->execute([$business['id']]);
        $main_image_path = $img_stmt->fetchColumn();
        $business['main_image'] = $main_image_path ? $main_image_path : 'images/default-business.jpg';
    }
    unset($business);

} catch (Exception $e) {
    error_log("Admin businesses page error: " . $e->getMessage());
    ob_end_clean();
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Error</h1>";
        echo "<p>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
    } else {
        header("Location: ../500.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Management - JSHUK Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #212529; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #343a40; color: #ffc107; }
        .business-image { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .badge-featured { background: #ffc107; color: #23272b; }
        .table thead th { vertical-align: middle; }
        .action-btns .btn { margin-right: 0.25rem; }
        @media (max-width: 991px) { .sidebar { min-height: auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-lg-2 col-md-3 d-md-block sidebar py-4 px-3">
            <div class="d-flex flex-column align-items-start">
                <a href="index.php" class="mb-4 text-white text-decoration-none fs-4 fw-bold"><i class="fa fa-crown me-2"></i>Admin Panel</a>
                <ul class="nav nav-pills flex-column w-100 mb-auto">
                    <li class="nav-item mb-1"><a href="index.php" class="nav-link"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                    <li class="nav-item mb-1"><a href="users.php" class="nav-link"><i class="fas fa-users me-2"></i>Users</a></li>
                    <li class="nav-item mb-1"><a href="businesses.php" class="nav-link active"><i class="fas fa-store me-2"></i>Businesses</a></li>
                    <li class="nav-item mb-1"><a href="categories.php" class="nav-link"><i class="fas fa-tags me-2"></i>Categories</a></li>
                    <li class="nav-item mb-1"><a href="reviews.php" class="nav-link"><i class="fas fa-star me-2"></i>Reviews</a></li>
                </ul>
                <hr class="text-white w-100">
                <div class="dropdown w-100">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Main content -->
        <main class="col-lg-10 col-md-9 ms-sm-auto px-4 py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">Business Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="?status=" class="btn btn-sm btn-outline-secondary <?php echo $statusFilter === '' ? 'active' : ''; ?>">All</a>
                        <a href="?status=active" class="btn btn-sm btn-outline-success <?php echo $statusFilter === 'active' ? 'active' : ''; ?>">Active</a>
                        <a href="?status=pending" class="btn btn-sm btn-outline-warning <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                        <a href="?status=inactive" class="btn btn-sm btn-outline-danger <?php echo $statusFilter === 'inactive' ? 'active' : ''; ?>">Inactive</a>
                    </div>
                </div>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Businesses</h6>
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="searchBusiness" placeholder="Search businesses...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Business Name</th>
                                    <th>Owner</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Featured</th>
                                    <th>Reviews</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($businesses as $business): ?>
                                <tr>
                                    <td>
                                        <?php if ($business['main_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($business['main_image']); ?>" class="business-image" alt="Business Image">
                                        <?php else: ?>
                                            <i class="fas fa-store fa-2x text-gray-300"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($business['business_name']); ?></td>
                                    <td><?php echo htmlspecialchars($business['username']); ?></td>
                                    <td><?php echo htmlspecialchars($business['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="business_id" value="<?php echo $business['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="active" <?php echo $business['business_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="pending" <?php echo $business['business_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="inactive" <?php echo $business['business_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($business['is_featured']): ?>
                                            <span class="badge badge-featured">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_featured">
                                            <input type="hidden" name="business_id" value="<?php echo $business['id']; ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm ms-1" title="Toggle Featured">
                                                <i class="fa fa-star"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $business['review_count']; ?> reviews</span>
                                        <?php if ($business['avg_rating']): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo number_format($business['avg_rating'], 1); ?> <i class="fas fa-star"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($business['created_at'])); ?></td>
                                    <td class="action-btns">
                                        <a href="view_business.php?id=<?php echo $business['id']; ?>" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="edit_business.php?id=<?php echo $business['id']; ?>" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this business?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="business_id" value="<?php echo $business['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($statusFilter); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Simple search functionality
    document.getElementById('searchBusiness').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });
</script>
</body>
</html>
<?php
// End output buffering and flush
ob_end_flush();
?>