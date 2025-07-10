<?php
// Remove any whitespace before this opening PHP tag
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ 4. Reorder Categories by Listing Count - Enhanced query with description
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
        // Fallback to basic categories if query fails
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
    // Use basic categories data
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

// ✅ 5. Add Dynamic Badges - Calculate thresholds for badges
$max_listings = 0;
if (!empty($categories_with_counts)) {
    $max_listings = max(array_column($categories_with_counts, 'business_count'));
}
?>

<section class="popular-categories-section py-5 bg-white border-top border-bottom">
  <div class="container">
    <h2 class="section-title text-center mb-4 fw-semibold">Popular Categories</h2>
  </div>
  <div class="categories-scroll-wrapper px-3" tabindex="0" aria-label="Popular business categories">
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
             class="category-card text-center flex-shrink-0 p-3 rounded shadow-sm text-decoration-none <?= $dimClass ?>"
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
      
      <!-- ✅ 10. Optional: Add Final CTA Card -->
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
</section>

<style>
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
  
  // ✅ 8. Responsive Improvements - Mobile tooltip handling
  const categoryCards = document.querySelectorAll('.category-card:not(.category-card-cta)');
  
  categoryCards.forEach(card => {
    const description = card.getAttribute('data-category-description') || card.getAttribute('data-tippy-content');
    
    if (description && description.trim()) {
      // Desktop: Use title attribute for tooltips
      if (window.innerWidth > 768) {
        card.setAttribute('title', description);
      } else {
        // Mobile: Add click handler for description
        card.addEventListener('click', function(e) {
          // Only show description if it's different from the name
          const categoryName = card.querySelector('.category-name').textContent.trim();
          if (description !== categoryName) {
            // Show description in a mobile-friendly way
            if (window.innerWidth <= 768) {
              e.preventDefault();
              showMobileTooltip(description, card);
            }
          }
        });
      }
    } else {
      console.log('No description for category:', card.querySelector('.category-name')?.textContent);
    }
  });
  
  // ✅ 9. Enhance Hover Effects - Only apply to non-dimmed categories
  const nonDimmedCards = document.querySelectorAll('.category-card:not(.dimmed-category):not(.category-card-cta)');
  nonDimmedCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-3px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0) scale(1)';
    });
  });
});

// ✅ 8. Mobile tooltip function
function showMobileTooltip(description, element) {
  // Create a simple mobile-friendly tooltip
  const tooltip = document.createElement('div');
  tooltip.className = 'mobile-tooltip';
  tooltip.innerHTML = `
    <div class="mobile-tooltip-content">
      <p>${description}</p>
      <button onclick="this.parentElement.parentElement.remove()" class="mobile-tooltip-close">✕</button>
    </div>
  `;
  
  // Position tooltip near the element
  const rect = element.getBoundingClientRect();
  tooltip.style.position = 'fixed';
  tooltip.style.top = `${rect.bottom + 10}px`;
  tooltip.style.left = `${rect.left}px`;
  tooltip.style.zIndex = '9999';
  
  document.body.appendChild(tooltip);
  
  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (tooltip.parentElement) {
      tooltip.remove();
    }
  }, 5000);
}

// ✅ 10. Suggest category function
function suggestCategory() {
  const email = 'admin@jshuk.com'; // Replace with actual contact email
  const subject = 'Category Suggestion for JShuk';
  const body = 'Hi JShuk team,\n\nI would like to suggest a new category:\n\nCategory Name:\nDescription:\nReason for adding:\n\nThank you!';
  
  const mailtoLink = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  
  // Try to open email client, fallback to alert
  try {
    window.open(mailtoLink);
  } catch (e) {
    alert('Please email us at ' + email + ' to suggest a new category!');
  }
}
</script>
