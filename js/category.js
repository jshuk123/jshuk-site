/**
 * Category Page JavaScript
 * Handles interactions for the category page including carousel, filters, and animations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Swiper for featured businesses carousel
    initializeSwiper();
    
    // Initialize location selector
    initializeLocationSelector();
    
    // Initialize filter form
    initializeFilterForm();
    
    // Initialize smooth scrolling
    initializeSmoothScrolling();
    
    // Initialize loading states
    initializeLoadingStates();
    
    // Initialize animations
    initializeAnimations();
    
    // Initialize geolocation (if available)
    initializeGeolocation();
});

/**
 * Initialize Swiper carousel for featured businesses
 */
function initializeSwiper() {
    const swiperContainer = document.querySelector('.swiper-container');
    if (swiperContainer) {
        new Swiper('.swiper-container', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: false,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                    spaceBetween: 20,
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 25,
                },
                1024: {
                    slidesPerView: 4,
                    spaceBetween: 30,
                },
                1200: {
                    slidesPerView: 5,
                    spaceBetween: 30,
                }
            },
            on: {
                init: function() {
                    // Add animation to slides when carousel initializes
                    const slides = this.slides;
                    slides.forEach((slide, index) => {
                        slide.style.animationDelay = `${index * 0.1}s`;
                        slide.classList.add('slide-animate');
                    });
                }
            }
        });
    }
}

/**
 * Initialize location selector functionality
 */
function initializeLocationSelector() {
    const locationSelect = document.getElementById('locationSelect');
    if (locationSelect) {
        // Auto-submit form when location changes
        locationSelect.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                // Add loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                    submitBtn.disabled = true;
                }
                
                // Submit form
                form.submit();
            }
        });
        
        // Add keyboard navigation
        locationSelect.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.dispatchEvent(new Event('change'));
            }
        });
    }
}

/**
 * Initialize filter form functionality
 */
function initializeFilterForm() {
    const filterForm = document.getElementById('categoryFilters');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            // Add loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
                submitBtn.disabled = true;
            }
            
            // Add a small delay to show loading state
            setTimeout(() => {
                // Form will submit normally
            }, 100);
        });
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Clear filters on escape
                const clearBtn = filterForm.querySelector('a[href*="category.php"]');
                if (clearBtn) {
                    clearBtn.click();
                }
            }
        });
    }
}

/**
 * Initialize smooth scrolling for anchor links
 */
function initializeSmoothScrolling() {
    // Smooth scroll to sections when clicking anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Initialize loading states for interactive elements
 */
function initializeLoadingStates() {
    // Add loading states to business cards
    const businessCards = document.querySelectorAll('.business-card, .featured-business-card');
    
    businessCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't add loading if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.closest('a') || 
                e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                return;
            }
            
            // Add loading state
            this.classList.add('loading');
            
            // Navigate to business page
            const businessLink = this.querySelector('a[href*="business.php"]');
            if (businessLink) {
                window.location.href = businessLink.href;
            }
        });
    });
    
    // Add loading states to form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.classList.add('loading');
            }
        });
    });
}

/**
 * Initialize scroll-triggered animations
 */
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.business-card, .testimonial-card, .featured-story-card').forEach(el => {
        observer.observe(el);
    });
    
    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        .business-card,
        .testimonial-card,
        .featured-story-card {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .business-card.animate-in,
        .testimonial-card.animate-in,
        .featured-story-card.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .slide-animate {
            animation: slideInUp 0.6s ease forwards;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Initialize geolocation detection
 */
function initializeGeolocation() {
    if (navigator.geolocation && !document.querySelector('#locationSelect').value) {
        // Show a subtle notification that we can detect location
        const locationNotice = document.createElement('div');
        locationNotice.className = 'location-notice';
        locationNotice.innerHTML = `
            <i class="fas fa-map-marker-alt"></i>
            <span>We can help you find businesses near you</span>
            <button class="btn btn-sm btn-outline-light ms-2" onclick="detectLocation()">Enable</button>
        `;
        
        // Add styles for the notice
        const style = document.createElement('style');
        style.textContent = `
            .location-notice {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(29, 42, 64, 0.9);
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                font-size: 0.9rem;
                z-index: 1000;
                backdrop-filter: blur(10px);
                animation: slideInRight 0.5s ease;
            }
            
            .location-notice button {
                font-size: 0.8rem;
                padding: 4px 8px;
            }
        `;
        document.head.appendChild(style);
        
        // Show notice after a delay
        setTimeout(() => {
            document.body.appendChild(locationNotice);
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                if (locationNotice.parentNode) {
                    locationNotice.remove();
                }
            }, 10000);
        }, 3000);
    }
}

/**
 * Detect user location using geolocation API
 */
function detectLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Here you would typically reverse geocode the coordinates
                // For now, we'll just show a success message
                showNotification('Location detected! Updating results...', 'success');
                
                // You could make an AJAX call here to update the page with nearby businesses
                // For now, we'll just redirect with a location parameter
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('location', 'Manchester'); // Default fallback
                window.location.href = currentUrl.toString();
            },
            function(error) {
                showNotification('Unable to detect location. Please select manually.', 'warning');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add styles for notification
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1001;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            }
            
            .notification-success {
                border-left: 4px solid #28a745;
            }
            
            .notification-warning {
                border-left: 4px solid #ffc107;
            }
            
            .notification-info {
                border-left: 4px solid #17a2b8;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 16px;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: #6c757d;
                cursor: pointer;
                padding: 0;
                margin-left: auto;
            }
            
            .notification-close:hover {
                color: #343a40;
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Handle business card interactions
 */
function handleBusinessCardInteraction(card) {
    // Add hover effects
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
    
    // Add click tracking (if analytics is available)
    card.addEventListener('click', function(e) {
        if (e.target.tagName !== 'A' && !e.target.closest('a')) {
            const businessName = this.querySelector('.business-name')?.textContent;
            if (businessName && typeof gtag !== 'undefined') {
                gtag('event', 'business_card_click', {
                    'business_name': businessName,
                    'category': document.querySelector('.hero-title')?.textContent
                });
            }
        }
    });
}

/**
 * Initialize all business card interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.business-card, .featured-business-card').forEach(card => {
        handleBusinessCardInteraction(card);
    });
});

/**
 * Handle window resize for responsive adjustments
 */
window.addEventListener('resize', function() {
    // Reinitialize Swiper on resize if needed
    const swiper = document.querySelector('.swiper-container')?.swiper;
    if (swiper) {
        swiper.update();
    }
});

/**
 * Add keyboard navigation support
 */
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const locationSelect = document.getElementById('locationSelect');
        if (locationSelect) {
            locationSelect.focus();
        }
    }
    
    // Escape to clear filters
    if (e.key === 'Escape') {
        const clearBtn = document.querySelector('a[href*="category.php"]');
        if (clearBtn) {
            clearBtn.click();
        }
    }
});

// Export functions for global access
window.categoryPage = {
    detectLocation,
    showNotification,
    initializeSwiper,
    initializeLocationSelector
}; 