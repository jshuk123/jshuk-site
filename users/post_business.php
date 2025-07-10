<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- SESSION AND AUTHENTICATION CHECK ---
// This is the most critical part. Ensure session is started and user is logged in.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// If user_id is not in the session, they MUST be redirected. No exceptions.
if (!isset($_SESSION['user_id'])) {
    // Set a message to inform the user why they were redirected.
    $_SESSION['error_message'] = 'You must be logged in to list a business.';
    // Add a redirect parameter to bring them back here after login.
    header('Location: /auth/login.php?redirect=/users/post_business.php');
    exit();
}

// Securely assign the user ID to a variable for use in the script.
$user_id = $_SESSION['user_id'];

// --- VERIFY USER EXISTS IN DATABASE ---
// This is the definitive fix for the foreign key constraint error.
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if ($stmt->fetch() === false) {
    // The user ID from the session does not exist in the database.
    // This is a stale session. Destroy it and force a new login.
    session_destroy();
    $_SESSION['error_message'] = 'Your session was invalid. Please log in again.';
    header('Location: /auth/login.php');
    exit();
}
// --- END VERIFICATION ---

require_once '../includes/upload_helper.php';

// Get categories for dropdown
$stmt = $pdo->query("SELECT id, name, parent_id FROM business_categories ORDER BY parent_id IS NOT NULL, parent_id, name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group categories by parent
$parents = [];
$children = [];
foreach ($categories as $cat) {
    if (is_null($cat['parent_id'])) {
        $parents[$cat['id']] = $cat['name'];
    } else {
        $children[$cat['parent_id']][] = $cat;
    }
}

// Simplified function to generate a unique slug
function generateUniqueSlug($pdo, $business_name) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $business_name)));
    $original_slug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM businesses WHERE slug = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $original_slug . '-' . $counter;
        $counter++;
    }
}

// Add robust debugging
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('FATAL ERROR: ' . print_r($error, true));
        echo '<div style="color:red;font-weight:bold;">A fatal error occurred: ' . htmlspecialchars($error['message']) . ' in ' . htmlspecialchars($error['file']) . ' on line ' . $error['line'] . '</div>';
    }
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_business'])) {
    // Log POST and FILES data for debugging
    error_log('POST DATA: ' . print_r($_POST, true));
    error_log('FILES DATA: ' . print_r($_FILES, true));
    // The $user_id is already validated and set from the top of the script.
    // We can trust it here because the script would have exited if it wasn't valid.
    
    try {
        $pdo->beginTransaction();

        // --- Validation ---
        $errors = [];
        if (empty($_POST['business_name'])) $errors[] = "Business name is required.";
        if (empty($_POST['category_id'])) $errors[] = "Category is required.";
        if (empty($_POST['description'])) $errors[] = "Description is required.";
        if (empty($_FILES['main_image']['name'])) $errors[] = "A main image is required.";
        
        if (!empty($errors)) {
            throw new Exception(implode("<br>", $errors));
        }

        // --- Prepare Data ---
        $slug = generateUniqueSlug($pdo, $_POST['business_name']);
        
        $contact_info = json_encode([
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
        ]);

        // --- Insert Business ---
        $insert_stmt = $pdo->prepare("
            INSERT INTO businesses (
                user_id, business_name, category_id, slug, description, address, 
                website, contact_info, created_at, updated_at
            ) VALUES (:user_id, :business_name, :category_id, :slug, :description, :address, :website, :contact_info, NOW(), NOW())
        ");
        $insert_stmt->execute([
            ':user_id' => $user_id, // Use the validated user_id from the top of the script
            ':business_name' => $_POST['business_name'],
            ':category_id' => $_POST['category_id'],
            ':slug' => $slug,
            ':description' => $_POST['description'],
            ':address' => $_POST['address'] ?? null,
            ':website' => $_POST['website'] ?? null,
            ':contact_info' => $contact_info
        ]);
        $business_id = $pdo->lastInsertId();

        // --- Handle Image Uploads ---
        // Main Image
        $main_image_path = handle_image_upload($_FILES['main_image'], $business_id, "main");
        if ($main_image_path) {
            $pdo->prepare("INSERT INTO business_images (business_id, file_path, sort_order) VALUES (?, ?, 0)")->execute([$business_id, $main_image_path]);
        }

        // Gallery Images
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $gallery_files = $_FILES['gallery_images'];
            $sort_order = 1;
            for ($i = 0; $i < count($gallery_files['name']); $i++) {
                $file = [
                    'name' => $gallery_files['name'][$i],
                    'type' => $gallery_files['type'][$i],
                    'tmp_name' => $gallery_files['tmp_name'][$i],
                    'error' => $gallery_files['error'][$i],
                    'size' => $gallery_files['size'][$i],
                ];
                $gallery_path = handle_image_upload($file, $business_id, "gallery-{$sort_order}");
                if ($gallery_path) {
                    $pdo->prepare("INSERT INTO business_images (business_id, file_path, sort_order) VALUES (?, ?, ?)")->execute([$business_id, $gallery_path, $sort_order++]);
                }
            }
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "Business listed successfully! It will be reviewed by an admin shortly.";
        header("Location: /users/dashboard.php?tab=businesses");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $debug_user_id = $_SESSION['user_id'] ?? '[USER ID NOT FOUND IN SESSION]';
        error_log('Business submission error: ' . $e->getMessage());
        echo '<div style="color:red;font-weight:bold;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $_SESSION['error_message'] = "Database Error: " . $e->getMessage() . " --- DEBUG INFO: Attempted to use User ID: " . htmlspecialchars($debug_user_id);
    }
}

