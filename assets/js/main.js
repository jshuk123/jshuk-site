/**
 * JShuk - Main JavaScript File
 * Handles core functionality for the homepage and general site interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('JShuk main.js loaded');
    
    // Initialize all components
    initializeMobileMenu();
    initializeFeaturedSlider();
    initializeSearchBar();
    initializeTooltips();
    initializeAccordions();
    initializeScrollEffects();
    
    // Handle window resize
    window.addEventListener('resize', debounce(function() {
        updateResponsiveComponents();
    }, 250));
});

/**
 * Mobile Menu Functionality
 */
function initializeMobileMenu() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileNavMenu');
    const mobileNavClose = document.getElementById('mobileNavClose');
    
    if (!menuToggle || !mobileMenu) {
        console.warn('Mobile menu elements not found');
        return;
    }
    
    // Toggle menu
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openMobileMenu();
    });
    
    // Close menu
    if (mobileNavClose) {
        mobileNavClose.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileMenu();
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (mobileMenu.classList.contains('active')) {
            if (!mobileMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                closeMobileMenu();
            }
        }
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });
    
    // Touch/swipe to close functionality
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    
    mobileMenu.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        isDragging = true;
    }, { passive: true });
    
    mobileMenu.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
        const diff = startX - currentX;
        
        if (Math.abs(diff) > 50) {
            closeMobileMenu();
            isDragging = false;
        }
    }, { passive: false });
    
    mobileMenu.addEventListener('touchend', function() {
        isDragging = false;
    }, { passive: true });
}

/**
 * Open mobile menu
 */
function openMobileMenu() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileNavMenu');
    
    if (!menuToggle || !mobileMenu) return;
    
    mobileMenu.classList.add('active');
    menuToggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    
    // Focus management
    const firstLink = mobileMenu.querySelector('a');
    if (firstLink) {
        setTimeout(() => firstLink.focus(), 100);
    }
}

/**
 * Close mobile menu
 */
function closeMobileMenu() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileNavMenu');
    
    if (!menuToggle || !mobileMenu) return;
    
    mobileMenu.classList.remove('active');
    menuToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    
    // Return focus to menu toggle
    menuToggle.focus();
}

/**
 * Featured Businesses Slider
 */
function initializeFeaturedSlider() {
    const sliderContainer = document.querySelector('.businesses-slider .slider-container');
    if (!sliderContainer) {
        console.log('Featured slider not found on this page');
        return;
    }
    
    const sliderTrack = sliderContainer.querySelector('.slider-track');
    const prevBtn = sliderContainer.querySelector('.slider-control.prev');
    const nextBtn = sliderContainer.querySelector('.slider-control.next');
    const sliderItems = sliderContainer.querySelectorAll('.slider-item');
    
    if (!sliderTrack || !prevBtn || !nextBtn || sliderItems.length === 0) {
        console.warn('Slider elements not found or no items - keeping section visible');
        // Don't hide the section - let PHP handle the display logic
        return;
    }
    
    let currentIndex = 0;
    
    const getItemWidth = () => {
        if (window.innerWidth <= 600) return 260 + 24;
        if (window.innerWidth <= 900) return 280 + 24;
        if (window.innerWidth <= 1200) return 300 + 24;
        return 320 + 24;
    };
    
    const updateSlider = () => {
        const itemWidth = getItemWidth();
        const visibleItems = Math.floor(sliderTrack.offsetWidth / itemWidth);
        const maxIndex = Math.max(0, sliderItems.length - visibleItems);
        
        currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));
        
        const translateX = -currentIndex * itemWidth;
        sliderTrack.style.transform = `translateX(${translateX}px)`;
        
        prevBtn.disabled = currentIndex <= 0;
        nextBtn.disabled = currentIndex >= maxIndex;
        
        if (sliderItems.length <= visibleItems) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
        }
    };
    
    // Event listeners
    prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });
    
    nextBtn.addEventListener('click', () => {
        const itemWidth = getItemWidth();
        const visibleItems = Math.floor(sliderTrack.offsetWidth / itemWidth);
        const maxIndex = Math.max(0, sliderItems.length - visibleItems);
        
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateSlider();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', debounce(() => {
        updateSlider();
    }, 200));
    
    // Initialize slider
    updateSlider();
    
    // Add touch/swipe support for mobile
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    
    sliderTrack.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        isDragging = true;
    });
    
    sliderTrack.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
        const diff = startX - currentX;
        
        if (Math.abs(diff) > 50) {
            const itemWidth = getItemWidth();
            const visibleItems = Math.floor(sliderTrack.offsetWidth / itemWidth);
            const maxIndex = Math.max(0, sliderItems.length - visibleItems);
            
            if (diff > 0 && currentIndex < maxIndex) {
                // Swipe left - next
                currentIndex++;
            } else if (diff < 0 && currentIndex > 0) {
                // Swipe right - previous
                currentIndex--;
            }
            updateSlider();
            isDragging = false;
        }
    });
    
    sliderTrack.addEventListener('touchend', () => {
        isDragging = false;
    });
}

