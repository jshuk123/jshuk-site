<?php
/**
 * Discovery Hub Section
 * Step 3: Unified section combining Popular Categories and New This Week
 */

// Get categories data (same logic as categories.php)
$categories_with_counts = [];
if (isset($pdo) && $pdo) {
    try {
        $categories_stmt = $pdo->query("
            SELECT c.id, c.name, c.icon, c.description, COUNT(b.id) AS business_count
            FROM business_categories c
            LEFT JOIN businesses b ON b.category_id = c.id AND b.status = 'active'
            GROUP BY c.id, c.name, c.icon, c.description
            ORDER BY business_count DESC, c.name ASC
        ");
        $categories_with_counts = $categories_stmt->fetchAll();
    } catch (PDOException $e) {
        $categories_with_counts = [];
        if (!empty($categories)) {
            foreach ($categories as $cat) {
                $cat['business_count'] = 0;
                $cat['icon'] = $cat['icon'] ?? 'fa-circle-question';
                $cat['description'] = $cat['description'] ?? '';
                $categories_with_counts[] = $cat;
            }
        }
    }
} else {
    $categories_with_counts = [];
    if (!empty($categories)) {
        foreach ($categories as $cat) {
            $cat['business_count'] = 0;
            $cat['icon'] = $cat['icon'] ?? 'fa-circle-question';
            $cat['description'] = $cat['description'] ?? '';
            $categories_with_counts[] = $cat;
        }
    }
}

// Calculate thresholds for badges
$max_listings = 0;
if (!empty($categories_with_counts)) {
    $max_listings = max(array_column($categories_with_counts, 'business_count'));
}
?>

<section id="discovery-hub" class="discovery-section">
    <div class="container">
        <h2 class="section-title">Support Local, Find Hidden Gems</h2>
        
        <!-- POPULAR CATEGORIES CONTAINER -->
        <div class="popular-categories-container">
            <div class="categories-scroll-wrapper" tabindex="0" aria-label="Popular business categories">
                <div class="category-scroll d-flex gap-3 overflow-auto pb-2">
                    <?php if (!empty($categories_with_counts)): ?>
                        <?php foreach ($categories_with_counts as $cat): ?>
                            <?php 
                            $dimClass = '';
                            $badge = '';
                            if ($cat['business_count'] > 10 && $cat['business_count'] >= $max_listings * 0.8) {
                                $badge = '<span class="category-badge category-badge-popular">Popular</span>';
                            } elseif ($cat['business_count'] > 5) {
                                $badge = '<span class="category-badge category-badge-active">Active</span>';
                            } elseif ($cat['business_count'] == 0) {
                                $badge = '<span class="category-badge category-badge-empty">New</span>';
                            }
                            $desc = trim($cat['description'] ?? '');
                            $attr = '';
                            if ($desc !== '') {
                                $escaped = htmlspecialchars($desc);
                                $attr = ' data-tippy-content="' . $escaped . '"';
                            }
                            ?>
                            <a href="/category.php?category_id=<?= $cat['id'] ?>"
                               class="category-card text-center flex-shrink-0 p-3 rounded shadow-sm text-decoration-none card-hover-effect <?= $dimClass ?>"
                               style="min-width: 160px; max-width: 200px; background: #f8f9fa;"<?= $attr ?> data-category-description="<?= htmlspecialchars($desc) ?>">
                                <?php if ($badge): ?>
                                    <div class="category-badge-container">
                                        <?= $badge ?>
                                    </div>
                                <?php endif; ?>
                                <?php 
                                $icon = $cat['icon'] ?? 'fa-circle-question';
                                if (strpos($icon, 'fa-') === 0 && strpos($icon, 'fa-solid') === false && strpos($icon, 'fa-brands') === false && strpos($icon, 'fa-regular') === false) {
                                    $icon = 'fa-solid ' . $icon;
                                }
                                ?>
                                <div class="category-icon-circle mx-auto mb-2" style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;background:#f1f3f6;border-radius:50%;">
                                    <i class="<?= htmlspecialchars($icon) ?> fs-2" aria-hidden="true"></i>
                                </div>
                                <h6 class="category-name fw-bold mt-2 mb-1" 
                                    style="font-size:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </h6>
                                <?php if ($desc !== ''): ?>
                                    <div class="category-description" style="font-size:0.95rem;color:#666;margin-bottom:0.25rem;min-height:2.5em;">
                                        <?= htmlspecialchars($desc) ?>
                                    </div>
                                <?php endif; ?>
                                <p class="small text-muted mb-0">
                                    <?= $cat['business_count'] ?> Listing<?= $cat['business_count'] == 1 ? '' : 's' ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center w-100 py-4">
                            <p>Categories loading...</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Optional: Add Final CTA Card -->
                    <div class="category-card category-card-cta text-center flex-shrink-0 p-3 rounded shadow-sm text-decoration-none"
                         style="min-width: 160px; max-width: 200px; background: linear-gradient(135deg, #ffd000 0%, #ffc400 100%); border: 2px dashed #fff;"
                         onclick="suggestCategory()"
                         role="button"
                         tabindex="0"
                         aria-label="Suggest a new category">
                        <div class="category-icon-circle mx-auto mb-2" style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.2);border-radius:50%;">
                            <i class="fa-solid fa-plus fs-2" style="color: #fff;" aria-hidden="true"></i>
                        </div>
                        <h6 class="fw-bold mt-2 mb-1" style="font-size:1rem; color: #fff;">
                            Suggest a Category
                        </h6>
                        <p class="small mb-0" style="color: rgba(255,255,255,0.8);">
                            Help us grow
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- NEW THIS WEEK CONTAINER -->
        <div class="new-this-week-container">
            <div class="businesses-grid">
                <?php if (!empty($newBusinesses)): ?>
                    <?php foreach (array_slice($newBusinesses, 0, 6) as $biz): ?>
                        <div class="business-card-wrapper">
                            <div class="business-card new-business-card card-hover-effect">
                                <div class="business-logo">
                                    <?php
                                    if (isset($pdo)) {
                                        $stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
                                        $stmt->execute([$biz['id']]);
                                        $image = $stmt->fetchColumn();
                                        $img_src = $image ? $image : '/images/jshuk-logo.png';
                                    } else {
                                        $img_src = '/images/jshuk-logo.png';
                                    }
                                    ?>
                                    <?php if ($image ?? false): ?>
                                        <img src="<?= htmlspecialchars($img_src) ?>" 
                                             alt="<?= htmlspecialchars($biz['business_name']) ?> Logo" 
                                             loading="lazy"
                                             onerror="this.onerror=null;this.src='/images/jshuk-logo.png';">
                                    <?php else: ?>
                                        <div class="logo-placeholder"><?= strtoupper(substr($biz['business_name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                    <?php if (($biz['subscription_tier'] ?? '') === 'premium_plus'): ?>
                                        <span class="badge-elite">Elite</span>
                                    <?php endif; ?>
                                </div>
                                <div class="business-info">
                                    <h3 class="business-title">
                                        <a href="<?= BASE_PATH ?>business.php?id=<?= urlencode($biz['id']) ?>">
                                            <?= htmlspecialchars($biz['business_name']) ?>
                                        </a>
                                    </h3>
                                    <p class="business-meta">
                                        <i class="fas fa-tag"></i> 
                                        <?= htmlspecialchars($biz['category_name'] ?? 'Business') ?>
                                    </p>
                                    <p class="business-joined">🆕 Just Joined</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-businesses">No new businesses added recently. <a href="<?= BASE_PATH ?>auth/register.php">Be the first to list!</a></div>
                <?php endif; ?>
            </div>
            <div class="section-actions">
                <a href="<?= BASE_PATH ?>search.php?sort=newest" class="btn-section">Explore More New Listings</a>
            </div>
        </div>
    </div>
</section>

<style>
/* Discovery Hub Section Styles */
.discovery-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.discovery-section .section-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 3rem;
}

/* Popular Categories Container */
.popular-categories-container {
    margin-bottom: 4rem;
}

.categories-scroll-wrapper {
    padding: 0 1rem;
}

.category-scroll {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1rem;
    scrollbar-width: thin;
    scrollbar-color: #ccc transparent;
}

.category-scroll::-webkit-scrollbar {
    height: 6px;
}

.category-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.category-scroll::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.category-card {
    min-width: 160px;
    max-width: 200px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    text-decoration: none;
    color: inherit;
}

.category-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    color: white;
}

.category-badge-popular {
    background: #dc3545;
}

.category-badge-active {
    background: #28a745;
}

.category-badge-empty {
    background: #6c757d;
}

.category-icon-circle {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f3f6;
    border-radius: 50%;
    margin: 0 auto 0.5rem;
}

.category-name {
    font-size: 1rem;
    font-weight: 700;
    margin: 0.5rem 0 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.category-description {
    display: none;
    font-size: 0.95rem;
    color: #666;
    margin-bottom: 0.25rem;
    min-height: 2.5em;
    line-height: 1.4;
    word-break: break-word;
    transition: opacity 0.2s;
}

@media (min-width: 769px) {
    .category-card:hover .category-description {
        display: block;
        opacity: 1;
    }
}

/* New This Week Container */
.new-this-week-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.businesses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.business-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
}

.business-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.business-logo {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 1rem;
    position: relative;
}

.business-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.logo-placeholder {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #6c757d;
}

.badge-elite {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ffc107;
    color: #222;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 8px;
}

.business-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.business-title a {
    color: #2c3e50;
    text-decoration: none;
}

.business-title a:hover {
    color: #007bff;
}

.business-meta {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.business-joined {
    font-size: 0.85rem;
    color: #28a745;
    font-weight: 600;
    margin: 0;
}

.section-actions {
    text-align: center;
}

.btn-section {
    display: inline-block;
    background: #007bff;
    color: white;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.btn-section:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
}

.no-businesses {
    text-align: center;
    color: #6c757d;
    grid-column: 1 / -1;
}

.no-businesses a {
    color: #007bff;
    text-decoration: none;
}

.no-businesses a:hover {
    text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 768px) {
    .discovery-section {
        padding: 2rem 0;
    }
    
    .discovery-section .section-title {
        font-size: 2rem;
        margin-bottom: 2rem;
    }
    
    .popular-categories-container {
        margin-bottom: 3rem;
    }
    
    .new-this-week-container {
        padding: 1.5rem;
    }
    
    .businesses-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .category-scroll {
        gap: 0.75rem;
    }
    
    .category-card {
        min-width: 140px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category carousel functionality
    const carousels = document.querySelectorAll('.category-carousel');
    
    carousels.forEach(carousel => {
        const track = carousel.querySelector('.category-carousel-track');
        const prevBtn = carousel.querySelector('.carousel-control.prev');
        const nextBtn = carousel.querySelector('.carousel-control.next');
        const items = carousel.querySelectorAll('.category-carousel-item');
        
        let currentIndex = 0;
        const itemsPerView = window.innerWidth > 1200 ? 4 : 3;
        const maxIndex = Math.max(0, items.length - itemsPerView);
        
        function updateCarousel() {
            const translateX = -currentIndex * (100 / itemsPerView);
            track.style.transform = `translateX(${translateX}%)`;
            
            // Update button states
            prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
            nextBtn.style.opacity = currentIndex >= maxIndex ? '0.5' : '1';
        }
        
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            });
            
            nextBtn.addEventListener('click', () => {
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateCarousel();
                }
            });
        }
        
        // Initialize
        updateCarousel();
        
        // Handle window resize
        window.addEventListener('resize', () => {
            const newItemsPerView = window.innerWidth > 1200 ? 4 : 3;
            if (newItemsPerView !== itemsPerView) {
                location.reload(); // Simple solution for responsive changes
            }
        });
    });
});

function suggestCategory() {
    // Implement category suggestion functionality
    alert('Category suggestion feature coming soon!');
}
</script> 