<?php
require_once 'config/config.php';
require_once 'includes/ImageManager.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Not logged in. Please <a href="/auth/login.php">login first</a>');
}

// Get business ID from query string
$business_id = isset($_GET['business_id']) ? (int)$_GET['business_id'] : null;
if (!$business_id) {
    die('Business ID is required');
}

// Debug information
echo "Debug Info:<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Business ID: " . $business_id . "<br>";

// Verify business ownership
try {
    // First check if the business exists and belongs to the user
    $stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ? AND user_id = ?");
    $stmt->execute([$business_id, $_SESSION['user_id']]);
    $business = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$business) {
        // List available businesses for this user
        $stmt = $pdo->prepare("SELECT * FROM businesses WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Available Businesses:</h3>";
        echo "<ul>";
        foreach ($businesses as $b) {
            $businessName = isset($b['business_name']) ? $b['business_name'] : 
                          (isset($b['name']) ? $b['name'] : 
                          'Business ' . $b['id']);
            echo "<li><a href='test_image_manager.php?business_id=" . $b['id'] . "'>" . 
                 htmlspecialchars($businessName) . " (ID: " . $b['id'] . ")</a></li>";
        }
        echo "</ul>";
        
        die('Not authorized - This business does not belong to you. Please select one of your businesses above.');
    }
    
    $businessName = isset($business['business_name']) ? $business['business_name'] : 
                   (isset($business['name']) ? $business['name'] : 
                   'Business ' . $business['id']);
    echo "Business Name: " . htmlspecialchars($businessName) . "<br><hr>";
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Manager Test</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/pages/image-manager.css" rel="stylesheet">

    <style>
    /* Fallback styles in case the CSS file doesn't load */
    .main-image-section {
        margin-bottom: 2rem;
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 1rem;
        background-color: #f8f9fa;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 8px;
        min-height: 200px;
    }
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Image Manager Test</h1>

        <!-- Main Image Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Main Image</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="main_image_input" class="form-label">Upload Main Image</label>
                    <input type="file" class="form-control" id="main_image_input" accept="image/*">
                </div>
                <div id="main_image_container" class="main-image-section">
                    <div class="empty-state">
                        <i class="fas fa-image"></i>
                        <p>No main image uploaded</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gallery Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Gallery Images</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="gallery_image_input" class="form-label">Upload Gallery Images</label>
                    <input type="file" class="form-control" id="gallery_image_input" accept="image/*" multiple>
                </div>
                <div id="gallery_container" class="gallery-grid">
                    <div class="empty-state">
                        <i class="fas fa-images"></i>
                        <p>No gallery images uploaded</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Required JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="js/image-manager.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize image manager
            const imageManager = new ImageManager({
                businessId: <?php echo $business_id; ?>,
                apiEndpoint: 'users/image_manager.php',
                mainImageContainer: document.getElementById('main_image_container'),
                galleryContainer: document.getElementById('gallery_container'),
                mainImageInput: document.getElementById('main_image_input'),
                galleryImageInput: document.getElementById('gallery_image_input'),
                onUpdate: function(type, data) {
                    console.log('Image update:', type, data);
                }
            });
        });
    </script>
</body>
</html> 