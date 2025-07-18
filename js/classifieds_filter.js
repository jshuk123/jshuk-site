/**
 * AJAX Filter System for JShuk Classifieds Page
 * Provides instant filtering without page refresh
 */

class ClassifiedsFilter {
    constructor() {
        this.debounceTimer = null;
        this.isLoading = false;
        this.currentFilters = {};
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeFilters();
    }
    
    bindEvents() {
        // Listen for filter changes
        this.bindFilterEvents();
        
        // Listen for sort changes
        this.bindSortEvents();
        
        // Listen for search input with debounce
        this.bindSearchEvents();
    }
    
    bindFilterEvents() {
        // Category dropdown in search bar
        const categorySelect = document.querySelector('#classifieds-search-form select[name="category"]');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => this.handleFilterChange());
        }
        
        // Location dropdown in search bar
        const locationSelect = document.querySelector('#classifieds-search-form select[name="location"]');
        if (locationSelect) {
            locationSelect.addEventListener('change', () => this.handleFilterChange());
        }
        
        // Filter buttons (All Items, Free Items Only, etc.)
        const filterButtons = document.querySelectorAll('.filter-buttons .btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleFilterButtonClick(button);
            });
        });
    }
    
    bindSortEvents() {
        const sortSelect = document.getElementById('sort-by');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => this.handleFilterChange());
        }
    }
    
    bindSearchEvents() {
        const searchInput = document.querySelector('#classifieds-search-form input[name="q"]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.handleFilterChange();
                }, 300); // 300ms debounce
            });
        }
    }
    
    handleFilterButtonClick(button) {
        // Remove active class from all buttons
        document.querySelectorAll('.filter-buttons .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        button.classList.add('active');
        
        // Update filters based on button
        const href = button.getAttribute('href');
        const url = new URL(href, window.location.origin);
        
        // Clear existing filters
        this.currentFilters = {};
        
        // Set new filters based on URL parameters
        if (url.searchParams.has('free_only')) {
            this.currentFilters.free_only = url.searchParams.get('free_only');
        }
        if (url.searchParams.has('category')) {
            this.currentFilters.category = url.searchParams.get('category');
        }
        
        // Apply the filters
        this.handleFilterChange();
    }
    
    initializeFilters() {
        // Get current filter values from URL or form
        this.currentFilters = this.getCurrentFilterValues();
    }
    
    getCurrentFilterValues() {
        const filters = {};
        
        // Get search form values
        const searchForm = document.querySelector('#classifieds-search-form');
        if (searchForm) {
            const formData = new FormData(searchForm);
            for (let [key, value] of formData.entries()) {
                if (value) {
                    filters[key] = value;
                }
            }
        }
        
        // Get sort value
        const sortSelect = document.getElementById('sort-by');
        if (sortSelect) {
            filters.sort = sortSelect.value;
        }
        
        // Get free only filter from active button
        const activeButton = document.querySelector('.filter-buttons .btn.active');
        if (activeButton) {
            const href = activeButton.getAttribute('href');
            const url = new URL(href, window.location.origin);
            if (url.searchParams.has('free_only')) {
                filters.free_only = url.searchParams.get('free_only');
            }
            if (url.searchParams.has('category')) {
                filters.category = url.searchParams.get('category');
            }
        }
        
        return filters;
    }
    
    async handleFilterChange() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();
        
        try {
            const filters = this.getCurrentFilterValues();
            const response = await this.makeAjaxRequest(filters);
            
            if (response.success) {
                this.updatePageContent(response);
                this.updateURL(filters);
                this.currentFilters = filters;
            } else {
                console.error('Filter request failed:', response.error);
                this.showErrorState();
            }
        } catch (error) {
            console.error('AJAX request failed:', error);
            this.showErrorState();
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }
    
    async makeAjaxRequest(filters) {
        const formData = new FormData();
        
        // Add all filter values to form data
        Object.keys(filters).forEach(key => {
            if (filters[key] !== '') {
                formData.append(key, filters[key]);
            }
        });
        
        const response = await fetch('/api/ajax_filter_classifieds.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    updatePageContent(response) {
        // Update results grid
        const resultsGrid = document.querySelector('.classifieds-grid');
        if (resultsGrid) {
            resultsGrid.innerHTML = response.results_html;
        }
        
        // Update result count
        const resultCount = document.querySelector('.text-muted');
        if (resultCount) {
            resultCount.textContent = `${response.total_classifieds} item${response.total_classifieds !== 1 ? 's' : ''} found`;
        }
        
        // Update search form values to match current filters
        this.updateSearchForm(response.filters_applied);
        
        // Smooth scroll to results if filters were applied
        this.scrollToResults();
        
        // Re-bind events to new content
        this.bindEvents();
    }
    
    updateSearchForm(filters) {
        // Update category dropdown
        const categorySelect = document.querySelector('#classifieds-search-form select[name="category"]');
        if (categorySelect) {
            categorySelect.value = filters.category || '';
        }
        
        // Update location dropdown
        const locationSelect = document.querySelector('#classifieds-search-form select[name="location"]');
        if (locationSelect) {
            locationSelect.value = filters.location || '';
        }
        
        // Update search input
        const searchInput = document.querySelector('#classifieds-search-form input[name="q"]');
        if (searchInput) {
            searchInput.value = filters.search || '';
        }
        
        // Update sort dropdown
        const sortSelect = document.getElementById('sort-by');
        if (sortSelect) {
            sortSelect.value = filters.sort || 'newest';
        }
    }
    
    updateURL(filters) {
        const url = new URL(window.location);
        
        // Clear existing filter params
        const paramsToRemove = ['category', 'q', 'sort', 'location', 'free_only'];
        paramsToRemove.forEach(param => url.searchParams.delete(param));
        
        // Add new filter params
        Object.keys(filters).forEach(key => {
            if (filters[key] !== '') {
                url.searchParams.set(key, filters[key]);
            }
        });
        
        // Update browser URL without page reload
        window.history.pushState({}, '', url);
    }
    
    showLoadingState() {
        const resultsArea = document.querySelector('.classifieds-section');
        
        if (resultsArea) {
            resultsArea.classList.add('loading');
        }
        
        // Add loading overlay to results
        this.addLoadingOverlay();
    }
    
    hideLoadingState() {
        const resultsArea = document.querySelector('.classifieds-section');
        
        if (resultsArea) {
            resultsArea.classList.remove('loading');
        }
        
        // Remove loading overlay
        this.removeLoadingOverlay();
    }
    
    addLoadingOverlay() {
        const resultsArea = document.querySelector('.classifieds-section');
        if (!resultsArea) return;
        
        // Remove existing overlay
        this.removeLoadingOverlay();
        
        // Create loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Updating results...</p>
            </div>
        `;
        
        resultsArea.appendChild(overlay);
    }
    
    removeLoadingOverlay() {
        const existingOverlay = document.querySelector('.loading-overlay');
        if (existingOverlay) {
            existingOverlay.remove();
        }
    }
    
    showErrorState() {
        const resultsArea = document.querySelector('.classifieds-section');
        if (resultsArea) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger text-center';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                There was an error loading the results. Please try again.
            `;
            
            const resultsGrid = resultsArea.querySelector('.classifieds-grid');
            if (resultsGrid) {
                resultsGrid.parentNode.insertBefore(errorDiv, resultsGrid);
                
                // Remove error message after 5 seconds
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }
        }
    }
    
    scrollToResults() {
        const resultsSection = document.querySelector('.classifieds-section');
        if (resultsSection) {
            resultsSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
    }
    
    resetFilters() {
        // Reset all form inputs
        const searchForm = document.querySelector('#classifieds-search-form');
        if (searchForm) {
            searchForm.reset();
        }
        
        // Reset sort dropdown
        const sortSelect = document.getElementById('sort-by');
        if (sortSelect) {
            sortSelect.value = 'newest';
        }
        
        // Reset filter buttons
        document.querySelectorAll('.filter-buttons .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Set "All Items" as active
        const allItemsBtn = document.querySelector('.filter-buttons .btn[href="/classifieds.php"]');
        if (allItemsBtn) {
            allItemsBtn.classList.add('active');
        }
        
        // Apply reset
        this.handleFilterChange();
    }
}

// Initialize the filter system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the classifieds filter
    window.classifiedsFilter = new ClassifiedsFilter();
    
    // Add loading states to classified cards
    const classifiedCards = document.querySelectorAll('.classified-card');
    
    classifiedCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't add loading if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                return;
            }
            
            // Add loading state
            this.classList.add('loading');
            
            // Navigate to classified page
            const classifiedLink = this.querySelector('a[href*="classified_view.php"]');
            if (classifiedLink) {
                window.location.href = classifiedLink.href;
            }
        });
    });
    
    // Add smooth scrolling for search form
    const searchForm = document.querySelector('#classifieds-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent form submission, let AJAX handle it
            
            // Add a small delay to show loading state
            const submitBtn = this.querySelector('.search-button-unified');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                submitBtn.disabled = true;
                
                // Reset button after a short delay
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fa fa-search"></i><span class="d-none d-md-inline">Search</span>';
                    submitBtn.disabled = false;
                }, 1000);
            }
            
            // Trigger filter change
            if (window.classifiedsFilter) {
                window.classifiedsFilter.handleFilterChange();
            }
        });
    }
    
    // Add scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('[data-scroll]').forEach(el => {
        observer.observe(el);
    });
});

// Add CSS for loading states
const style = document.createElement('style');
style.textContent = `
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 12px;
    }
    
    .loading-spinner {
        text-align: center;
    }
    
    .classifieds-section.loading {
        position: relative;
        min-height: 200px;
    }
    
    .classifieds-section.loading .classifieds-grid {
        opacity: 0.5;
        pointer-events: none;
    }
`;
document.head.appendChild(style); 