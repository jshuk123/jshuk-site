<?php
/**
 * Edit Ad Form
 * Allows editing of existing advertisements
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

// Check admin access
function checkAdminAccess() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ../index.php');
        exit;
    }
}
checkAdminAccess();

// Get ad ID
$adId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$adId) {
    header('Location: ads.php');
    exit;
}

// Get ad data
$stmt = $pdo->prepare("
    SELECT a.*, c.name as category_name, b.business_name 
    FROM ads a 
    LEFT JOIN business_categories c ON a.category_id = c.id 
    LEFT JOIN businesses b ON a.business_id = b.id 
    WHERE a.id = ?
");
$stmt->execute([$adId]);
$ad = $stmt->fetch();

if (!$ad) {
    header('Location: ads.php?error=not_found');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $linkUrl = trim($_POST['link_url']);
    $zone = $_POST['zone'];
    $categoryId = $_POST['category_id'] ?: null;
    $location = $_POST['location'] ?: null;
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $status = $_POST['status'];
    $priority = (int)$_POST['priority'];
    $businessId = $_POST['business_id'] ?: null;
    $ctaText = trim($_POST['cta_text']);

    $errors = [];

    // SMART DATE DEFAULTS - Prevent invisible ads
    if (empty($startDate)) {
        $startDate = date('Y-m-d');
        $errors[] = "Start date was empty - defaulted to today (" . $startDate . ").";
    }
    if (empty($endDate)) {
        $endDate = date('Y-m-d', strtotime('+6 months'));
        $errors[] = "End date was empty - defaulted to 6 months from today (" . $endDate . ").";
    }

    // Validation
    if (empty($title)) $errors[] = "Title is required.";
    if (empty($linkUrl)) $errors[] = "Link URL is required.";
    if (!filter_var($linkUrl, FILTER_VALIDATE_URL)) $errors[] = "Please enter a valid URL.";
    if (empty($zone)) $errors[] = "Zone is required.";
    
    // ENHANCED DATE VALIDATION
    if ($startDate > $endDate) {
        $errors[] = "Start date cannot be after end date.";
    }
    if ($endDate < date('Y-m-d')) {
        $errors[] = "End date cannot be in the past.";
    }
    
    if ($priority < 1 || $priority > 10) $errors[] = "Priority must be between 1 and 10.";

    // Handle file upload (optional)
    $imageUrl = $ad['image_url']; // Keep existing image by default
    if ($_FILES['image']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errors[] = "Please upload a valid image file (JPEG, PNG, GIF, or WebP).";
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $errors[] = "Image file size must be less than 5MB.";
        } else {
            $uploadDir = "../uploads/ads/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                // Delete old image if it exists
                if ($ad['image_url'] && file_exists($uploadDir . $ad['image_url'])) {
                    unlink($uploadDir . $ad['image_url']);
                }
                $imageUrl = $fileName;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }

    // If no errors, update the ad
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE ads SET 
                    title = ?, image_url = ?, link_url = ?, zone = ?, category_id = ?, 
                    location = ?, start_date = ?, end_date = ?, status = ?, priority = ?, 
                    business_id = ?, cta_text = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $title, $imageUrl, $linkUrl, $zone, $categoryId, $location,
                $startDate, $endDate, $status, $priority, $businessId, $ctaText, $adId
            ]);

            // Log the action
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'UPDATE',
                'ads',
                $adId,
                "Updated ad: $title",
                $_SERVER['REMOTE_ADDR']
            ]);

            header('Location: ads.php?success=4');
            exit;

        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get data for dropdowns
$categories = $pdo->query("SELECT id, name FROM business_categories ORDER BY name")->fetchAll();

// Get businesses for filter - check if status column exists in businesses table
$businesses = [];
try {
    // Check if status column exists in businesses table
    $checkBusinessStatus = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'status'");
    if ($checkBusinessStatus->rowCount() > 0) {
        $businesses = $pdo->query("SELECT id, business_name FROM businesses WHERE status = 'active' ORDER BY business_name")->fetchAll();
    } else {
        // No status column, get all businesses
        $businesses = $pdo->query("SELECT id, business_name FROM businesses ORDER BY business_name")->fetchAll();
    }
} catch (PDOException $e) {
    // Error querying businesses table, use empty array
    $businesses = [];
}

// Predefined locations
$locations = ['London', 'Manchester', 'Birmingham', 'Leeds', 'Liverpool', 'Sheffield', 'Edinburgh', 'Glasgow', 'Cardiff', 'Belfast'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Ad - JShuk Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/admin_ads.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Advertisement</h1>
            <p class="text-muted">Modify advertisement: <?= htmlspecialchars($ad['title']) ?></p>
        </div>
        <div>
            <a href="ads.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Ads
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Ad Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="adForm">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Basic Information</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Ad Title *</label>
                                    <input type="text" name="title" class="form-control" 
                                           value="<?= htmlspecialchars($ad['title']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Link URL *</label>
                                    <input type="url" name="link_url" class="form-control" 
                                           value="<?= htmlspecialchars($ad['link_url']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ad Image</label>
                                    <input type="file" id="ad_image" name="ad_image" onchange="previewAdImage(this)">
                                    <small class="text-muted">Leave empty to keep current image. Max size: 5MB. Formats: JPEG, PNG, GIF, WebP</small>
                                    
                                    <!-- Ad Preview -->
                                    <div id="ad-preview" class="ad-header" style="margin-top:20px;">
                                        <img id="ad-preview-img" src="<?php echo isset($ad['image_url']) ? '/uploads/ads/' . htmlspecialchars($ad['image_url']) : ''; ?>" alt="Ad Preview" style="<?php echo isset($ad['image_url']) ? 'display:block;' : 'display:none;'; ?>">
                                        <div id="ad-preview-label" class="ad-label" style="<?php echo isset($ad['image_url']) ? 'display:block;' : 'display:none;'; ?>">ADVERTISEMENT</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">CTA Button Text</label>
                                    <input type="text" name="cta_text" class="form-control" 
                                           value="<?= htmlspecialchars($ad['cta_text'] ?? '') ?>"
                                           placeholder="e.g., Shop Now, Learn More, Book Now">
                                </div>
                            </div>

                            <!-- Targeting & Settings -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Targeting & Settings</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Zone *</label>
                                    <select name="zone" class="form-select" required>
                                        <option value="">Select Zone</option>
                                        <option value="header" <?= $ad['zone'] === 'header' ? 'selected' : '' ?>>Header</option>
                                        <option value="sidebar" <?= $ad['zone'] === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                                        <option value="footer" <?= $ad['zone'] === 'footer' ? 'selected' : '' ?>>Footer</option>
                                        <option value="carousel" <?= $ad['zone'] === 'carousel' ? 'selected' : '' ?>>Carousel</option>
                                        <option value="inline" <?= $ad['zone'] === 'inline' ? 'selected' : '' ?>>Inline</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Category (Optional)</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $ad['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Location (Optional)</label>
                                    <select name="location" class="form-select">
                                        <option value="">All Locations</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?= $loc ?>" <?= $ad['location'] === $loc ? 'selected' : '' ?>>
                                                <?= $loc ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Business (Optional)</label>
                                    <select name="business_id" class="form-select">
                                        <option value="">General Ad</option>
                                        <?php foreach ($businesses as $biz): ?>
                                            <option value="<?= $biz['id'] ?>" <?= $ad['business_id'] == $biz['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($biz['business_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <!-- Scheduling -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Scheduling</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Start Date *</label>
                                    <input type="date" name="start_date" class="form-control" 
                                           value="<?= $ad['start_date'] ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">End Date *</label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="<?= $ad['end_date'] ?>" required>
                                </div>
                            </div>

                            <!-- Status & Priority -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Status & Priority</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="paused" <?= $ad['status'] === 'paused' ? 'selected' : '' ?>>Paused</option>
                                        <option value="active" <?= $ad['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="expired" <?= $ad['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Priority (1-10)</label>
                                    <input type="number" name="priority" class="form-control" 
                                           value="<?= $ad['priority'] ?>" min="1" max="10" required>
                                    <small class="text-muted">Higher priority ads are shown first</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="ads.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Ad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye"></i> Live Preview</h5>
                </div>
                <div class="card-body">
                    <div id="adPreview" class="ad-preview-container has-preview">
                        <?php if ($ad['image_url']): ?>
                            <div class="ad-container ad-<?= $ad['zone'] ?>">
                                <span class="ad-label">Advertisement</span>
                                <a href="<?= htmlspecialchars($ad['link_url']) ?>" target="_blank" class="ad-link">
                                    <img src="<?= BASE_PATH . 'uploads/ads/' . $ad['image_url'] ?>" 
                                         alt="<?= htmlspecialchars($ad['title']) ?>" class="ad-preview">
                                    <?php if ($ad['cta_text']): ?>
                                        <div class="ad-cta"><?= htmlspecialchars($ad['cta_text']) ?></div>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-image fa-3x mb-3"></i>
                                <p>No image available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Ad Statistics -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Ad Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-item">
                                <h4 class="text-primary mb-1">0</h4>
                                <small class="text-muted">Views</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <h4 class="text-success mb-1">0</h4>
                                <small class="text-muted">Clicks</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="small text-muted">
                        <div><strong>Created:</strong> <?= date('M j, Y', strtotime($ad['created_at'])) ?></div>
                        <div><strong>Last Updated:</strong> <?= date('M j, Y', strtotime($ad['updated_at'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- Zone Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Zone Information</h6>
                </div>
                <div class="card-body">
                    <div class="zone-info">
                        <div class="zone-item <?= $ad['zone'] === 'header' ? 'active' : '' ?>" data-zone="header">
                            <strong>Header:</strong> Top of page, full width
                        </div>
                        <div class="zone-item <?= $ad['zone'] === 'sidebar' ? 'active' : '' ?>" data-zone="sidebar">
                            <strong>Sidebar:</strong> Right sidebar, vertical format
                        </div>
                        <div class="zone-item <?= $ad['zone'] === 'footer' ? 'active' : '' ?>" data-zone="footer">
                            <strong>Footer:</strong> Bottom of page, full width
                        </div>
                        <div class="zone-item <?= $ad['zone'] === 'carousel' ? 'active' : '' ?>" data-zone="carousel">
                            <strong>Carousel:</strong> Homepage carousel rotation
                        </div>
                        <div class="zone-item <?= $ad['zone'] === 'inline' ? 'active' : '' ?>" data-zone="inline">
                            <strong>Inline:</strong> Within content areas
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/ad_preview.js"></script>
<script>
function previewAdImage(input) {
    const previewImg = document.getElementById('ad-preview-img');
    const label = document.getElementById('ad-preview-label');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            label.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html> 