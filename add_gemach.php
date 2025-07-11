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

try {
    if (isset($pdo) && $pdo) {
        // Load gemach categories
        $stmt = $pdo->query("SELECT id, name, slug FROM gemach_categories WHERE is_active = 1 ORDER BY sort_order, name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid request. Please try again.');
            }
            
            // Validate required fields
            $required_fields = ['name', 'category_id', 'description', 'location', 'contact_phone'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Please fill in all required fields.");
                }
            }
            
            // Validate email if provided
            if (!empty($_POST['contact_email']) && !filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }
            
            // Validate phone number
            if (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $_POST['contact_phone'])) {
                throw new Exception("Please enter a valid phone number.");
            }
            
            // Handle image uploads
            $image_paths = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/gemachim/';
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
                            throw new Exception("Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.");
                        }
                        
                        if ($file_size > $max_size) {
                            throw new Exception("File size too large. Maximum size is 5MB.");
                        }
                        
                        $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $image_paths[] = $file_path;
                        } else {
                            throw new Exception("Failed to upload image. Please try again.");
                        }
                    }
                }
            }
            
            // Prepare WhatsApp link
            $whatsapp_link = '';
            if (!empty($_POST['whatsapp_number'])) {
                $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number']);
                $whatsapp_link = "https://wa.me/{$whatsapp_number}";
            }
            
            // Insert gemach into database
            $stmt = $pdo->prepare("
                INSERT INTO gemachim (
                    name, category_id, description, location, contact_phone, 
                    contact_email, whatsapp_link, image_paths, donation_enabled, 
                    donation_link, in_memory_of, submitted_by, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['category_id'],
                $_POST['description'],
                $_POST['location'],
                $_POST['contact_phone'],
                $_POST['contact_email'] ?? null,
                $whatsapp_link,
                !empty($image_paths) ? json_encode($image_paths) : null,
                isset($_POST['donation_enabled']) ? 1 : 0,
                $_POST['donation_link'] ?? null,
                $_POST['in_memory_of'] ?? null,
                $_SESSION['user_id'] ?? null
            ]);
            
            $success_message = "Thank you! Your gemach has been submitted and is pending admin approval. We'll notify you once it's live.";
            
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$pageTitle = "Add a Gemach | Submit Your Community Gemach - JShuk";
$page_css = "add_gemach.css";
$metaDescription = "Submit your gemach to the JShuk community directory. Share your items with the Jewish community and help others in need. Easy submission process with admin approval.";
$metaKeywords = "add gemach, submit gemach, community lending, jewish community, mitzvah, charity";

include 'includes/header_main.php';
?>

<!-- HERO SECTION -->
<section class="add-gemach-hero" data-scroll>
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Add Your Gemach</h1>
            <p class="hero-subtitle">Share your items with the community and help others in need.</p>
        </div>
    </div>
</section>

