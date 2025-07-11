<?php
require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Initialize variables
$categories = [];
$locations = [];
$amenities = [];
$tags = [];
$errors = [];
$success = false;
$retreat_data = [];

try {
    if (isset($pdo) && $pdo) {
        // Load retreat categories
        $stmt = $pdo->query("SELECT id, name, slug, description, icon_class, emoji FROM retreat_categories WHERE is_active = 1 ORDER BY sort_order, name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load retreat locations
        $stmt = $pdo->query("SELECT id, name, slug, region FROM retreat_locations WHERE is_active = 1 ORDER BY sort_order, name");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load retreat amenities
        $stmt = $pdo->query("SELECT id, name, icon_class, category FROM retreat_amenities WHERE is_active = 1 ORDER BY sort_order, name");
        $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load retreat tags
        $stmt = $pdo->query("SELECT id, name, color FROM retreat_tags WHERE is_active = 1 ORDER BY sort_order, name");
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf_token = $_POST['csrf_token'] ?? '';
            
            if (!validateCsrfToken($csrf_token)) {
                $errors[] = "Invalid security token. Please try again.";
            } else {
                // Validate and sanitize input
                $retreat_data = [
                    'title' => trim($_POST['title'] ?? ''),
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'location_id' => (int)($_POST['location_id'] ?? 0),
                    'description' => trim($_POST['description'] ?? ''),
                    'short_description' => trim($_POST['short_description'] ?? ''),
                    'price_per_night' => (float)($_POST['price_per_night'] ?? 0),
                    'price_shabbos_package' => (float)($_POST['price_shabbos_package'] ?? 0),
                    'price_yt_package' => (float)($_POST['price_yt_package'] ?? 0),
                    'guest_capacity' => (int)($_POST['guest_capacity'] ?? 1),
                    'bedrooms' => (int)($_POST['bedrooms'] ?? 1),
                    'bathrooms' => (int)($_POST['bathrooms'] ?? 1),
                    'address' => trim($_POST['address'] ?? ''),
                    'postcode' => trim($_POST['postcode'] ?? ''),
                    'latitude' => (float)($_POST['latitude'] ?? 0),
                    'longitude' => (float)($_POST['longitude'] ?? 0),
                    'distance_to_shul' => (float)($_POST['distance_to_shul'] ?? 0),
                    'nearest_shul' => trim($_POST['nearest_shul'] ?? ''),
                    'private_entrance' => isset($_POST['private_entrance']),
                    'kosher_kitchen' => isset($_POST['kosher_kitchen']),
                    'kitchen_type' => $_POST['kitchen_type'] ?? 'parve',
                    'shabbos_equipped' => isset($_POST['shabbos_equipped']),
                    'plata_available' => isset($_POST['plata_available']),
                    'wifi_available' => isset($_POST['wifi_available']),
                    'air_conditioning' => isset($_POST['air_conditioning']),
                    'baby_cot_available' => isset($_POST['baby_cot_available']),
                    'no_stairs' => isset($_POST['no_stairs']),
                    'accessible' => isset($_POST['accessible']),
                    'mikveh_nearby' => isset($_POST['mikveh_nearby']),
                    'mikveh_distance' => (float)($_POST['mikveh_distance'] ?? 0),
                    'parking_available' => isset($_POST['parking_available']),
                    'garden_access' => isset($_POST['garden_access']),
                    'min_stay_nights' => (int)($_POST['min_stay_nights'] ?? 1),
                    'max_stay_nights' => (int)($_POST['max_stay_nights'] ?? 30),
                    'available_this_shabbos' => isset($_POST['available_this_shabbos']),
                    'instant_booking' => isset($_POST['instant_booking']),
                    'selected_amenities' => $_POST['amenities'] ?? [],
                    'selected_tags' => $_POST['tags'] ?? []
                ];
                
                // Validation
                if (empty($retreat_data['title'])) {
                    $errors[] = "Property title is required.";
                }
                
                if (empty($retreat_data['category_id'])) {
                    $errors[] = "Please select a property type.";
                }
                
                if (empty($retreat_data['location_id'])) {
                    $errors[] = "Please select a location.";
                }
                
                if (empty($retreat_data['description'])) {
                    $errors[] = "Property description is required.";
                }
                
                if (empty($retreat_data['short_description'])) {
                    $errors[] = "Short description is required.";
                }
                
                if ($retreat_data['price_per_night'] <= 0) {
                    $errors[] = "Please enter a valid price per night.";
                }
                
                if ($retreat_data['guest_capacity'] < 1) {
                    $errors[] = "Guest capacity must be at least 1.";
                }
                
                if (empty($retreat_data['address'])) {
                    $errors[] = "Property address is required.";
                }
                
                // If no errors, save the retreat
                if (empty($errors)) {
                    try {
                        $pdo->beginTransaction();
                        
                        // Insert retreat
                        $stmt = $pdo->prepare("
                            INSERT INTO retreats (
                                title, category_id, location_id, host_id, description, short_description,
                                price_per_night, price_shabbos_package, price_yt_package, guest_capacity,
                                bedrooms, bathrooms, address, postcode, latitude, longitude,
                                distance_to_shul, nearest_shul, private_entrance, kosher_kitchen,
                                kitchen_type, shabbos_equipped, plata_available, wifi_available,
                                air_conditioning, baby_cot_available, no_stairs, accessible,
                                mikveh_nearby, mikveh_distance, parking_available, garden_access,
                                min_stay_nights, max_stay_nights, available_this_shabbos,
                                instant_booking, status, submitted_by
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?
                            )
                        ");
                        
                        $stmt->execute([
                            $retreat_data['title'], $retreat_data['category_id'], $retreat_data['location_id'],
                            $_SESSION['user_id'], $retreat_data['description'], $retreat_data['short_description'],
                            $retreat_data['price_per_night'], $retreat_data['price_shabbos_package'],
                            $retreat_data['price_yt_package'], $retreat_data['guest_capacity'],
                            $retreat_data['bedrooms'], $retreat_data['bathrooms'], $retreat_data['address'],
                            $retreat_data['postcode'], $retreat_data['latitude'], $retreat_data['longitude'],
                            $retreat_data['distance_to_shul'], $retreat_data['nearest_shul'],
                            $retreat_data['private_entrance'], $retreat_data['kosher_kitchen'],
                            $retreat_data['kitchen_type'], $retreat_data['shabbos_equipped'],
                            $retreat_data['plata_available'], $retreat_data['wifi_available'],
                            $retreat_data['air_conditioning'], $retreat_data['baby_cot_available'],
                            $retreat_data['no_stairs'], $retreat_data['accessible'],
                            $retreat_data['mikveh_nearby'], $retreat_data['mikveh_distance'],
                            $retreat_data['parking_available'], $retreat_data['garden_access'],
                            $retreat_data['min_stay_nights'], $retreat_data['max_stay_nights'],
                            $retreat_data['available_this_shabbos'], $retreat_data['instant_booking'],
                            $_SESSION['user_id']
                        ]);
                        
                        $retreat_id = $pdo->lastInsertId();
                        
                        // Insert amenities
                        if (!empty($retreat_data['selected_amenities'])) {
                            $stmt = $pdo->prepare("INSERT INTO retreat_amenity_relations (retreat_id, amenity_id) VALUES (?, ?)");
                            foreach ($retreat_data['selected_amenities'] as $amenity_id) {
                                $stmt->execute([$retreat_id, $amenity_id]);
                            }
                        }
                        
                        // Insert tags
                        if (!empty($retreat_data['selected_tags'])) {
                            $stmt = $pdo->prepare("INSERT INTO retreat_tag_relations (retreat_id, tag_id) VALUES (?, ?)");
                            foreach ($retreat_data['selected_tags'] as $tag_id) {
                                $stmt->execute([$retreat_id, $tag_id]);
                            }
                        }
                        
                        $pdo->commit();
                        $success = true;
                        
                        // Redirect to success page or retreat detail page
                        header("Location: /retreat.php?id=$retreat_id&success=1");
                        exit;
                        
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $errors[] = "Database error: " . $e->getMessage();
                    }
                }
            }
        }
        
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

$pageTitle = "List Your Property | Retreats & Simcha Rentals - JShuk";
$page_css = "add_retreat.css";
$metaDescription = "List your property for Jewish retreats and simcha rentals. Join our community of trusted hosts and help families find perfect accommodations.";
$metaKeywords = "list property, jewish retreats, host property, simcha rental, kosher accommodation";

include 'includes/header_main.php';
?>

<!-- HERO SECTION -->
<section class="add-retreat-hero" data-scroll>
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">🏠 List Your Property</h1>
            <p class="hero-subtitle">Share your space with the Jewish community</p>
            <div class="hero-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <span>Fill out details</span>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <span>Add photos</span>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <span>Go live!</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FORM SECTION -->
<section class="add-retreat-form" data-scroll>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="retreat-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="title" class="form-label">Property Title *</label>
                                    <input type="text" id="title" name="title" class="form-control" 
                                           value="<?= htmlspecialchars($retreat_data['title'] ?? '') ?>" 
                                           placeholder="e.g., Beautiful Chosson/Kallah Flat in Golders Green" required>
                                    <small class="form-text">Make it descriptive and appealing</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category_id" class="form-label">Property Type *</label>
                                    <select id="category_id" name="category_id" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= (($retreat_data['category_id'] ?? 0) == $category['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['emoji']) ?> <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location_id" class="form-label">Location *</label>
                                    <select id="location_id" name="location_id" class="form-select" required>
                                        <option value="">Select Location</option>
                                        <?php foreach ($locations as $location): ?>
                                        <option value="<?= $location['id'] ?>" 
                                                <?= (($retreat_data['location_id'] ?? 0) == $location['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($location['name']) ?>, <?= htmlspecialchars($location['region']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guest_capacity" class="form-label">Sleeps *</label>
                                    <select id="guest_capacity" name="guest_capacity" class="form-select" required>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" 
                                                <?= (($retreat_data['guest_capacity'] ?? 1) == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> guest<?= $i > 1 ? 's' : '' ?>
                                        </option>
                                        <?php endfor; ?>
                                        <option value="15" <?= (($retreat_data['guest_capacity'] ?? 1) == 15) ? 'selected' : '' ?>>15+ guests</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="short_description" class="form-label">Short Description *</label>
                            <textarea id="short_description" name="short_description" class="form-control" rows="3" 
                                      placeholder="Brief description that appears in search results..." required><?= htmlspecialchars($retreat_data['short_description'] ?? '') ?></textarea>
                            <small class="form-text">Keep it under 500 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Full Description *</label>
                            <textarea id="description" name="description" class="form-control" rows="6" 
                                      placeholder="Detailed description of your property, amenities, and what makes it special..." required><?= htmlspecialchars($retreat_data['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Pricing -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-pound-sign"></i>
                            Pricing
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_per_night" class="form-label">Price per Night *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">£</span>
                                        <input type="number" id="price_per_night" name="price_per_night" class="form-control" 
                                               value="<?= htmlspecialchars($retreat_data['price_per_night'] ?? '') ?>" 
                                               min="0" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_shabbos_package" class="form-label">Shabbos Package</label>
                                    <div class="input-group">
                                        <span class="input-group-text">£</span>
                                        <input type="number" id="price_shabbos_package" name="price_shabbos_package" class="form-control" 
                                               value="<?= htmlspecialchars($retreat_data['price_shabbos_package'] ?? '') ?>" 
                                               min="0" step="0.01">
                                    </div>
                                    <small class="form-text">Optional special rate</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_yt_package" class="form-label">Yom Tov Package</label>
                                    <div class="input-group">
                                        <span class="input-group-text">£</span>
                                        <input type="number" id="price_yt_package" name="price_yt_package" class="form-control" 
                                               value="<?= htmlspecialchars($retreat_data['price_yt_package'] ?? '') ?>" 
                                               min="0" step="0.01">
                                    </div>
                                    <small class="form-text">Optional special rate</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Property Details -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-home"></i>
                            Property Details
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bedrooms" class="form-label">Bedrooms</label>
                                    <select id="bedrooms" name="bedrooms" class="form-select">
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?= $i ?>" 
                                                <?= (($retreat_data['bedrooms'] ?? 1) == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> bedroom<?= $i > 1 ? 's' : '' ?>
                                        </option>
                                        <?php endfor; ?>
                                        <option value="8" <?= (($retreat_data['bedrooms'] ?? 1) == 8) ? 'selected' : '' ?>>8+ bedrooms</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bathrooms" class="form-label">Bathrooms</label>
                                    <select id="bathrooms" name="bathrooms" class="form-select">
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <option value="<?= $i ?>" 
                                                <?= (($retreat_data['bathrooms'] ?? 1) == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> bathroom<?= $i > 1 ? 's' : '' ?>
                                        </option>
                                        <?php endfor; ?>
                                        <option value="6" <?= (($retreat_data['bathrooms'] ?? 1) == 6) ? 'selected' : '' ?>>6+ bathrooms</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="min_stay_nights" class="form-label">Minimum Stay</label>
                                    <select id="min_stay_nights" name="min_stay_nights" class="form-select">
                                        <option value="1" <?= (($retreat_data['min_stay_nights'] ?? 1) == 1) ? 'selected' : '' ?>>1 night</option>
                                        <option value="2" <?= (($retreat_data['min_stay_nights'] ?? 1) == 2) ? 'selected' : '' ?>>2 nights</option>
                                        <option value="3" <?= (($retreat_data['min_stay_nights'] ?? 1) == 3) ? 'selected' : '' ?>>3 nights</option>
                                        <option value="7" <?= (($retreat_data['min_stay_nights'] ?? 1) == 7) ? 'selected' : '' ?>>1 week</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">Full Address *</label>
                            <input type="text" id="address" name="address" class="form-control" 
                                   value="<?= htmlspecialchars($retreat_data['address'] ?? '') ?>" 
                                   placeholder="Street address" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="postcode" class="form-label">Postcode</label>
                                    <input type="text" id="postcode" name="postcode" class="form-control" 
                                           value="<?= htmlspecialchars($retreat_data['postcode'] ?? '') ?>" 
                                           placeholder="e.g., NW11 8AB">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nearest_shul" class="form-label">Nearest Shul</label>
                                    <input type="text" id="nearest_shul" name="nearest_shul" class="form-control" 
                                           value="<?= htmlspecialchars($retreat_data['nearest_shul'] ?? '') ?>" 
                                           placeholder="e.g., Golders Green Synagogue">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="distance_to_shul" class="form-label">Distance to Shul (meters)</label>
                                    <input type="number" id="distance_to_shul" name="distance_to_shul" class="form-control" 
                                           value="<?= htmlspecialchars($retreat_data['distance_to_shul'] ?? '') ?>" 
                                           min="0" step="10" placeholder="e.g., 500">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mikveh_distance" class="form-label">Distance to Mikveh (meters)</label>
                                    <input type="number" id="mikveh_distance" name="mikveh_distance" class="form-control" 
                                           value="<?= htmlspecialchars($retreat_data['mikveh_distance'] ?? '') ?>" 
                                           min="0" step="10" placeholder="e.g., 1000">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden coordinates for map -->
                        <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($retreat_data['latitude'] ?? '') ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($retreat_data['longitude'] ?? '') ?>">
                    </div>
                    
                    <!-- Amenities -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-list-check"></i>
                            Amenities & Features
                        </h3>
                        
                        <div class="amenities-grid">
                            <?php 
                            $amenity_categories = ['essential', 'comfort', 'luxury', 'accessibility', 'kosher'];
                            foreach ($amenity_categories as $category):
                                $category_amenities = array_filter($amenities, fn($a) => $a['category'] === $category);
                                if (!empty($category_amenities)):
                            ?>
                            <div class="amenity-category">
                                <h4 class="category-title"><?= ucfirst($category) ?> Amenities</h4>
                                <div class="amenity-options">
                                    <?php foreach ($category_amenities as $amenity): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="amenity_<?= $amenity['id'] ?>" 
                                               name="amenities[]" value="<?= $amenity['id'] ?>" 
                                               class="form-check-input"
                                               <?= in_array($amenity['id'], $retreat_data['selected_amenities'] ?? []) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="amenity_<?= $amenity['id'] ?>">
                                            <i class="<?= htmlspecialchars($amenity['icon_class']) ?>"></i>
                                            <?= htmlspecialchars($amenity['name']) ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        
                        <!-- Quick checkboxes for common features -->
                        <div class="quick-features">
                            <h4 class="category-title">Quick Features</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" id="private_entrance" name="private_entrance" 
                                               class="form-check-input"
                                               <?= ($retreat_data['private_entrance'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="private_entrance">
                                            <i class="fas fa-door-open"></i>
                                            Private entrance
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" id="kosher_kitchen" name="kosher_kitchen" 
                                               class="form-check-input"
                                               <?= ($retreat_data['kosher_kitchen'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="kosher_kitchen">
                                            <i class="fas fa-utensils"></i>
                                            Kosher kitchen
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" id="shabbos_equipped" name="shabbos_equipped" 
                                               class="form-check-input"
                                               <?= ($retreat_data['shabbos_equipped'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="shabbos_equipped">
                                            <i class="fas fa-star-of-david"></i>
                                            Shabbos equipped
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" id="no_stairs" name="no_stairs" 
                                               class="form-check-input"
                                               <?= ($retreat_data['no_stairs'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="no_stairs">
                                            <i class="fas fa-wheelchair"></i>
                                            No stairs
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" id="wifi_available" name="wifi_available" 
                                               class="form-check-input"
                                               <?= ($retreat_data['wifi_available'] ?? true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="wifi_available">
                                            <i class="fas fa-wifi"></i>
                                            WiFi available
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" id="parking_available" name="parking_available" 
                                               class="form-check-input"
                                               <?= ($retreat_data['parking_available'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="parking_available">
                                            <i class="fas fa-parking"></i>
                                            Parking available
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" id="garden_access" name="garden_access" 
                                               class="form-check-input"
                                               <?= ($retreat_data['garden_access'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="garden_access">
                                            <i class="fas fa-seedling"></i>
                                            Garden access
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" id="available_this_shabbos" name="available_this_shabbos" 
                                               class="form-check-input"
                                               <?= ($retreat_data['available_this_shabbos'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="available_this_shabbos">
                                            <i class="fas fa-calendar-check"></i>
                                            Available this Shabbos
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kitchen Type -->
                        <div class="form-group">
                            <label for="kitchen_type" class="form-label">Kitchen Type</label>
                            <select id="kitchen_type" name="kitchen_type" class="form-select">
                                <option value="parve" <?= (($retreat_data['kitchen_type'] ?? 'parve') === 'parve') ? 'selected' : '' ?>>Parve only</option>
                                <option value="meat" <?= (($retreat_data['kitchen_type'] ?? 'parve') === 'meat') ? 'selected' : '' ?>>Meat kitchen</option>
                                <option value="dairy" <?= (($retreat_data['kitchen_type'] ?? 'parve') === 'dairy') ? 'selected' : '' ?>>Dairy kitchen</option>
                                <option value="separate" <?= (($retreat_data['kitchen_type'] ?? 'parve') === 'separate') ? 'selected' : '' ?>>Separate meat/dairy</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Tags -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-tags"></i>
                            Property Tags
                        </h3>
                        <p class="section-description">Select tags that best describe your property</p>
                        
                        <div class="tags-grid">
                            <?php foreach ($tags as $tag): ?>
                            <div class="form-check">
                                <input type="checkbox" id="tag_<?= $tag['id'] ?>" 
                                       name="tags[]" value="<?= $tag['id'] ?>" 
                                       class="form-check-input"
                                       <?= in_array($tag['id'], $retreat_data['selected_tags'] ?? []) ? 'checked' : '' ?>>
                                <label class="form-check-label tag-label" for="tag_<?= $tag['id'] ?>" 
                                       style="--tag-color: <?= htmlspecialchars($tag['color']) ?>">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Submit Section -->
                    <div class="form-section submit-section">
                        <div class="submit-info">
                            <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                            <ul>
                                <li>Your listing will be reviewed by our team (usually within 24 hours)</li>
                                <li>You'll receive an email confirmation once approved</li>
                                <li>You can add photos and manage your listing from your dashboard</li>
                                <li>Guests can start booking your property once it's live</li>
                            </ul>
                        </div>
                        
                        <div class="submit-actions">
                            <button type="submit" class="btn-jshuk-primary btn-lg">
                                <i class="fas fa-paper-plane"></i>
                                Submit Listing
                            </button>
                            <a href="/retreats.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i>
                                Back to Browse
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for short description
    const shortDesc = document.getElementById('short_description');
    const shortDescCounter = document.createElement('small');
    shortDescCounter.className = 'form-text text-muted';
    shortDesc.parentNode.appendChild(shortDescCounter);
    
    function updateCounter() {
        const remaining = 500 - shortDesc.value.length;
        shortDescCounter.textContent = `${remaining} characters remaining`;
        shortDescCounter.style.color = remaining < 50 ? '#dc3545' : '#6c757d';
    }
    
    shortDesc.addEventListener('input', updateCounter);
    updateCounter();
    
    // Auto-calculate coordinates from postcode (placeholder for future implementation)
    const postcodeInput = document.getElementById('postcode');
    const addressInput = document.getElementById('address');
    
    // This would integrate with a geocoding service
    function updateCoordinates() {
        // Placeholder for geocoding API integration
        console.log('Would geocode:', postcodeInput.value || addressInput.value);
    }
    
    postcodeInput.addEventListener('blur', updateCoordinates);
    addressInput.addEventListener('blur', updateCoordinates);
    
    // Form validation
    const form = document.querySelector('.retreat-form');
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Remove validation styling on input
    form.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
});
</script>

<?php include 'includes/footer_main.php'; ?> 