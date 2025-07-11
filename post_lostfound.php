<?php
require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$success_message = '';
$error_message = '';
$categories = [];
$locations = [];

try {
    if (isset($pdo) && $pdo) {
        // Load categories and locations
        $stmt = $pdo->query("SELECT name, icon FROM lostfound_categories WHERE is_active = 1 ORDER BY sort_order, name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT name, area FROM lostfound_locations WHERE is_active = 1 ORDER BY sort_order, name");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    if (APP_DEBUG) {
        error_log("Lost & Found categories/locations error: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid request. Please try again.";
    } else {
        try {
            // Validate required fields
            $required_fields = ['post_type', 'title', 'category', 'location', 'date_lost_found', 'description'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Please fill in all required fields.");
                }
            }
            
            // Validate post type
            if (!in_array($_POST['post_type'], ['lost', 'found'])) {
                throw new Exception("Invalid post type.");
            }
            
            // Validate date
            $date_lost_found = DateTime::createFromFormat('Y-m-d', $_POST['date_lost_found']);
            if (!$date_lost_found) {
                throw new Exception("Invalid date format.");
            }
            
            // Handle image uploads
            $image_paths = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/lostfound/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_type = $_FILES['images']['type'][$key];
                        $file_size = $_FILES['images']['size'][$key];
                        
                        if (!in_array($file_type, $allowed_types)) {
                            throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.");
                        }
                        
                        if ($file_size > $max_size) {
                            throw new Exception("File too large. Maximum size is 5MB.");
                        }
                        
                        $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                        $filename = uniqid() . '_' . time() . '.' . $file_extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $filepath)) {
                            $image_paths[] = $filepath;
                        } else {
                            throw new Exception("Failed to upload image.");
                        }
                    }
                }
            }
            
            // Prepare data for insertion
            $data = [
                'post_type' => $_POST['post_type'],
                'title' => trim($_POST['title']),
                'category' => $_POST['category'],
                'location' => $_POST['location'],
                'date_lost_found' => $_POST['date_lost_found'],
                'description' => trim($_POST['description']),
                'image_paths' => !empty($image_paths) ? json_encode($image_paths) : null,
                'is_blurred' => isset($_POST['is_blurred']) ? 1 : 0,
                'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'contact_whatsapp' => trim($_POST['contact_whatsapp'] ?? ''),
                'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
                'hide_contact_until_verified' => isset($_POST['hide_contact_until_verified']) ? 1 : 0,
                'user_id' => $_SESSION['user_id'] ?? null
            ];
            
            // Insert into database
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $query = "INSERT INTO lostfound_posts ({$columns}) VALUES ({$placeholders})";
            $stmt = $pdo->prepare($query);
            $stmt->execute($data);
            
            $success_message = "Your item has been posted successfully! The community will help you find it.";
            
            // Clear form data
            $_POST = [];
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

$pageTitle = "Post Lost or Found Item | JShuk";
$page_css = "post_lostfound.css";
$metaDescription = "Post a lost or found item on JShuk's community board. Follow halachic guidelines and help reunite people with their belongings.";
$metaKeywords = "post lost item, post found item, lost and found, jewish community, halachic guidelines";

include 'includes/header_main.php';
?>