$pageTitle = "List Your Business | JShuk";
$page_css = "post_business.css";
include '../includes/header_main.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Main Content Column -->
        <div class="col-lg-8">
            <h1 class="mb-4">List Your Business</h1>
    
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" id="business-form">
                <!-- Business Details Card -->
                <div class="card mb-4" id="business-details-card">
                    <div class="card-header">
                        <h5 class="mb-0">Business Details</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted">Provide the core details of your business. Fields marked with an asterisk (*) are required.</p>
                        <div class="mb-3">
                            <label for="business_name" class="form-label">Business Name*</label>
                            <input type="text" class="form-control" id="business_name" name="business_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category*</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="" disabled selected>Select a category...</option>
                                <?php foreach ($parents as $parent_id => $parent_name): ?>
                                    <?php if (!empty($children[$parent_id])): ?>
                                        <optgroup label="<?= htmlspecialchars($parent_name) ?>">
                                            <?php foreach ($children[$parent_id] as $child): ?>
                                                <option value="<?= $child['id'] ?>"><?= htmlspecialchars($child['name']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description*</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required placeholder="Tell us about your business..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Card -->
                <div class="card" id="contact-info-card">
                     <div class="card-header">
                        <h5 class="mb-0">Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted">Provide contact details so customers can reach you.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="e.g., contact@mybusiness.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g., (555) 123-4567">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" placeholder="e.g., 123 Main St, Anytown, USA">
                        </div>
                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" placeholder="e.g., https://www.mybusiness.com">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar Column -->
        <div class="col-lg-4">
            <!-- Image Upload Card -->
            <div class="card sticky-top" style="top: 2rem;" id="image-upload-card">
                <div class="card-header">
                    <h5 class="mb-0">Business Images</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small">A great main image is crucial for attracting customers.</p>
                    <div class="mb-3">
                        <label for="main_image" class="form-label">Main Image*</label>
                        <input type="file" class="form-control" id="main_image" name="main_image" form="business-form" accept="image/*" required>
                        <div class="form-text">This will be your business's primary photo.</div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="gallery_images" class="form-label">Gallery Images</label>
                        <input type="file" class="form-control" id="gallery_images" name="gallery_images[]" form="business-form" accept="image/*" multiple>
                        <div class="form-text">Add more photos to showcase your business (optional).</div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" name="submit_business" class="btn btn-primary w-100" form="business-form">
                        <i class="fa-solid fa-paper-plane me-2"></i>Submit Business for Review
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_main.php'; ?> 