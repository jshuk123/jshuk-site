<?php
// Start output buffering
ob_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Load configuration
    require_once '../config/config.php';
    
    // Check admin access
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        ob_end_clean();
        exit();
    }
    
    // Verify admin role
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ../index.php');
        ob_end_clean();
        exit();
    }
    
    // Get admin info
    $adminName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';
    $adminEmail = $_SESSION['email'] ?? '';
    $adminRole = $_SESSION['role'] ?? 'admin';
    $lastLogin = $_SESSION['last_login'] ?? '';
    
    // Get key statistics with error handling
    $stats = [
        'total_businesses' => 0,
        'pending_businesses' => 0,
        'total_users' => 0,
        'total_reviews' => 0,
        'pending_reviews' => 0
    ];
    
    try {
        $stats['total_businesses'] = $pdo->query("SELECT COUNT(*) FROM businesses")->fetchColumn();
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
        
        // Try to get pending counts, but fall back if status column doesn't exist
        try {
            $stats['pending_businesses'] = $pdo->query("SELECT COUNT(*) FROM businesses WHERE status = 'pending'")->fetchColumn();
        } catch (PDOException $e) {
            $stats['pending_businesses'] = 0;
        }
        
        try {
            $stats['pending_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
        } catch (PDOException $e) {
            $stats['pending_reviews'] = 0;
        }
    } catch (PDOException $e) {
        error_log("Error getting statistics: " . $e->getMessage());
    }
    
    // Get recent activity with error handling
    $recentBusinesses = [];
    $recentUsers = [];
    $recentReviews = [];
    
    try {
        $recentBusinesses = $pdo->query("
            SELECT b.business_name, b.created_at, u.username 
            FROM businesses b 
            LEFT JOIN users u ON b.user_id = u.id 
            ORDER BY b.created_at DESC LIMIT 5
        ")->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting recent businesses: " . $e->getMessage());
    }
    
    try {
        $recentUsers = $pdo->query("
            SELECT username, email, created_at, role 
            FROM users 
            ORDER BY created_at DESC LIMIT 5
        ")->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting recent users: " . $e->getMessage());
    }
    
    try {
        $recentReviews = $pdo->query("
            SELECT r.comment, r.rating, r.created_at, u.username, b.business_name 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            LEFT JOIN businesses b ON r.business_id = b.id 
            ORDER BY r.created_at DESC LIMIT 5
        ")->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting recent reviews: " . $e->getMessage());
    }
    
    // Example notifications
    $notifications = [
        ['type' => 'info', 'icon' => 'fa-info-circle', 'msg' => 'System maintenance scheduled for Sunday 2am.'],
        ['type' => 'warning', 'icon' => 'fa-exclamation-triangle', 'msg' => $stats['pending_businesses'] . ' businesses pending approval.'],
        ['type' => 'success', 'icon' => 'fa-user-check', 'msg' => 'New user registered.']
    ];
    
    // Chart data with error handling
    $chartLabels = [];
    $businessGrowth = [];
    $userGrowth = [];
    
    try {
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $chartLabels[] = date('M Y', strtotime($month));
            $businessGrowth[] = (int)$pdo->query("SELECT COUNT(*) FROM businesses WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetchColumn();
            $userGrowth[] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Error getting chart data: " . $e->getMessage());
        // Fallback data
        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $businessGrowth = [0, 0, 0, 0, 0, 0];
        $userGrowth = [0, 0, 0, 0, 0, 0];
    }

} catch (Exception $e) {
    error_log("Admin panel error: " . $e->getMessage());
    ob_end_clean();
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Error</h1>";
        echo "<p>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Check the error logs for more details.</p>";
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
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; transition: background 0.3s, color 0.3s; }
        .sidebar { min-height: 100vh; background: #212529; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #343a40; color: #ffc107; }
        .stat-card { border-radius: 1rem; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.02); }
        .notification { border-radius: 0.5rem; margin-bottom: 0.5rem; }
        .recent-table th, .recent-table td { vertical-align: middle; }
        .dark-mode { background: #181a1b !important; color: #e0e0e0 !important; }
        .dark-mode .sidebar { background: #181a1b !important; }
        .dark-mode .card, .dark-mode .table, .dark-mode .modal-content { background: #23272b !important; color: #e0e0e0; }
        .dark-mode .card-header, .dark-mode .table th { background: #23272b !important; color: #ffc107; }
        .dark-mode .nav-link { color: #e0e0e0 !important; }
        .dark-mode .nav-link.active, .dark-mode .nav-link:hover { background: #23272b !important; color: #ffc107 !important; }
        .dark-mode .btn, .dark-mode .form-control { background: #23272b; color: #e0e0e0; border-color: #444; }
        .dark-mode .btn-primary { background: #ffc107; color: #23272b; border: none; }
        .dark-mode .btn-danger { background: #dc3545; color: #fff; border: none; }
        .dark-mode .alert { background: #23272b; color: #ffc107; border-color: #444; }
        @media (max-width: 991px) {
            .sidebar { min-height: auto; }
        }
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
                        <li class="nav-item mb-1"><a href="index.php" class="nav-link active"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                        <li class="nav-item mb-1"><a href="businesses.php" class="nav-link"><i class="fas fa-store me-2"></i>Businesses</a></li>
                        <li class="nav-item mb-1"><a href="users.php" class="nav-link"><i class="fas fa-users me-2"></i>Users</a></li>
                        <li class="nav-item mb-1"><a href="categories.php" class="nav-link"><i class="fas fa-tags me-2"></i>Categories</a></li>
                        <li class="nav-item mb-1"><a href="recruitment.php" class="nav-link"><i class="fas fa-briefcase me-2"></i>Jobs</a></li>
                        <li class="nav-item mb-1"><a href="classifieds.php" class="nav-link"><i class="fas fa-list-alt me-2"></i>Classifieds</a></li>
                        <li class="nav-item mb-1"><a href="reviews.php" class="nav-link"><i class="fas fa-star me-2"></i>Reviews</a></li>
                        <li class="nav-item mb-1"><a href="ads.php" class="nav-link"><i class="fas fa-ad me-2"></i>Ads</a></li>
                        <li class="nav-item mb-1"><a href="enhanced_carousel_manager.php" class="nav-link"><i class="fas fa-images me-2"></i>Carousel</a></li>
                    </ul>
                    <hr class="text-white w-100">
                    <div class="w-100 mb-2">
                        <form class="d-flex" method="get" action="users.php">
                            <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Search users..." aria-label="Search">
                            <button class="btn btn-warning btn-sm" type="submit"><i class="fa fa-search"></i></button>
                        </form>
                    </div>
                    <a href="#" class="btn btn-secondary w-100 mb-2" id="toggleDarkMode"><i class="fa fa-moon me-2"></i>Toggle Dark Mode</a>
                    <a href="../logout.php" class="btn btn-danger w-100"><i class="fa fa-sign-out-alt me-2"></i>Log out</a>
                </div>
            </nav>
            <!-- Main content -->
            <main class="col-lg-10 col-md-9 ms-sm-auto px-4 py-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-1"><?php echo htmlspecialchars($adminName); ?></h1>
                        <div class="text-muted">Role: <span class="badge bg-primary text-uppercase">Admin</span> <?php echo $adminEmail ? '| ' . htmlspecialchars($adminEmail) : ''; ?></div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-secondary">Session: <?php echo htmlspecialchars(session_id()); ?></span>
                        <?php if ($lastLogin): ?>
                            <span class="badge bg-info">Last login: <?php echo htmlspecialchars($lastLogin); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Notifications -->
                <div class="mb-4">
                    <?php foreach ($notifications as $note): ?>
                        <div class="alert alert-<?php echo $note['type']; ?> notification"><i class="fa <?php echo $note['icon']; ?> me-2"></i><?php echo htmlspecialchars($note['msg']); ?></div>
                    <?php endforeach; ?>
                </div>
                <!-- Stat Cards -->
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <div class="card stat-card border-primary shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3"><i class="fa fa-store fa-2x text-primary"></i></div>
                                <div>
                                    <div class="fw-bold fs-5"><?php echo $stats['total_businesses']; ?></div>
                                    <div class="text-muted">Total Businesses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card border-warning shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3"><i class="fa fa-clock fa-2x text-warning"></i></div>
                                <div>
                                    <div class="fw-bold fs-5"><?php echo $stats['pending_businesses']; ?></div>
                                    <div class="text-muted">Pending Businesses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card border-success shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3"><i class="fa fa-users fa-2x text-success"></i></div>
                                <div>
                                    <div class="fw-bold fs-5"><?php echo $stats['total_users']; ?></div>
                                    <div class="text-muted">Total Users</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card border-info shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3"><i class="fa fa-star fa-2x text-info"></i></div>
                                <div>
                                    <div class="fw-bold fs-5"><?php echo $stats['total_reviews']; ?></div>
                                    <div class="text-muted">Total Reviews</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Chart.js Widget -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-dark text-white"><i class="fa fa-chart-line me-2"></i>Business & User Growth (Last 6 Months)</div>
                            <div class="card-body">
                                <canvas id="growthChart" height="120"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Custom Widget: Admin Notes -->
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-secondary text-white"><i class="fa fa-sticky-note me-2"></i>Admin Notes</div>
                            <div class="card-body">
                                <textarea class="form-control mb-2" id="adminNotes" rows="6" placeholder="Write your notes here..."></textarea>
                                <button class="btn btn-primary w-100" onclick="saveNotes()"><i class="fa fa-save me-2"></i>Save Notes</button>
                                <div id="notesSavedMsg" class="text-success mt-2" style="display:none;">Notes saved (locally).</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Recent Activity -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-primary text-white"><i class="fa fa-store me-2"></i>Recent Businesses</div>
                            <div class="card-body p-0">
                                <table class="table table-sm recent-table mb-0">
                                    <thead><tr><th>Name</th><th>Owner</th><th>Status</th><th>Created</th></tr></thead>
                                    <tbody>
                                    <?php if (!empty($recentBusinesses)): ?>
                                        <?php foreach ($recentBusinesses as $b): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($b['business_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($b['username'] ?? 'N/A'); ?></td>
                                                <td><span class="badge bg-success">Active</span></td>
                                                <td><?php echo $b['created_at'] ? date('M j, Y', strtotime($b['created_at'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center">No businesses found</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-success text-white"><i class="fa fa-users me-2"></i>Recent Users</div>
                            <div class="card-body p-0">
                                <table class="table table-sm recent-table mb-0">
                                    <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                                    <tbody>
                                    <?php if (!empty($recentUsers)): ?>
                                        <?php foreach ($recentUsers as $u): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($u['username'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($u['email'] ?? 'N/A'); ?></td>
                                                <td><span class="badge bg-<?php echo ($u['role'] ?? 'user') === 'admin' ? 'primary' : 'secondary'; ?> text-uppercase"><?php echo htmlspecialchars($u['role'] ?? 'user'); ?></span></td>
                                                <td><?php echo $u['created_at'] ? date('M j, Y', strtotime($u['created_at'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center">No users found</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-info text-white"><i class="fa fa-star me-2"></i>Recent Reviews</div>
                            <div class="card-body p-0">
                                <table class="table table-sm recent-table mb-0">
                                    <thead><tr><th>User</th><th>Business</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
                                    <tbody>
                                    <?php if (!empty($recentReviews)): ?>
                                        <?php foreach ($recentReviews as $r): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($r['username'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($r['business_name'] ?? 'N/A'); ?></td>
                                                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($r['rating'] ?? 'N/A'); ?></span></td>
                                                <td><?php echo htmlspecialchars(mb_strimwidth($r['comment'] ?? '', 0, 40, '...')); ?></td>
                                                <td><?php echo $r['created_at'] ? date('M j, Y', strtotime($r['created_at'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">No reviews found</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    // Dark mode toggle
    const toggleDarkMode = document.getElementById('toggleDarkMode');
    if (toggleDarkMode) {
        toggleDarkMode.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('adminDarkMode', document.body.classList.contains('dark-mode'));
        });
    }
    
    // Restore dark mode preference
    if (localStorage.getItem('adminDarkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
    
    // Chart.js for business/user growth
    const ctx = document.getElementById('growthChart');
    if (ctx) {
        const growthChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [
                    {
                        label: 'Businesses',
                        data: <?php echo json_encode($businessGrowth); ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Users',
                        data: <?php echo json_encode($userGrowth); ?>,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
    
    // Admin Notes widget (localStorage)
    function saveNotes() {
        const notes = document.getElementById('adminNotes').value;
        localStorage.setItem('adminNotes', notes);
        document.getElementById('notesSavedMsg').style.display = 'block';
        setTimeout(() => { document.getElementById('notesSavedMsg').style.display = 'none'; }, 2000);
    }
    
    const adminNotes = document.getElementById('adminNotes');
    if (adminNotes) {
        adminNotes.value = localStorage.getItem('adminNotes') || '';
    }
    </script>
</body>
</html>
<?php
// End output buffering and flush
ob_end_flush();
?> 