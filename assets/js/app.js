/**
 * JShuk - Main JavaScript Application
 * Modern, responsive, and user-friendly functionality
 */

class JShukApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupLazyLoading();
        this.setupFormValidation();
        this.setupNotifications();
        this.setupSearch();
        this.setupCarousels();
        this.setupMobileMenu();
        this.setupScrollEffects();
        this.setupAnalytics();
        this.initializeButtons();
        this.initializeBackToTop();
        // this.initializeNavigation(); // Commented out to use Bootstrap's native collapse functionality
    }

    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Handle form submissions
        document.addEventListener('submit', (e) => this.handleFormSubmit(e));
        
        // Handle AJAX requests
        document.addEventListener('click', (e) => this.handleAjaxClick(e));
        
        // Handle scroll events
        window.addEventListener('scroll', this.debounce(() => this.handleScroll(), 10));
        
        // Handle resize events
        window.addEventListener('resize', this.debounce(() => this.handleResize(), 100));
        
        // Handle keyboard events
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
    }

    /**
     * Initialize UI components
     */
    initializeComponents() {
        this.initializeTooltips();
        this.initializeModals();
        // this.initializeDropdowns(); // Commented out to use Bootstrap's native dropdown functionality
        this.initializeTabs();
        this.initializeAccordions();
        this.initializeImageZoom();
        this.initializeInfiniteScroll();
    }

    /**
     * Setup lazy loading for images
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Setup form validation
     */
    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => this.validateForm(e));
            form.addEventListener('input', (e) => this.validateField(e.target));
            form.addEventListener('blur', (e) => this.validateField(e.target), true);
        });
    }

    /**
     * Validate a single form field
     */
    validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');
        const pattern = field.getAttribute('pattern');
        const minLength = field.getAttribute('minlength');
        const maxLength = field.getAttribute('maxlength');
        
        let isValid = true;
        let errorMessage = '';

        // Check if required
        if (required && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Check email format
        if (type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }

        // Check pattern
        if (pattern && value && !new RegExp(pattern).test(value)) {
            isValid = false;
            errorMessage = field.getAttribute('data-error') || 'Invalid format';
        }

        // Check length
        if (minLength && value.length < parseInt(minLength)) {
            isValid = false;
            errorMessage = `Minimum ${minLength} characters required`;
        }

        if (maxLength && value.length > parseInt(maxLength)) {
            isValid = false;
            errorMessage = `Maximum ${maxLength} characters allowed`;
        }

        // Update field state
        this.updateFieldState(field, isValid, errorMessage);
        
        return isValid;
    }

    /**
     * Update field validation state
     */
    updateFieldState(field, isValid, errorMessage) {
        const container = field.closest('.form-group') || field.parentElement;
        const errorElement = container.querySelector('.invalid-feedback');
        
        field.classList.remove('is-valid', 'is-invalid');
        
        if (isValid) {
            field.classList.add('is-valid');
            if (errorElement) errorElement.style.display = 'none';
        } else {
            field.classList.add('is-invalid');
            if (errorElement) {
                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';
            }
        }
    }

    /**
     * Validate entire form
     */
    validateForm(event) {
        const form = event.target;
        const fields = form.querySelectorAll('input, select, textarea');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        if (!isValid) {
            event.preventDefault();
            this.showNotification('Please correct the errors in the form', 'error');
        }

        return isValid;
    }

    /**
     * Setup notifications system
     */
    setupNotifications() {
        this.notificationContainer = document.createElement('div');
        this.notificationContainer.className = 'notification-container';
        this.notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(this.notificationContainer);
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${this.getNotificationColor(type)};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            cursor: pointer;
        `;
        
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        this.notificationContainer.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
    }

    /**
     * Remove notification
     */
    removeNotification(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }

    /**
     * Get notification color
     */
    getNotificationColor(type) {
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        return colors[type] || colors.info;
    }

    /**
     * Setup search functionality
     */
    setupSearch() {
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.querySelector('.search-input');
        
        if (searchForm && searchInput) {
            let searchTimeout;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        this.performSearch(query);
                    }, 300);
                }
            });
        }
    }

    /**
     * Perform search
     */
    async performSearch(query) {
        try {
            const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.success) {
                this.displaySearchResults(data.results);
            } else {
                this.showNotification('Search failed', 'error');
            }
        } catch (error) {
            console.error('Search error:', error);
            this.showNotification('Search failed', 'error');
        }
    }

    /**
     * Display search results
     */
    displaySearchResults(results) {
        const resultsContainer = document.querySelector('.search-results');
        if (!resultsContainer) return;

        if (results.length === 0) {
            resultsContainer.innerHTML = '<p class="text-muted">No results found</p>';
            return;
        }

        const html = results.map(result => `
            <div class="search-result-item">
                <h5><a href="${result.url}">${result.title}</a></h5>
                <p>${result.description}</p>
            </div>
        `).join('');

        resultsContainer.innerHTML = html;
    }

    /**
     * Setup carousels
     */
    setupCarousels() {
        // Initialize standard Bootstrap Carousels
        const adCarouselElement = document.getElementById('adCarousel');
        if (adCarouselElement) {
            new bootstrap.Carousel(adCarouselElement, {
                interval: 5000,
                ride: 'carousel'
            });
        }

        // Initialize custom scrolling carousel for categories
        const categoryCarousel = document.getElementById('categoryCarousel');
        if (categoryCarousel) {
            const container = categoryCarousel.querySelector('.category-track');
            const prevBtn = categoryCarousel.querySelector('.category-carousel-control.prev');
            const nextBtn = categoryCarousel.querySelector('.category-carousel-control.next');

            if (container && prevBtn && nextBtn) {
                const updateButtons = () => {
                    const scrollAmount = container.offsetWidth;
                    const maxScroll = container.scrollWidth - container.clientWidth;
                    prevBtn.disabled = container.scrollLeft < 1;
                    nextBtn.disabled = container.scrollLeft >= maxScroll - 1;
                };

                prevBtn.addEventListener('click', () => {
                    container.scrollBy({ left: -container.offsetWidth, behavior: 'smooth' });
                });

                nextBtn.addEventListener('click', () => {
                    container.scrollBy({ left: container.offsetWidth, behavior: 'smooth' });
                });

                container.addEventListener('scroll', this.debounce(updateButtons, 50));
                window.addEventListener('resize', this.debounce(updateButtons, 200));
                updateButtons(); // Initial check
            }
        }

        // Initialize businesses slider
        this.initializeBusinessesSlider();
    }

    /**
     * Initialize the businesses slider functionality
     * Note: This is now handled by main.js for better organization
     */
    initializeBusinessesSlider() {
        // Slider initialization moved to main.js
        // This method is kept for backward compatibility
        console.log('Slider initialization handled by main.js');
    }

    /**
     * Setup mobile menu
     */
    setupMobileMenu() {
        const menuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileNavMenu');
        const mobileNavClose = document.getElementById('mobileNavClose');
        
        // Touch/swipe variables
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.openMobileMenu();
            }.bind(this));
        }
        
        if (mobileNavClose && mobileMenu) {
            mobileNavClose.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.closeMobileMenu();
            }.bind(this));
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                if (!mobileMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                    this.closeMobileMenu();
                }
            }
        });
        
        // Close menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileMenu && mobileMenu.classList.contains('active')) {
                this.closeMobileMenu();
            }
        });
        
        // Swipe to close functionality
        if (mobileMenu) {
            // Touch events for swipe
            mobileMenu.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            mobileMenu.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            mobileMenu.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
            
            // Mouse events for desktop testing
            mobileMenu.addEventListener('mousedown', this.handleMouseStart.bind(this));
            mobileMenu.addEventListener('mousemove', this.handleMouseMove.bind(this));
            mobileMenu.addEventListener('mouseup', this.handleMouseEnd.bind(this));
            mobileMenu.addEventListener('mouseleave', this.handleMouseEnd.bind(this));
        }
        
        // Store references for use in other methods
        this.menuToggle = menuToggle;
        this.mobileMenu = mobileMenu;
    }
    
    /**
     * Open mobile menu
     */
    openMobileMenu() {
        if (this.mobileMenu) {
            this.mobileMenu.classList.add('active');
            this.mobileMenu.setAttribute('aria-hidden', 'false');
            if (this.menuToggle) {
                this.menuToggle.setAttribute('aria-expanded', 'true');
            }
            document.body.style.overflow = 'hidden';
        }
    }
    
    /**
     * Close mobile menu
     */
    closeMobileMenu() {
        if (this.mobileMenu) {
            this.mobileMenu.classList.add('closing');
            setTimeout(() => {
                this.mobileMenu.classList.remove('active', 'closing');
                this.mobileMenu.setAttribute('aria-hidden', 'true');
                if (this.menuToggle) {
                    this.menuToggle.setAttribute('aria-expanded', 'false');
                }
                document.body.style.overflow = '';
            }, 300);
        }
    }
    
    /**
     * Touch event handlers for swipe to close
     */
    handleTouchStart(e) {
        this.startX = e.touches[0].clientX;
        this.isDragging = true;
    }
    
    handleTouchMove(e) {
        if (!this.isDragging) return;
        e.preventDefault();
        this.currentX = e.touches[0].clientX;
        const diff = this.startX - this.currentX;
        
        if (diff > 50) { // Swipe left to close
            this.closeMobileMenu();
            this.isDragging = false;
        }
    }
    
    handleTouchEnd() {
        this.isDragging = false;
    }
    
    handleMouseStart(e) {
        this.startX = e.clientX;
        this.isDragging = true;
    }
    
    handleMouseMove(e) {
        if (!this.isDragging) return;
        this.currentX = e.clientX;
        const diff = this.startX - this.currentX;
        
        if (diff > 50) { // Swipe left to close
            this.closeMobileMenu();
            this.isDragging = false;
        }
    }
    
    handleMouseEnd() {
        this.isDragging = false;
    }

    /**
     * Setup scroll effects
     */
    setupScrollEffects() {
        const elements = document.querySelectorAll('[data-scroll]');
        
        if ('IntersectionObserver' in window) {
            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('scroll-animate');
                    }
                });
            }, { threshold: 0.1 });

            elements.forEach(element => {
                scrollObserver.observe(element);
            });
        }
    }

    /**
     * Setup analytics
     */
    setupAnalytics() {
        // Track page views
        this.trackPageView();
        
        // Track events
        document.addEventListener('click', (e) => {
            const trackElement = e.target.closest('[data-track]');
            if (trackElement) {
                const event = trackElement.dataset.track;
                const category = trackElement.dataset.category || 'general';
                this.trackEvent(category, event);
            }
        });
    }

    /**
     * Track page view
     */
    trackPageView() {
        if (typeof gtag !== 'undefined') {
            gtag('config', 'GA_MEASUREMENT_ID', {
                page_title: document.title,
                page_location: window.location.href
            });
        }
    }

    /**
     * Track event
     */
    trackEvent(category, action, label = null) {
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                event_category: category,
                event_label: label
            });
        }
    }

    /**
     * Handle form submission
     */
    async handleFormSubmit(event) {
        const form = event.target;
        
        if (form.hasAttribute('data-ajax')) {
            event.preventDefault();
            await this.submitFormAjax(form);
        }
    }

    /**
     * Submit form via AJAX
     */
    async submitFormAjax(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('[type="submit"]');
        
        // Disable submit button
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
        }

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(data.message || 'Success!', 'success');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                }
            } else {
                this.showNotification(data.message || 'An error occurred', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showNotification('An error occurred', 'error');
        } finally {
            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = submitButton.dataset.originalText || 'Submit';
            }
        }
    }

    /**
     * Handle AJAX clicks
     */
    async handleAjaxClick(event) {
        const element = event.target.closest('[data-ajax]');
        
        if (element) {
            event.preventDefault();
            
            const url = element.href || element.dataset.url;
            const method = element.dataset.method || 'GET';
            
            try {
                const response = await fetch(url, { method });
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification(data.message || 'Success!', 'success');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    this.showNotification(data.message || 'An error occurred', 'error');
                }
            } catch (error) {
                console.error('AJAX error:', error);
                this.showNotification('An error occurred', 'error');
            }
        }
    }

    /**
     * Handle scroll events
     */
    handleScroll() {
        const scrollTop = window.pageYOffset;
        const navbar = document.querySelector('.navbar');
        
        if (navbar) {
            if (scrollTop > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }

        // Back to top button
        const backToTop = document.querySelector('.back-to-top');
        if (backToTop) {
            if (scrollTop > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        }
    }

    /**
     * Handle resize events
     */
    handleResize() {
        // Update any responsive components
        this.updateResponsiveComponents();
    }

    /**
     * Handle keyboard events
     */
    handleKeyboard(event) {
        // ESC key to close modals
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                this.closeModal(openModal);
            }
        }

        // Enter key to submit forms
        if (event.key === 'Enter' && event.target.matches('input, textarea')) {
            const form = event.target.closest('form');
            if (form && !event.target.matches('textarea')) {
                event.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        }
    }

    /**
     * Handle visibility change
     */
    handleVisibilityChange() {
        if (document.hidden) {
            document.title = 'Come back! - ' + document.title;
        } else {
            document.title = document.title.replace('Come back! - ', '');
        }
    }

    /**
     * Utility functions
     */
    debounce(func, wait) {
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

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = e.target.dataset.tooltip;
                tooltip.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 1000;
                    pointer-events: none;
                `;
                
                document.body.appendChild(tooltip);
                
                const rect = e.target.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                
                e.target.tooltip = tooltip;
            });
            
            element.addEventListener('mouseleave', (e) => {
                if (e.target.tooltip) {
                    e.target.tooltip.remove();
                    e.target.tooltip = null;
                }
            });
        });
    }

    /**
     * Initialize modals
     */
    initializeModals() {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                const modal = document.getElementById(modalId);
                if (modal) {
                    this.openModal(modal);
                }
            });
        });

        // Close modal on backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target);
            }
        });
    }

    /**
     * Open modal
     */
    openModal(modal) {
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close modal
     */
    closeModal(modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    /* Commenting out the custom dropdown logic to prevent conflict with Bootstrap 5
    initializeDropdowns() {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

        dropdownToggles.forEach(toggle => {
            const dropdownMenu = toggle.nextElementSibling;
            
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                } else {
                    this.closeAllDropdowns();
                    dropdownMenu.classList.add('show');
                }
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target.matches('.dropdown-toggle')) {
                this.closeAllDropdowns();
            }
        });
    }

    closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
    */

    /**
     * Initialize tabs
     */
    initializeTabs() {
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = link.getAttribute('href');
                const tabContent = document.querySelector(target);
                
                if (tabContent) {
                    const tabPane = tabContent.closest('.tab-pane');
                    const tabContainer = tabPane.closest('.tab-content');
                    
                    tabContainer.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('active', 'show');
                    });
                    
                    tabPane.classList.add('active', 'show');
                    
                    tabLinks.forEach(l => {
                        l.classList.remove('active');
                    });
                    
                    link.classList.add('active');
                }
            });
        });
    }

    /**
     * Initialize accordions
     */
    initializeAccordions() {
        const accordions = document.querySelectorAll('.accordion');
        
        accordions.forEach(accordion => {
            const items = accordion.querySelectorAll('.accordion-item');
            
            items.forEach(item => {
                const header = item.querySelector('.accordion-header');
                const content = item.querySelector('.accordion-content');
                
                if (header && content) {
                    header.addEventListener('click', () => {
                        const isOpen = item.classList.contains('open');
                        
                        // Close all items
                        items.forEach(i => {
                            i.classList.remove('open');
                            const c = i.querySelector('.accordion-content');
                            if (c) c.style.maxHeight = null;
                        });
                        
                        // Open current item if it was closed
                        if (!isOpen) {
                            item.classList.add('open');
                            content.style.maxHeight = content.scrollHeight + 'px';
                        }
                    });
                }
            });
        });
    }

    /**
     * Initialize image zoom
     */
    initializeImageZoom() {
        const images = document.querySelectorAll('.zoom-image');
        
        images.forEach(img => {
            img.addEventListener('click', () => {
                const modal = document.createElement('div');
                modal.className = 'image-modal';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    cursor: pointer;
                `;
                
                const modalImg = document.createElement('img');
                modalImg.src = img.src;
                modalImg.style.cssText = `
                    max-width: 90%;
                    max-height: 90%;
                    object-fit: contain;
                `;
                
                modal.appendChild(modalImg);
                document.body.appendChild(modal);
                
                modal.addEventListener('click', () => {
                    modal.remove();
                });
            });
        });
    }

    /**
     * Initialize infinite scroll
     */
    initializeInfiniteScroll() {
        const containers = document.querySelectorAll('[data-infinite-scroll]');
        
        containers.forEach(container => {
            let page = 1;
            let loading = false;
            
            const loadMore = async () => {
                if (loading) return;
                
                loading = true;
                const url = container.dataset.infiniteScroll;
                
                try {
                    const response = await fetch(`${url}?page=${page}`);
                    const data = await response.json();
                    
                    if (data.success && data.content) {
                        container.insertAdjacentHTML('beforeend', data.content);
                        page++;
                        
                        if (!data.hasMore) {
                            container.removeAttribute('data-infinite-scroll');
                        }
                    }
                } catch (error) {
                    console.error('Infinite scroll error:', error);
                } finally {
                    loading = false;
                }
            };
            
            // Check if we need to load more
            const checkScroll = () => {
                const rect = container.getBoundingClientRect();
                if (rect.bottom <= window.innerHeight + 100) {
                    loadMore();
                }
            };
            
            window.addEventListener('scroll', this.debounce(checkScroll, 100));
        });
    }

    /**
     * Update responsive components
     */
    updateResponsiveComponents() {
        // Update any components that need to be responsive
        const isMobile = window.innerWidth < 768;
        
        // Update mobile menu
        const mobileMenu = document.querySelector('.mobile-menu');
        if (mobileMenu && !isMobile) {
            mobileMenu.classList.remove('active');
        }
    }

    initializeButtons() {
        // Hero buttons
        document.querySelectorAll('.btn-hero').forEach(button => {
            if (!button.hasAttribute('href')) {
                button.addEventListener('click', (e) => {
                    const href = button.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            }
        });

        // Regular buttons
        document.querySelectorAll('.btn-primary, .btn-secondary').forEach(button => {
            if (!button.classList.contains('btn-hero') && !button.hasAttribute('href')) {
                button.addEventListener('click', (e) => {
                    const href = button.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            }
        });
    }

    initializeBackToTop() {
        const backToTop = document.querySelector('.back-to-top');
        if (backToTop) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    backToTop.style.display = 'block';
                } else {
                    backToTop.style.display = 'none';
                }
            });

            backToTop.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    }
}

/**
 * Initialize the application once the window and all its resources are fully loaded.
 */
window.addEventListener('load', () => {
    window.app = new JShukApp();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = JShukApp;
}

// Global function for mobile menu (for HTML onclick handlers)
window.openMobileMenu = function() {
    if (window.app && window.app.openMobileMenu) {
        window.app.openMobileMenu();
    }
};

window.closeMobileMenu = function() {
    if (window.app && window.app.closeMobileMenu) {
        window.app.closeMobileMenu();
    }
};