<!-- HERO SECTION -->
<section class="hero-section bg-gradient-primary text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="h2 fw-bold mb-3">
                    <i class="fas fa-plus-circle me-2"></i>Post a Lost or Found Item
                </h1>
                <p class="lead mb-0">
                    Help reunite people with their belongings — one mitzvah at a time.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- MAIN CONTENT -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Post Form -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-edit me-2"></i>Item Details
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="postForm">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            
                            <!-- Post Type -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Post Type *</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="post_type" 
                                                   id="post_type_lost" value="lost" 
                                                   <?= ($_POST['post_type'] ?? '') === 'lost' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="post_type_lost">
                                                <i class="fas fa-search text-danger me-1"></i>Lost Item
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="post_type" 
                                                   id="post_type_found" value="found" 
                                                   <?= ($_POST['post_type'] ?? '') === 'found' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="post_type_found">
                                                <i class="fas fa-hand-holding text-primary me-1"></i>Found Item
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Halachic Reminder for Found Items -->
                            <div id="halachic-reminder" class="alert alert-info d-none">
                                <div class="d-flex">
                                    <i class="fas fa-info-circle me-2 mt-1"></i>
                                    <div>
                                        <strong>Halacha Reminder:</strong> Please don't describe found items in full detail.
                                        <br>Example: say "Found an item near Golders Green Tesco" instead of "Found a silver bracelet with three stones."
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Basic Information -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Item Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                                               placeholder="Brief description of the item" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category *</label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                                        <?= ($_POST['category'] ?? '') === $cat['name'] ? 'selected' : '' ?>>
                                                    <i class="<?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location *</label>
                                        <select class="form-select" id="location" name="location" required>
                                            <option value="">Select Location</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?= htmlspecialchars($loc['name']) ?>" 
                                                        <?= ($_POST['location'] ?? '') === $loc['name'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($loc['name']) ?>
                                                    <?= $loc['area'] ? ' (' . htmlspecialchars($loc['area']) . ')' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date_lost_found" class="form-label">Date Lost/Found *</label>
                                        <input type="date" class="form-control" id="date_lost_found" name="date_lost_found" 
                                               value="<?= htmlspecialchars($_POST['date_lost_found'] ?? '') ?>" 
                                               max="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Provide a detailed description of the item..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                <div class="form-text">
                                    <span id="char-count">0</span>/1000 characters
                                </div>
                            </div>
                            
                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label class="form-label">Images (optional)</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" name="images[]" 
                                           accept="image/*" multiple id="imageUpload">
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearImages()">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                                <div class="form-text">
                                    You can upload up to 3 images. Maximum size: 5MB each.
                                </div>
                                
                                <!-- Image Preview -->
                                <div id="imagePreview" class="mt-3 d-none">
                                    <div class="row g-2" id="previewContainer"></div>
                                </div>
                                
                                <!-- Blur Option -->
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_blurred" id="is_blurred" 
                                           <?= isset($_POST['is_blurred']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_blurred">
                                        Blur images for privacy (recommended for found items)
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="card bg-light mb-3">
                                <div class="card-header">
                                    <h5 class="h6 mb-0">
                                        <i class="fas fa-address-book me-2"></i>Contact Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="contact_phone" class="form-label">Phone</label>
                                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                                       value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>" 
                                                       placeholder="+44 123 456 7890">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="contact_email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                                       value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>" 
                                                       placeholder="your@email.com">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="contact_whatsapp" class="form-label">WhatsApp</label>
                                                <input type="text" class="form-control" id="contact_whatsapp" name="contact_whatsapp" 
                                                       value="<?= htmlspecialchars($_POST['contact_whatsapp'] ?? '') ?>" 
                                                       placeholder="+44 123 456 7890">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Privacy Options -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous" 
                                                       <?= isset($_POST['is_anonymous']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_anonymous">
                                                    Post anonymously
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="hide_contact_until_verified" 
                                                       id="hide_contact_until_verified" 
                                                       <?= isset($_POST['hide_contact_until_verified']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="hide_contact_until_verified">
                                                    Hide contact details until simanim are submitted
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Buttons -->
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Post Item
                                </button>
                                <a href="/lostfound.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Lost & Found
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="h6 mb-0">
                            <i class="fas fa-question-circle me-2"></i>Need Help?
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>For Lost Items:</h6>
                                <ul class="small">
                                    <li>Be specific about when and where you lost it</li>
                                    <li>Include unique identifying features</li>
                                    <li>Provide multiple contact methods</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>For Found Items:</h6>
                                <ul class="small">
                                    <li>Don't describe in full detail (halachic requirement)</li>
                                    <li>Mention general location and date</li>
                                    <li>Enable contact hiding until verified</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Character counter
document.getElementById('description').addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('char-count').textContent = count;
    if (count > 900) {
        document.getElementById('char-count').style.color = '#dc3545';
    } else {
        document.getElementById('char-count').style.color = '#6c757d';
    }
});

// Show/hide halachic reminder based on post type
document.querySelectorAll('input[name="post_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const reminder = document.getElementById('halachic-reminder');
        if (this.value === 'found') {
            reminder.classList.remove('d-none');
        } else {
            reminder.classList.add('d-none');
        }
    });
});

// Image preview functionality
document.getElementById('imageUpload').addEventListener('change', function() {
    const files = this.files;
    const preview = document.getElementById('imagePreview');
    const container = document.getElementById('previewContainer');
    
    if (files.length > 0) {
        preview.classList.remove('d-none');
        container.innerHTML = '';
        
        for (let i = 0; i < Math.min(files.length, 3); i++) {
            const file = files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'col-md-4';
                div.innerHTML = `
                    <div class="position-relative">
                        <img src="${e.target.result}" class="img-fluid rounded" style="height: 150px; object-fit: cover;">
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                onclick="removeImage(${i})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                container.appendChild(div);
            };
            
            reader.readAsDataURL(file);
        }
    } else {
        preview.classList.add('d-none');
    }
});

function clearImages() {
    document.getElementById('imageUpload').value = '';
    document.getElementById('imagePreview').classList.add('d-none');
    document.getElementById('previewContainer').innerHTML = '';
}

function removeImage(index) {
    const input = document.getElementById('imageUpload');
    const dt = new DataTransfer();
    
    for (let i = 0; i < input.files.length; i++) {
        if (i !== index) {
            dt.items.add(input.files[i]);
        }
    }
    
    input.files = dt.files;
    
    // Trigger change event to update preview
    const event = new Event('change');
    input.dispatchEvent(event);
}

// Form validation
document.getElementById('postForm').addEventListener('submit', function(e) {
    const description = document.getElementById('description').value;
    if (description.length > 1000) {
        e.preventDefault();
        alert('Description must be 1000 characters or less.');
        return false;
    }
    
    const files = document.getElementById('imageUpload').files;
    for (let file of files) {
        if (file.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('Each image must be 5MB or less.');
            return false;
        }
    }
});
</script>

<?php include 'includes/footer_main.php'; ?> 