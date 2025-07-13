<?php
/**
 * Enhanced Carousel Manager - Advanced Admin Panel
 * JShuk Advanced Carousel Management System
 * Phase 2: Admin Control Panel
 */

require_once '../config/config.php';

// Check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $cta_text = trim($_POST['cta_text'] ?? '');
        $cta_link = trim($_POST['cta_link'] ?? '');
        $priority = (int) ($_POST['priority'] ?? 0);
        $location = trim($_POST['location'] ?? 'all');
        $sponsored = isset($_POST['sponsored']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;
        $zone = trim($_POST['zone'] ?? 'homepage');
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        // Validate required fields
        if (empty($title)) {
            $error = "Title is required.";
        } else {
            // Handle image upload
            $image_url = '';
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
                        $image_url = 'uploads/carousel/' . $filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }
            
            if (empty($error)) {
                try {
                    if ($action === 'add') {
                        $stmt = $pdo->prepare("
                            INSERT INTO carousel_slides (
                                title, subtitle, image_url, cta_text, cta_link, 
                                priority, location, sponsored, start_date, end_date, 
                                active, zone
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $title, $subtitle, $image_url, $cta_text, $cta_link,
                            $priority, $location, $sponsored, $start_date, $end_date,
                            $active, $zone
                        ]);
                        $success = "Carousel slide added successfully!";
                    } else {
                        $slide_id = (int) $_POST['slide_id'];
                        $update_fields = [
                            'title' => $title,
                            'subtitle' => $subtitle,
                            'cta_text' => $cta_text,
                            'cta_link' => $cta_link,
                            'priority' => $priority,
                            'location' => $location,
                            'sponsored' => $sponsored,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'active' => $active,
                            'zone' => $zone
                        ];
                        
                        if (!empty($image_url)) {
                            $update_fields['image_url'] = $image_url;
                        }
                        
                        $sql = "UPDATE carousel_slides SET " . 
                               implode(', ', array_map(fn($k) => "$k = ?", array_keys($update_fields))) .
                               " WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([...array_values($update_fields), $slide_id]);
                        $success = "Carousel slide updated successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete' && isset($_POST['slide_id'])) {
        $slide_id = (int) $_POST['slide_id'];
        try {
            // Get image path to delete file
            $stmt = $pdo->prepare("SELECT image_url FROM carousel_slides WHERE id = ?");
            $stmt->execute([$slide_id]);
            $slide = $stmt->fetch();
            
            if ($slide && file_exists('../' . $slide['image_url'])) {
                unlink('../' . $slide['image_url']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM carousel_slides WHERE id = ?");
            $stmt->execute([$slide_id]);
            $success = "Slide deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting slide: " . $e->getMessage();
        }
    } elseif ($action === 'toggle' && isset($_POST['slide_id'])) {
        $slide_id = (int) $_POST['slide_id'];
        try {
            $stmt = $pdo->prepare("UPDATE carousel_slides SET active = NOT active WHERE id = ?");
            $stmt->execute([$slide_id]);
            $success = "Slide status updated!";
        } catch (PDOException $e) {
            $error = "Error updating slide: " . $e->getMessage();
        }
    }
}

// Get filters
$location_filter = $_GET['location'] ?? '';
$zone_filter = $_GET['zone'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($location_filter) {
    $where_conditions[] = "location = ?";
    $params[] = $location_filter;
}

if ($zone_filter) {
    $where_conditions[] = "zone = ?";
    $params[] = $zone_filter;
}

if ($status_filter === 'active') {
    $where_conditions[] = "active = 1";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "active = 0";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch slides with filters
try {
    $stmt = $pdo->prepare("
        SELECT * FROM carousel_slides 
        $where_clause
        ORDER BY priority DESC, sponsored DESC, created_at DESC
    ");
    $stmt->execute($params);
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching slides: " . $e->getMessage();
    $slides = [];
}

// Get locations for filter dropdown
try {
    $stmt = $pdo->query("SELECT DISTINCT location FROM carousel_slides WHERE location != '' ORDER BY location");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $locations = [];
}

// Get zones for filter dropdown
try {
    $stmt = $pdo->query("SELECT DISTINCT zone FROM carousel_slides WHERE zone != '' ORDER BY zone");
    $zones = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $zones = [];
}

// Get analytics summary
try {
    $stmt = $pdo->query("
        SELECT 
            cs.id,
            cs.title,
            COUNT(CASE WHEN ca.event_type = 'impression' THEN 1 END) as impressions,
            COUNT(CASE WHEN ca.event_type = 'click' THEN 1 END) as clicks
        FROM carousel_slides cs
        LEFT JOIN carousel_analytics ca ON cs.id = ca.slide_id
        GROUP BY cs.id, cs.title
        ORDER BY impressions DESC
        LIMIT 10
    ");
    $analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $analytics = [];
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Carousel Manager - JShuk Admin</title>
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
        .sponsored-badge { background: linear-gradient(45deg, #ff6b6b, #ff8e53); }
        .filter-section { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .preview-container { border: 2px dashed #dee2e6; border-radius: 10px; padding: 20px; text-align: center; }
        .dark-mode { background: #181a1b !important; color: #e0e0e0 !important; }
        .dark-mode .sidebar { background: #181a1b !important; }
        .dark-mode .card, .dark-mode .table, .dark-mode .modal-content { background: #23272b !important; color: #e0e0e0; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h4 class="text-white">🎠 Carousel Manager</h4>
                    <p class="text-muted">Advanced Control Panel</p>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#slides">
                            <i class="fas fa-images me-2"></i>Manage Slides
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#analytics">
                            <i class="fas fa-chart-bar me-2"></i>Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#settings">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                </ul>
                
                <hr class="text-white">
                <div class="text-center">
                    <button id="toggleDarkMode" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-moon"></i> Dark Mode
                    </button>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Enhanced Carousel Manager</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlideModal">
                        <i class="fas fa-plus me-2"></i>Add New Slide
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Slides</h5>
                            <h2><?= count($slides) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Active Slides</h5>
                            <h2><?= count(array_filter($slides, fn($s) => $s['active'])) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Sponsored</h5>
                            <h2><?= count(array_filter($slides, fn($s) => $s['sponsored'])) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Zones</h5>
                            <h2><?= count(array_unique(array_column($slides, 'zone'))) ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <h5><i class="fas fa-filter me-2"></i>Filters</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Location</label>
                        <select name="location" class="form-select">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= $location_filter === $loc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($loc)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Zone</label>
                        <select name="zone" class="form-select">
                            <option value="">All Zones</option>
                            <?php foreach ($zones as $zone): ?>
                                <option value="<?= htmlspecialchars($zone) ?>" <?= $zone_filter === $zone ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($zone)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="?" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Slides Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Manage Carousel Slides</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($slides)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No carousel slides found. Add your first slide above!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Zone</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Dates</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slides as $slide): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= htmlspecialchars($slide['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($slide['title']) ?>" 
                                                     style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($slide['title']) ?></strong>
                                                <?php if ($slide['subtitle']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($slide['subtitle']) ?></small>
                                                <?php endif; ?>
                                                <?php if ($slide['sponsored']): ?>
                                                    <br><span class="badge sponsored-badge">Sponsored</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($slide['location'])) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars(ucfirst($slide['zone'])) ?></span>
                                            </td>
                                            <td><?= $slide['priority'] ?></td>
                                            <td>
                                                <span class="badge <?= $slide['active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $slide['active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($slide['start_date'] || $slide['end_date']): ?>
                                                    <small>
                                                        <?php if ($slide['start_date']): ?>
                                                            From: <?= date('M j, Y', strtotime($slide['start_date'])) ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($slide['end_date']): ?>
                                                            To: <?= date('M j, Y', strtotime($slide['end_date'])) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">No dates set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editSlide(<?= $slide['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle">
                                                        <input type="hidden" name="slide_id" value="<?= $slide['id'] ?>">
                                                        <button type="submit" class="btn <?= $slide['active'] ? 'btn-warning' : 'btn-success' ?>">
                                                            <i class="fas <?= $slide['active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this slide?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="slide_id" value="<?= $slide['id'] ?>">
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

            <!-- Analytics Section -->
            <?php if (!empty($analytics)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Analytics Overview</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Slide</th>
                                    <th>Impressions</th>
                                    <th>Clicks</th>
                                    <th>CTR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics as $stat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stat['title']) ?></td>
                                        <td><?= number_format($stat['impressions']) ?></td>
                                        <td><?= number_format($stat['clicks']) ?></td>
                                        <td>
                                            <?php 
                                            $ctr = $stat['impressions'] > 0 ? ($stat['clicks'] / $stat['impressions']) * 100 : 0;
                                            echo number_format($ctr, 2) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Enhanced Carousel Preview for Admins -->
<div class="container my-5">
    <h3 class="mb-3">Live Homepage Carousel Preview</h3>
    <?php
    // Show the enhanced carousel as it appears on the homepage
    $zone = 'homepage';
    $location = null;
    include '../sections/enhanced_carousel.php';
    ?>
</div>

<!-- Add/Edit Slide Modal -->
<div class="modal fade" id="addSlideModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Carousel Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data" id="addSlideForm">
                <div class="modal-body">
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
                                <label for="priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="priority" name="priority" min="0" max="100" value="0">
                                <div class="form-text">Higher numbers appear first</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subtitle" class="form-label">Subtitle</label>
                        <textarea class="form-control" id="subtitle" name="subtitle" rows="2"></textarea>
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
                                <label for="cta_link" class="form-label">CTA Link</label>
                                <input type="url" class="form-control" id="cta_link" name="cta_link" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="location" class="form-label">Target Location</label>
                                <select class="form-select" id="location" name="location">
                                    <option value="all">All Locations</option>
                                    <option value="london">London</option>
                                    <option value="manchester">Manchester</option>
                                    <option value="gateshead">Gateshead</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="zone" class="form-label">Zone</label>
                                <select class="form-select" id="zone" name="zone">
                                    <option value="homepage">Homepage</option>
                                    <option value="businesses">Businesses Page</option>
                                    <option value="post-business">Post Business</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="image" class="form-label">Background Image *</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                <div class="form-text">Recommended: 1920x600px, max 5MB</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sponsored" name="sponsored">
                            <label class="form-check-label" for="sponsored">Sponsored Content</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Slide</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Slide Modal -->
<div class="modal fade" id="editSlideModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Carousel Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data" id="editSlideForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="slide_id" id="edit_slide_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_title" class="form-label">Title *</label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="edit_priority" name="priority" min="0" max="100" value="0">
                                <div class="form-text">Higher numbers appear first</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_subtitle" class="form-label">Subtitle</label>
                        <textarea class="form-control" id="edit_subtitle" name="subtitle" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_cta_text" class="form-label">CTA Button Text</label>
                                <input type="text" class="form-control" id="edit_cta_text" name="cta_text" placeholder="e.g., Learn More, Shop Now">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_cta_link" class="form-label">CTA Link</label>
                                <input type="url" class="form-control" id="edit_cta_link" name="cta_link" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_location" class="form-label">Target Location</label>
                                <select class="form-select" id="edit_location" name="location">
                                    <option value="all">All Locations</option>
                                    <option value="london">London</option>
                                    <option value="manchester">Manchester</option>
                                    <option value="gateshead">Gateshead</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_zone" class="form-label">Zone</label>
                                <select class="form-select" id="edit_zone" name="zone">
                                    <option value="homepage">Homepage</option>
                                    <option value="businesses">Businesses Page</option>
                                    <option value="post-business">Post Business</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_image" class="form-label">Background Image</label>
                                <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                <div class="form-text">Leave blank to keep current image. Recommended: 1920x600px, max 5MB</div>
                                <img id="edit_image_preview" src="" alt="Current Image" style="max-width:100%;max-height:100px;margin-top:5px;display:none;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_sponsored" name="sponsored">
                            <label class="form-check-label" for="edit_sponsored">Sponsored Content</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_active" name="active">
                            <label class="form-check-label" for="edit_active">Active</label>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
<script>
// Dark mode toggle
document.getElementById('toggleDarkMode').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
});

// Edit slide function
function editSlide(slideId) {
    // Find the slide data from the PHP-rendered JS object
    const slides = <?php echo json_encode($slides); ?>;
    const slide = slides.find(s => s.id == slideId);
    if (!slide) return;
    document.getElementById('edit_slide_id').value = slide.id;
    document.getElementById('edit_title').value = slide.title;
    document.getElementById('edit_priority').value = slide.priority;
    document.getElementById('edit_subtitle').value = slide.subtitle;
    document.getElementById('edit_cta_text').value = slide.cta_text;
    document.getElementById('edit_cta_link').value = slide.cta_link;
    document.getElementById('edit_location').value = slide.location;
    document.getElementById('edit_zone').value = slide.zone;
    document.getElementById('edit_start_date').value = slide.start_date ? slide.start_date.split('T')[0] : '';
    document.getElementById('edit_end_date').value = slide.end_date ? slide.end_date.split('T')[0] : '';
    document.getElementById('edit_sponsored').checked = !!parseInt(slide.sponsored);
    document.getElementById('edit_active').checked = !!parseInt(slide.active);
    if (slide.image_url) {
        document.getElementById('edit_image_preview').src = slide.image_url;
        document.getElementById('edit_image_preview').style.display = 'block';
    } else {
        document.getElementById('edit_image_preview').style.display = 'none';
    }
    new bootstrap.Modal(document.getElementById('editSlideModal')).show();
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

</body>
</html> 