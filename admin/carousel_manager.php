<?php
/**
 * JShuk Carousel Manager - Admin Panel Integration
 * Manages homepage carousel ads
 */

// Include configuration and database connection
require_once '../config/config.php';

// Check if user is admin (don't start session if already active)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

// Create table if needed (run once)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS carousel_ads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            subtitle VARCHAR(255),
            image_path VARCHAR(255) NOT NULL,
            cta_text VARCHAR(50),
            cta_url VARCHAR(255),
            active BOOLEAN DEFAULT TRUE,
            is_auto_generated BOOLEAN DEFAULT FALSE,
            business_id INT,
            position INT DEFAULT 1,
            expires_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active_position (active, position),
            INDEX idx_business_id (business_id),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    error_log("Error creating carousel_ads table: " . $e->getMessage());
}

// Handle form submission for adding new ad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $cta_text = trim($_POST['cta_text'] ?? '');
        $cta_url = trim($_POST['cta_url'] ?? '');
        $position = (int) ($_POST['position'] ?? 1);
        $active = isset($_POST['active']) ? 1 : 0;
        $business_id = !empty($_POST['business_id']) ? (int) $_POST['business_id'] : null;
        
        // Validate required fields
        if (empty($title) || empty($_FILES['image']['tmp_name'])) {
            $error = "Title and image are required.";
        } else {
            // Upload image
            $image_path = '';
            if (!empty($_FILES['image']['tmp_name'])) {
                $upload_dir = '../uploads/carousel/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $error = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
                } else {
                    $filename = 'carousel_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        $image_path = 'uploads/carousel/' . $filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }
            
            if (empty($error)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO carousel_ads (title, subtitle, cta_text, cta_url, image_path, position, active, business_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $subtitle, $cta_text, $cta_url, $image_path, $position, $active, $business_id]);
                    $success = "Carousel ad added successfully!";
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete' && isset($_POST['ad_id'])) {
        $ad_id = (int) $_POST['ad_id'];
        try {
            // Get image path to delete file
            $stmt = $pdo->prepare("SELECT image_path FROM carousel_ads WHERE id = ?");
            $stmt->execute([$ad_id]);
            $ad = $stmt->fetch();
            
            if ($ad && file_exists('../' . $ad['image_path'])) {
                unlink('../' . $ad['image_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM carousel_ads WHERE id = ?");
            $stmt->execute([$ad_id]);
            $success = "Ad deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting ad: " . $e->getMessage();
        }
    } elseif ($action === 'toggle' && isset($_POST['ad_id'])) {
        $ad_id = (int) $_POST['ad_id'];
        try {
            $stmt = $pdo->prepare("UPDATE carousel_ads SET active = NOT active WHERE id = ?");
            $stmt->execute([$ad_id]);
            $success = "Ad status updated!";
        } catch (PDOException $e) {
            $error = "Error updating ad: " . $e->getMessage();
        }
    }
}

// Fetch active ads for preview
try {
    $stmt = $pdo->query("
        SELECT * FROM carousel_ads 
        WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY position ASC, created_at DESC
    ");
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching ads: " . $e->getMessage();
    $ads = [];
}

// Fetch all ads for admin management
try {
    $stmt = $pdo->query("
        SELECT ca.*, b.business_name 
        FROM carousel_ads ca 
        LEFT JOIN businesses b ON ca.business_id = b.id 
        ORDER BY ca.position ASC, ca.created_at DESC
    ");
    $all_ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_ads = [];
}

// Get businesses for dropdown
$businesses = [];
try {
    // Check if status column exists in businesses table
    $checkBusinessStatus = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'status'");
    if ($checkBusinessStatus->rowCount() > 0) {
        $stmt = $pdo->query("SELECT id, business_name FROM businesses WHERE status = 'active' ORDER BY business_name");
    } else {
        // No status column, get all businesses
        $stmt = $pdo->query("SELECT id, business_name FROM businesses ORDER BY business_name");
    }
    $businesses = $stmt->fetchAll();
} catch (PDOException $e) {
    // Error querying businesses table, use empty array
    $businesses = [];
}

// Get admin info for header
$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminEmail = $_SESSION['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel Manager - JShuk Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css" />
    <style>
        body { background: #f4f6fa; }
        .sidebar { min-height: 100vh; background: #212529; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #343a40; color: #ffc107; }
        .carousel-preview { height: 300px; border-radius: 10px; overflow: hidden; }
        .swiper-slide { background-size: cover; background-position: center; }
        .carousel-content { background: rgba(0,0,0,0.6); color: white; padding: 20px; border-radius: 10px; }
        .stat-card { border-radius: 1rem; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.02); }
        .dark-mode { background: #181a1b !important; color: #e0e0e0 !important; }
        .dark-mode .sidebar { background: #181a1b !important; }
        .dark-mode .card, .dark-mode .table, .dark-mode .modal-content { background: #23272b !important; color: #e0e0e0; }
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
                    <li class="nav-item mb-1"><a href="businesses.php" class="nav-link"><i class="fas fa-store me-2"></i>Businesses</a></li>
                    <li class="nav-item mb-1"><a href="users.php" class="nav-link"><i class="fas fa-users me-2"></i>Users</a></li>
                    <li class="nav-item mb-1"><a href="categories.php" class="nav-link"><i class="fas fa-tags me-2"></i>Categories</a></li>
                    <li class="nav-item mb-1"><a href="recruitment.php" class="nav-link"><i class="fas fa-briefcase me-2"></i>Jobs</a></li>
                    <li class="nav-item mb-1"><a href="classifieds.php" class="nav-link"><i class="fas fa-list-alt me-2"></i>Classifieds</a></li>
                    <li class="nav-item mb-1"><a href="reviews.php" class="nav-link"><i class="fas fa-star me-2"></i>Reviews</a></li>
                    <li class="nav-item mb-1"><a href="ads.php" class="nav-link"><i class="fas fa-ad me-2"></i>Ads</a></li>
                    <li class="nav-item mb-1"><a href="carousel_manager.php" class="nav-link active"><i class="fas fa-images me-2"></i>Carousel</a></li>
                </ul>
                <hr class="text-white w-100">
                <a href="#" class="btn btn-secondary w-100 mb-2" id="toggleDarkMode"><i class="fa fa-moon me-2"></i>Toggle Dark Mode</a>
                <a href="../logout.php" class="btn btn-danger w-100"><i class="fa fa-sign-out-alt me-2"></i>Log out</a>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-lg-10 col-md-9 ms-sm-auto px-4 py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-1">🎠 Carousel Manager</h1>
                    <div class="text-muted">Manage homepage carousel ads and promotional content</div>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary">Welcome, <?= htmlspecialchars($adminName) ?></span>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="card stat-card border-primary shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3"><i class="fa fa-images fa-2x text-primary"></i></div>
                            <div>
                                <div class="fw-bold fs-5"><?= count($all_ads) ?></div>
                                <div class="text-muted">Total Ads</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-success shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3"><i class="fa fa-eye fa-2x text-success"></i></div>
                            <div>
                                <div class="fw-bold fs-5"><?= count($ads) ?></div>
                                <div class="text-muted">Active Ads</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-warning shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3"><i class="fa fa-clock fa-2x text-warning"></i></div>
                            <div>
                                <div class="fw-bold fs-5"><?= count(array_filter($all_ads, function($ad) { return $ad['is_auto_generated']; })) ?></div>
                                <div class="text-muted">Auto-Generated</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-info shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3"><i class="fa fa-store fa-2x text-info"></i></div>
                            <div>
                                <div class="fw-bold fs-5"><?= count(array_filter($all_ads, function($ad) { return !empty($ad['business_id']); })) ?></div>
                                <div class="text-muted">Business Ads</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carousel Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($ads)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                            <h5>No active carousel ads</h5>
                            <p class="text-muted">Add your first carousel ad below to see it here!</p>
                        </div>
                    <?php else: ?>
                        <div class="carousel-preview">
                            <div class="swiper">
                                <div class="swiper-wrapper">
                                    <?php foreach ($ads as $ad): ?>
                                        <div class="swiper-slide" style="background-image: url('<?= htmlspecialchars($ad['image_path']) ?>')">
                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                <div class="carousel-content text-center">
                                                    <h3><?= htmlspecialchars($ad['title']) ?></h3>
                                                    <?php if ($ad['subtitle']): ?>
                                                        <p><?= htmlspecialchars($ad['subtitle']) ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($ad['cta_url']): ?>
                                                        <a href="<?= htmlspecialchars($ad['cta_url']) ?>" class="btn btn-warning btn-sm" target="_blank">
                                                            <?= htmlspecialchars($ad['cta_text'] ?: 'Learn More') ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="swiper-pagination"></div>
                                <div class="swiper-button-prev"></div>
                                <div class="swiper-button-next"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add New Ad Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Carousel Ad</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="number" class="form-control" id="position" name="position" min="1" max="20" value="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <input type="text" class="form-control" id="subtitle" name="subtitle">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cta_text" class="form-label">CTA Button Text</label>
                                    <input type="text" class="form-control" id="cta_text" name="cta_text" placeholder="e.g., Learn More, Shop Now">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cta_url" class="form-label">CTA Link</label>
                                    <input type="url" class="form-control" id="cta_url" name="cta_url" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="business_id" class="form-label">Associated Business</label>
                                    <select class="form-select" id="business_id" name="business_id">
                                        <option value="">Select business (optional)</option>
                                        <?php foreach ($businesses as $business): ?>
                                            <option value="<?= $business['id'] ?>"><?= htmlspecialchars($business['business_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Background Image *</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                    <div class="form-text">Recommended: 1920x600px, max 5MB</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                <label class="form-check-label" for="active">Active</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Carousel Ad
                        </button>
                    </form>
                </div>
            </div>

            <!-- Manage Existing Ads -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Manage Carousel Ads</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($all_ads)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No carousel ads found. Add your first ad above!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Position</th>
                                        <th>Business</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_ads as $ad): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= htmlspecialchars($ad['image_path']) ?>" 
                                                     alt="<?= htmlspecialchars($ad['title']) ?>" 
                                                     style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($ad['title']) ?></strong>
                                                <?php if ($ad['subtitle']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($ad['subtitle']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $ad['position'] ?></td>
                                            <td><?= htmlspecialchars($ad['business_name'] ?? 'Manual') ?></td>
                                            <td>
                                                <span class="badge <?= $ad['active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $ad['active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($ad['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle">
                                                        <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                                        <button type="submit" class="btn <?= $ad['active'] ? 'btn-warning' : 'btn-success' ?>">
                                                            <i class="fas <?= $ad['active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this ad?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
<script>
// Initialize Swiper for carousel preview
const swiper = new Swiper('.swiper', {
    loop: true,
    autoplay: {
        delay: 4000,
        disableOnInteraction: false,
    },
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
});

// Dark mode toggle
document.getElementById('toggleDarkMode').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
});
</script>

</body>
</html> 