<!-- FORM SECTION -->
<section class="add-gemach-form" data-scroll>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
                <?php endif; ?>
                
                <div class="form-card">
                    <h2 class="form-title">Gemach Details</h2>
                    <p class="form-subtitle">All submissions require admin approval before going live.</p>
                    
                    <form method="POST" enctype="multipart/form-data" id="add-gemach-form">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3 class="section-title">Basic Information</h3>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Gemach Name *</label>
                                        <input type="text" id="name" name="name" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                        <div class="form-text">Give your gemach a clear, descriptive name</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select id="category_id" name="category_id" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    <?= (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description" class="form-label">Description *</label>
                                <textarea id="description" name="description" class="form-control" rows="4" required
                                          placeholder="Describe what items are available, conditions, and any specific requirements..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                <div class="form-text">Be detailed about what's available and any conditions for borrowing</div>
                            </div>
                        </div>
                        
                        <!-- Location & Contact -->
                        <div class="form-section">
                            <h3 class="section-title">Location & Contact</h3>
                            
                            <div class="form-group">
                                <label for="location" class="form-label">Location/Area *</label>
                                <input type="text" id="location" name="location" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" 
                                       placeholder="e.g., North London, Manchester, Birmingham" required>
                                <div class="form-text">City, borough, or general area where items can be collected</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_phone" class="form-label">Phone Number *</label>
                                        <input type="tel" id="contact_phone" name="contact_phone" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_email" class="form-label">Email Address</label>
                                        <input type="email" id="contact_email" name="contact_email" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="whatsapp_number" class="form-label">WhatsApp Number</label>
                                <input type="tel" id="whatsapp_number" name="whatsapp_number" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['whatsapp_number'] ?? '') ?>"
                                       placeholder="Include country code (e.g., 447123456789)">
                                <div class="form-text">Optional: Include country code for international numbers</div>
                            </div>
                        </div>
                        
                        <!-- Images -->
                        <div class="form-section">
                            <h3 class="section-title">Images</h3>
                            
                            <div class="form-group">
                                <label for="images" class="form-label">Upload Images</label>
                                <input type="file" id="images" name="images[]" class="form-control" 
                                       accept="image/*" multiple>
                                <div class="form-text">Upload up to 5 images (JPG, PNG, GIF, WebP). Maximum 5MB each.</div>
                            </div>
                            
                            <div id="image-preview" class="image-preview-grid"></div>
                        </div>
                        
                        <!-- Donation Settings -->
                        <div class="form-section">
                            <h3 class="section-title">Donation Settings</h3>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" id="donation_enabled" name="donation_enabled" class="form-check-input"
                                       <?= isset($_POST['donation_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="donation_enabled">
                                    Accept donations for this gemach
                                </label>
                                <div class="form-text">Allow community members to donate to support your gemach</div>
                            </div>
                            
                            <div id="donation-fields" class="donation-fields" style="display: none;">
                                <div class="form-group">
                                    <label for="donation_link" class="form-label">Donation Link</label>
                                    <input type="url" id="donation_link" name="donation_link" class="form-control" 
                                           value="<?= htmlspecialchars($_POST['donation_link'] ?? '') ?>"
                                           placeholder="https://your-donation-platform.com">
                                    <div class="form-text">Link to your preferred donation platform (PayPal, JustGiving, etc.)</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="form-section">
                            <h3 class="section-title">Additional Information</h3>
                            
                            <div class="form-group">
                                <label for="in_memory_of" class="form-label">In Memory Of</label>
                                <input type="text" id="in_memory_of" name="in_memory_of" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['in_memory_of'] ?? '') ?>"
                                       placeholder="e.g., In memory of Sarah Cohen">
                                <div class="form-text">Optional: Dedicate this gemach to someone's memory</div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn-jshuk-primary btn-lg">
                                <i class="fas fa-paper-plane"></i>
                                Submit Gemach
                            </button>
                            <a href="/gemachim.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i>
                                Back to Gemachim
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Information Box -->
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                    <ul>
                        <li>Your submission will be reviewed by our admin team</li>
                        <li>We'll verify the information and contact details</li>
                        <li>Once approved, your gemach will appear in the directory</li>
                        <li>You'll receive an email notification when it goes live</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const donationCheckbox = document.getElementById('donation_enabled');
    const donationFields = document.getElementById('donation-fields');
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('image-preview');
    
    // Toggle donation fields
    donationCheckbox.addEventListener('change', function() {
        donationFields.style.display = this.checked ? 'block' : 'none';
    });
    
    // Image preview
    imageInput.addEventListener('change', function() {
        imagePreview.innerHTML = '';
        
        if (this.files) {
            Array.from(this.files).forEach((file, index) => {
                if (index >= 5) return; // Limit to 5 images
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview-item';
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <span class="image-name">${file.name}</span>
                    `;
                    imagePreview.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        }
    });
    
    // Form validation
    const form = document.getElementById('add-gemach-form');
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
    
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.startsWith('44')) {
                    value = '+' + value;
                } else if (value.startsWith('0')) {
                    value = '+44' + value.substring(1);
                }
            }
            this.value = value;
        });
    });
});
</script>

<?php include 'includes/footer_main.php'; ?> 