<?php
/**
 * Business Gallery Component
 * Displays business images in a grid with lightbox functionality
 */

// Get business data from the parent scope
$business_id = $business['id'] ?? null;
$business_name = $business['name'] ?? 'Business Name';

// Fetch gallery images from database
$gallery_images = [];
$main_image = '';

try {
    // Get main business image
    $main_query = "SELECT main_image FROM businesses WHERE id = ?";
    $main_stmt = $pdo->prepare($main_query);
    $main_stmt->execute([$business_id]);
    $main_result = $main_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($main_result && !empty($main_result['main_image'])) {
        $main_image = '/uploads/businesses/' . $business_id . '/images/' . $main_result['main_image'];
    }
    
    // Get gallery images
    $gallery_query = "SELECT * FROM business_gallery WHERE business_id = ? ORDER BY sort_order ASC, created_at DESC";
    $gallery_stmt = $pdo->prepare($gallery_query);
    $gallery_stmt->execute([$business_id]);
    $gallery_images = $gallery_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Handle error silently - gallery will show as empty
    error_log("Error fetching gallery: " . $e->getMessage());
}

// If no gallery images found, check for images in the uploads directory
if (empty($gallery_images)) {
    $gallery_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/businesses/' . $business_id . '/gallery/';
    if (is_dir($gallery_path)) {
        $files = scandir($gallery_path);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $gallery_images[] = [
                    'id' => count($gallery_images) + 1,
                    'filename' => $file,
                    'title' => pathinfo($file, PATHINFO_FILENAME),
                    'description' => '',
                    'image_path' => '/uploads/businesses/' . $business_id . '/gallery/' . $file
                ];
            }
        }
    }
}

// Limit to 12 images for performance
$gallery_images = array_slice($gallery_images, 0, 12);
?>

<!-- Business Gallery Section -->
<section class="business-gallery py-5" id="gallery">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <i class="fas fa-images text-primary me-2"></i>
                Photo Gallery
            </h2>
            <p class="section-subtitle text-muted">
                Take a look at our work and facilities
            </p>
            <div class="section-divider mx-auto"></div>
        </div>

        <?php if (!empty($gallery_images) || !empty($main_image)): ?>
        <div class="gallery-container">
            <!-- Main Image (if exists) -->
            <?php if (!empty($main_image)): ?>
            <div class="gallery-main-image mb-4">
                <div class="main-image-container">
                    <img src="<?php echo htmlspecialchars($main_image); ?>" 
                         alt="<?php echo htmlspecialchars($business_name); ?>" 
                         class="main-image"
                         onclick="openLightbox('<?php echo htmlspecialchars($main_image); ?>', '<?php echo htmlspecialchars($business_name); ?>')">
                    <div class="main-image-overlay">
                        <div class="overlay-content">
                            <i class="fas fa-expand-alt"></i>
                            <span>Click to enlarge</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gallery Grid -->
            <?php if (!empty($gallery_images)): ?>
            <div class="gallery-grid">
                <?php foreach ($gallery_images as $index => $image): ?>
                <div class="gallery-item" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="gallery-image-container">
                        <img src="<?php echo htmlspecialchars($image['image_path'] ?? $image['filename']); ?>" 
                             alt="<?php echo htmlspecialchars($image['title'] ?? 'Gallery Image'); ?>"
                             class="gallery-image"
                             loading="lazy"
                             onclick="openLightbox('<?php echo htmlspecialchars($image['image_path'] ?? $image['filename']); ?>', '<?php echo htmlspecialchars($image['title'] ?? 'Gallery Image'); ?>')">
                        <div class="gallery-overlay">
                            <div class="overlay-content">
                                <i class="fas fa-expand-alt"></i>
                                <?php if (!empty($image['title'])): ?>
                                <span class="image-title"><?php echo htmlspecialchars($image['title']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- View All Button -->
            <?php if (count($gallery_images) >= 12): ?>
            <div class="view-all-gallery text-center mt-4">
                <button class="btn btn-outline-primary btn-lg" onclick="viewAllImages()">
                    <i class="fas fa-images me-2"></i>
                    View All Images
                </button>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- No Images State -->
        <div class="no-images-state text-center py-5">
            <div class="no-images-icon mb-3">
                <i class="fas fa-camera text-muted" style="font-size: 3rem;"></i>
            </div>
            <h5 class="no-images-title">No Photos Available</h5>
            <p class="no-images-text text-muted">
                Photos will be added soon. Check back later!
            </p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Lightbox Modal -->
<div class="lightbox-modal" id="lightboxModal">
    <div class="lightbox-overlay" onclick="closeLightbox()"></div>
    <div class="lightbox-content">
        <div class="lightbox-header">
            <h5 class="lightbox-title" id="lightboxTitle"></h5>
            <button class="lightbox-close" onclick="closeLightbox()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lightbox-body">
            <img id="lightboxImage" src="" alt="" class="lightbox-img">
        </div>
        <div class="lightbox-footer">
            <button class="lightbox-nav prev" onclick="previousImage()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="lightbox-counter" id="lightboxCounter"></span>
            <button class="lightbox-nav next" onclick="nextImage()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
// Lightbox functionality
let currentImageIndex = 0;
let lightboxImages = [];

// Initialize lightbox images array
document.addEventListener('DOMContentLoaded', function() {
    const galleryImages = document.querySelectorAll('.gallery-image, .main-image');
    lightboxImages = Array.from(galleryImages).map(img => ({
        src: img.src,
        alt: img.alt
    }));
});

function openLightbox(imageSrc, imageTitle) {
    const modal = document.getElementById('lightboxModal');
    const image = document.getElementById('lightboxImage');
    const title = document.getElementById('lightboxTitle');
    const counter = document.getElementById('lightboxCounter');
    
    // Find image index
    currentImageIndex = lightboxImages.findIndex(img => img.src === imageSrc);
    if (currentImageIndex === -1) currentImageIndex = 0;
    
    // Update lightbox content
    image.src = imageSrc;
    image.alt = imageTitle;
    title.textContent = imageTitle;
    updateLightboxCounter();
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Add keyboard event listeners
    document.addEventListener('keydown', handleLightboxKeys);
}

function closeLightbox() {
    const modal = document.getElementById('lightboxModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    document.removeEventListener('keydown', handleLightboxKeys);
}

function previousImage() {
    currentImageIndex = (currentImageIndex - 1 + lightboxImages.length) % lightboxImages.length;
    updateLightboxImage();
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % lightboxImages.length;
    updateLightboxImage();
}

function updateLightboxImage() {
    const image = document.getElementById('lightboxImage');
    const title = document.getElementById('lightboxTitle');
    
    image.src = lightboxImages[currentImageIndex].src;
    image.alt = lightboxImages[currentImageIndex].alt;
    title.textContent = lightboxImages[currentImageIndex].alt;
    updateLightboxCounter();
}

function updateLightboxCounter() {
    const counter = document.getElementById('lightboxCounter');
    counter.textContent = `${currentImageIndex + 1} / ${lightboxImages.length}`;
}

function handleLightboxKeys(e) {
    switch(e.key) {
        case 'Escape':
            closeLightbox();
            break;
        case 'ArrowLeft':
            previousImage();
            break;
        case 'ArrowRight':
            nextImage();
            break;
    }
}

function viewAllImages() {
    // TODO: Implement view all images functionality
    alert('View all images functionality coming soon!');
}

// Close lightbox when clicking outside
document.getElementById('lightboxModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});
</script> 