/**
 * Search Bar Functionality
 */
function initializeSearchBar() {
    const searchBar = document.querySelector('.airbnb-search-bar');
    const searchInput = document.querySelector('.airbnb-search-bar input[name="search"]');
    
    if (!searchBar || !searchInput) return;
    
    // Desktop autofocus only on large screens
    if (window.innerWidth > 1024) {
        searchInput.focus();
    }
    
    // Enhanced focus effects
    searchInput.addEventListener('focus', function() {
        searchBar.style.boxShadow = '0 8px 30px rgba(0, 0, 0, 0.12)';
        searchBar.style.borderColor = '#ffd000';
        searchBar.style.transform = 'translateY(-1px)';
    });
    
    searchInput.addEventListener('blur', function() {
        searchBar.style.boxShadow = '0 5px 25px rgba(0, 0, 0, 0.08)';
        searchBar.style.borderColor = '#e0e0e0';
        searchBar.style.transform = 'translateY(0)';
    });
    
    // Enhanced form validation
    searchBar.addEventListener('submit', function(e) {
        const location = searchBar.querySelector('select[name="location"]').value;
        const category = searchBar.querySelector('select[name="category"]').value;
        const search = searchBar.querySelector('input[name="search"]').value.trim();
        
        // Don't submit if no criteria selected
        if (!location && !category && !search) {
            e.preventDefault();
            searchInput.focus();
            
            // Add visual feedback
            searchBar.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(function() {
                searchBar.style.animation = '';
            }, 500);
            
            return false;
        }
    });
    
    // Add shake animation CSS
    if (!document.querySelector('#shake-animation')) {
        const style = document.createElement('style');
        style.id = 'shake-animation';
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Tooltips Initialization
 */
function initializeTooltips() {
    if (window.tippy) {
        tippy('[data-tippy-content]', {
            theme: 'jshuk-elite',
            animation: 'shift-away',
            arrow: true,
            delay: [100, 30],
            duration: [250, 180],
            maxWidth: 320,
            interactive: true,
            placement: 'top',
        });
    }
}

/**
 * Accordion Functionality
 */
function initializeAccordions() {
    const accordionButtons = document.querySelectorAll('.accordion-button');
    
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            
            if (target) {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                // Close all other accordion items
                accordionButtons.forEach(otherButton => {
                    if (otherButton !== this) {
                        otherButton.setAttribute('aria-expanded', 'false');
                        otherButton.classList.add('collapsed');
                        const otherTargetId = otherButton.getAttribute('data-bs-target');
                        const otherTarget = document.querySelector(otherTargetId);
                        if (otherTarget) {
                            otherTarget.classList.remove('show');
                        }
                    }
                });
                
                // Toggle current item
                this.setAttribute('aria-expanded', !isExpanded);
                this.classList.toggle('collapsed');
                target.classList.toggle('show');
            }
        });
    });
}

/**
 * Scroll Effects
 */
function initializeScrollEffects() {
    const scrollElements = document.querySelectorAll('[data-scroll]');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        scrollElements.forEach(el => observer.observe(el));
    } else {
        // Fallback for browsers without IntersectionObserver
        scrollElements.forEach(el => el.classList.add('animate-in'));
    }
}

/**
 * Update responsive components
 */
function updateResponsiveComponents() {
    // Update slider if it exists
    const sliderContainer = document.querySelector('.businesses-slider .slider-container');
    if (sliderContainer) {
        const event = new Event('resize');
        window.dispatchEvent(event);
    }
}

/**
 * Utility function: Debounce
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Utility function: Throttle
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions for global access
window.openMobileMenu = openMobileMenu;
window.closeMobileMenu = closeMobileMenu;
window.initializeFeaturedSlider = initializeFeaturedSlider; 