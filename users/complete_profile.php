<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';


// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/  login.php');
    exit();
}

$error = '';
$success = '';
$user = null;

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        $username = $_POST['username'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $business_name = $_POST['business_name'] ?? '';
        
        // Validate username uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            throw new Exception("Username already taken. Please choose another.");
        }
        
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['profile_image']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.");
            }
            
            // Create user directory if it doesn't exist
            $upload_dir = 'uploads/users/' . $_SESSION['user_id'] . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'profile-' . uniqid() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filepath)) {
                // Update user profile with new image
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, 
                        phone = ?, 
                        first_name = ?, 
                        last_name = ?,
                        profile_image = ?,
                        business_name = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $username, 
                    $phone, 
                    $first_name, 
                    $last_name,
                    $filepath,
                    $business_name,
                    $_SESSION['user_id']
                ]);
            } else {
                throw new Exception("Error uploading file.");
            }
        } else {
            // Update without changing profile image
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, 
                    phone = ?, 
                    first_name = ?, 
                    last_name = ?,
                    business_name = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $username, 
                $phone, 
                $first_name, 
                $last_name,
                $business_name,
                $_SESSION['user_id']
            ]);
        }
        
        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Redirect if all required fields are filled
        if (!empty($username) && !empty($first_name) && !empty($last_name)) {
            header('Location: profile.php');
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$pageTitle = "Complete Your Profile";
$page_css = "complete_profile.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Complete Your Profile</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="complete_profile.php" enctype="multipart/form-data">
                        <!-- Profile Image Upload -->
                        <div class="text-center mb-4">
                            <div class="profile-image-container">
                                <img src="<?php echo htmlspecialchars($user['profile_image'] ?? 'images/default-avatar.jpg'); ?>" 
                                     class="profile-image mb-3" 
                                     id="profileImagePreview" 
                                     alt="Profile Image">
                                <div class="upload-overlay">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </div>
                            <input type="file" class="form-control d-none" id="profile_image" name="profile_image" accept="image/*">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('profile_image').click()">
                                <i class="fas fa-upload me-2"></i>Upload Profile Picture
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username*</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="business_name" class="form-label">Business Name*</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" 
                                       value="<?php echo htmlspecialchars($user['business_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name*</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name*</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/complete_profile.js"></script>
<?php include '../includes/footer_main.php'; ?> 