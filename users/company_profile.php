<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/users/company_profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $company_name = trim($_POST['company_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $about_us = trim($_POST['about_us'] ?? '');
        $industry = trim($_POST['industry'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $company_size = $_POST['company_size'] ?? '1-10';
        $founded_year = $_POST['founded_year'] ?? null;
        $location = trim($_POST['location'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $social_linkedin = trim($_POST['social_linkedin'] ?? '');
        $social_twitter = trim($_POST['social_twitter'] ?? '');
        $social_facebook = trim($_POST['social_facebook'] ?? '');

        // Validation
        if (empty($company_name)) {
            throw new Exception('Company name is required');
        }

        // Generate slug from company name
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $company_name)));
        $slug = trim($slug, '-');

        // Check if company profile exists
        $stmt = $pdo->prepare("SELECT id FROM company_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $existing_profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_profile) {
            // Update existing profile
            $stmt = $pdo->prepare("
                UPDATE company_profiles SET 
                company_name = ?, slug = ?, description = ?, about_us = ?, industry = ?, 
                website = ?, company_size = ?, founded_year = ?, location = ?, 
                contact_email = ?, contact_phone = ?, social_linkedin = ?, 
                social_twitter = ?, social_facebook = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $company_name, $slug, $description, $about_us, $industry,
                $website, $company_size, $founded_year, $location,
                $contact_email, $contact_phone, $social_linkedin,
                $social_twitter, $social_facebook, $user_id
            ]);
        } else {
            // Create new profile
            $stmt = $pdo->prepare("
                INSERT INTO company_profiles (
                    user_id, company_name, slug, description, about_us, industry,
                    website, company_size, founded_year, location, contact_email,
                    contact_phone, social_linkedin, social_twitter, social_facebook
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $company_name, $slug, $description, $about_us, $industry,
                $website, $company_size, $founded_year, $location, $contact_email,
                $contact_phone, $social_linkedin, $social_twitter, $social_facebook
            ]);
        }

        $success_message = 'Company profile updated successfully!';

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch existing company profile
$company_profile = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $company_profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching company profile: " . $e->getMessage());
}

$pageTitle = "My Company Profile";
$page_css = "company_profile_edit.css";
include '../includes/header_main.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 2rem;">
                <div class="card-body text-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=0d6efd&color=fff&size=100&rounded=true" alt="User Avatar" class="rounded-circle mb-3">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($user_name) ?></h5>
                    <p class="card-text text-muted small">Employer</p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/users/dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="/users/company_profile.php" class="list-group-item list-group-item-action active">
                        <i class="fa-solid fa-building me-2"></i>Company Profile
                    </a>
                    <a href="/users/manage_jobs.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-briefcase me-2"></i>Manage Jobs
                    </a>
                    <a href="/users/applications.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-users me-2"></i>Applications
                    </a>
                    <hr class="my-1">
                    <a href="/users/edit_profile.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-user-edit me-2"></i>Edit Profile
                    </a>
                    <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fa-solid fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">My Company Profile</h1>
                    <p class="text-muted mb-0">
                        Manage your company's public profile and branding
                    </p>
                </div>
                <?php if ($company_profile): ?>
                    <a href="/company-profile.php?slug=<?= htmlspecialchars($company_profile['slug']) ?>" 
                       class="btn btn-outline-primary" target="_blank">
                        <i class="fa-solid fa-external-link-alt me-2"></i>View Public Profile
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Company Name *</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?= htmlspecialchars($company_profile['company_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <input type="text" class="form-control" id="industry" name="industry" 
                                       value="<?= htmlspecialchars($company_profile['industry'] ?? '') ?>" 
                                       placeholder="e.g., Technology, Healthcare, Finance">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website" 
                                       value="<?= htmlspecialchars($company_profile['website'] ?? '') ?>" 
                                       placeholder="https://www.yourcompany.com">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?= htmlspecialchars($company_profile['location'] ?? '') ?>" 
                                       placeholder="e.g., London, UK">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="company_size" class="form-label">Company Size</label>
                                <select class="form-select" id="company_size" name="company_size">
                                    <option value="1-10" <?= ($company_profile['company_size'] ?? '') === '1-10' ? 'selected' : '' ?>>1-10 employees</option>
                                    <option value="11-50" <?= ($company_profile['company_size'] ?? '') === '11-50' ? 'selected' : '' ?>>11-50 employees</option>
                                    <option value="51-200" <?= ($company_profile['company_size'] ?? '') === '51-200' ? 'selected' : '' ?>>51-200 employees</option>
                                    <option value="201-500" <?= ($company_profile['company_size'] ?? '') === '201-500' ? 'selected' : '' ?>>201-500 employees</option>
                                    <option value="501-1000" <?= ($company_profile['company_size'] ?? '') === '501-1000' ? 'selected' : '' ?>>501-1000 employees</option>
                                    <option value="1000+" <?= ($company_profile['company_size'] ?? '') === '1000+' ? 'selected' : '' ?>>1000+ employees</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="founded_year" class="form-label">Founded Year</label>
                                <input type="number" class="form-control" id="founded_year" name="founded_year" 
                                       value="<?= htmlspecialchars($company_profile['founded_year'] ?? '') ?>" 
                                       min="1800" max="<?= date('Y') ?>" placeholder="e.g., 2020">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                       value="<?= htmlspecialchars($company_profile['contact_email'] ?? '') ?>" 
                                       placeholder="contact@yourcompany.com">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                       value="<?= htmlspecialchars($company_profile['contact_phone'] ?? '') ?>" 
                                       placeholder="+44 20 1234 5678">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Company Overview</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Brief overview of your company..."><?= htmlspecialchars($company_profile['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="about_us" class="form-label">About Us</label>
                            <textarea class="form-control" id="about_us" name="about_us" rows="6" 
                                      placeholder="Detailed description of your company, mission, values, and culture..."><?= htmlspecialchars($company_profile['about_us'] ?? '') ?></textarea>
                        </div>

                        <!-- Social Media Links -->
                        <h4 class="mt-4 mb-3">Social Media</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="social_linkedin" class="form-label">LinkedIn</label>
                                <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" 
                                       value="<?= htmlspecialchars($company_profile['social_linkedin'] ?? '') ?>" 
                                       placeholder="https://linkedin.com/company/yourcompany">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="social_twitter" class="form-label">Twitter</label>
                                <input type="url" class="form-control" id="social_twitter" name="social_twitter" 
                                       value="<?= htmlspecialchars($company_profile['social_twitter'] ?? '') ?>" 
                                       placeholder="https://twitter.com/yourcompany">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="social_facebook" class="form-label">Facebook</label>
                                <input type="url" class="form-control" id="social_facebook" name="social_facebook" 
                                       value="<?= htmlspecialchars($company_profile['social_facebook'] ?? '') ?>" 
                                       placeholder="https://facebook.com/yourcompany">
                            </div>
                        </div>

                        <!-- Logo and Banner Upload -->
                        <h4 class="mt-4 mb-3">Company Branding</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="logo" class="form-label">Company Logo</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <small class="form-text text-muted">Recommended size: 200x200px, PNG or JPG</small>
                                <?php if ($company_profile && $company_profile['logo_path']): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($company_profile['logo_path']) ?>" 
                                             alt="Current Logo" class="img-thumbnail" style="max-width: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="banner" class="form-label">Banner Image</label>
                                <input type="file" class="form-control" id="banner" name="banner" accept="image/*">
                                <small class="form-text text-muted">Recommended size: 1200x400px, PNG or JPG</small>
                                <?php if ($company_profile && $company_profile['banner_path']): ?>
                                    <div class="mt-2">
                                        <img src="<?= htmlspecialchars($company_profile['banner_path']) ?>" 
                                             alt="Current Banner" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Company Profile
                                </button>
                            </div>
                            
                            <?php if ($company_profile): ?>
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        Last updated: <?= date('M j, Y g:i A', strtotime($company_profile['updated_at'])) ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Preview -->
            <?php if ($company_profile): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Profile Preview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="profile-preview">
                        <div class="preview-header">
                            <div class="preview-logo">
                                <?php if ($company_profile['logo_path']): ?>
                                    <img src="<?= htmlspecialchars($company_profile['logo_path']) ?>" alt="Company Logo">
                                <?php else: ?>
                                    <div class="preview-logo-placeholder">
                                        <i class="fas fa-building"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="preview-info">
                                <h3><?= htmlspecialchars($company_profile['company_name']) ?></h3>
                                <?php if ($company_profile['industry']): ?>
                                    <p class="text-muted"><?= htmlspecialchars($company_profile['industry']) ?></p>
                                <?php endif; ?>
                                <?php if ($company_profile['location']): ?>
                                    <p class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($company_profile['location']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($company_profile['description']): ?>
                        <div class="preview-description">
                            <p><?= htmlspecialchars(mb_strimwidth($company_profile['description'], 0, 200, '...')) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="preview-actions">
                            <a href="/company-profile.php?slug=<?= htmlspecialchars($company_profile['slug']) ?>" 
                               class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fas fa-external-link-alt me-1"></i>View Full Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Company Profile Edit Styles */
.profile-preview {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    background: #f8f9fa;
}

.preview-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.preview-logo {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-logo-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #ffd700 0%, #ffcc00 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a3353;
    font-size: 1.5rem;
}

.preview-info h3 {
    margin: 0;
    color: #1a3353;
    font-size: 1.25rem;
}

.preview-info p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.preview-description {
    margin-bottom: 1rem;
}

.preview-description p {
    color: #495057;
    line-height: 1.5;
}

.preview-actions {
    text-align: center;
}

/* Form enhancements */
.form-label {
    font-weight: 600;
    color: #1a3353;
}

.form-control:focus,
.form-select:focus {
    border-color: #ffd700;
    box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
}

.btn-primary {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    border: none;
    color: #1a3353;
    font-weight: 600;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .preview-header {
        flex-direction: column;
        text-align: center;
    }
    
    .preview-info h3 {
        font-size: 1.1rem;
    }
}
</style>

<?php include '../includes/footer_main.php'; ?> 