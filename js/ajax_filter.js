/**
 * AJAX Filter System for JShuk Businesses Page
 * Provides instant filtering without page refresh
 */

class BusinessFilter {
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
        // Category dropdown
        const categorySelect = document.getElementById('category');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => this.handleFilterChange());
        }
        
        // Location checkboxes
        const locationCheckboxes = document.querySelectorAll('input[name="locations[]"]');
        locationCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.handleFilterChange());
        });
        
        // Rating radio buttons
        const ratingRadios = document.querySelectorAll('input[name="rating"]');
        ratingRadios.forEach(radio => {
            radio.addEventListener('change', () => this.handleFilterChange());
        });
    }
    
    bindSortEvents() {
        const sortSelect = document.getElementById('sort-by');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => this.handleFilterChange());
        }
    }
    
    bindSearchEvents() {
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.handleFilterChange();
                }, 300); // 300ms debounce
            });
        }
    }
    
    initializeFilters() {
        // Get current filter values from URL or form
        this.currentFilters = this.getCurrentFilterValues();
    }
    
    getCurrentFilterValues() {
        const form = document.querySelector('.filter-form');
        if (!form) return {};
        
        const formData = new FormData(form);
        const filters = {};
        
        // Get all form values
        for (let [key, value] of formData.entries()) {
            if (key === 'locations[]') {
                if (!filters.locations) filters.locations = [];
                filters.locations.push(value);
            } else {
                filters[key] = value;
            }
        }
        
        // Get sort value from separate form
        const sortForm = document.querySelector('.sorting-form');
        if (sortForm) {
            const sortSelect = sortForm.querySelector('select[name="sort"]');
            if (sortSelect) {
                filters.sort = sortSelect.value;
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
            if (key === 'locations' && Array.isArray(filters[key])) {
                filters[key].forEach(location => {
                    formData.append('locations[]', location);
                });
            } else {
                formData.append(key, filters[key]);
            }
        });
        
        const response = await fetch('/api/ajax_filter_businesses.php', {
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
        const resultsGrid = document.querySelector('.results-grid-area .row');
        if (resultsGrid) {
            resultsGrid.innerHTML = response.results_html;
        }
        
        // Update sidebar filters
        const sidebar = document.querySelector('.filter-sidebar');
        if (sidebar) {
            const newForm = sidebar.querySelector('.filter-form');
            if (newForm) {
                newForm.innerHTML = response.sidebar_html;
                // Re-bind events to new form elements
                this.bindFilterEvents();
            }
        }
        
        // Update result count
        const resultCount = document.querySelector('.result-count');
        if (resultCount) {
            resultCount.textContent = `Showing ${response.start_result_number}-${response.end_result_number} of ${response.total_businesses} businesses`;
        }
        
        // Update map data if available and map system is initialized
        if (response.map_data && window.businessMap && window.businessMap.isInitialized()) {
            window.businessMap.updateBusinessData(response.map_data);
        }
        
        // Smooth scroll to results if filters were applied
        this.scrollToResults();
    }
    
    updateURL(filters) {
        const url = new URL(window.location);
        
        // Clear existing filter params
        const paramsToRemove = ['category', 'search', 'sort', 'locations', 'rating'];
        paramsToRemove.forEach(param => url.searchParams.delete(param));
        
        // Add new filter params
        Object.keys(filters).forEach(key => {
            if (key === 'locations' && Array.isArray(filters[key])) {
                filters[key].forEach(location => {
                    url.searchParams.append('locations[]', location);
                });
            } else if (filters[key] !== '') {
                url.searchParams.set(key, filters[key]);
            }
        });
        
        // Update browser URL without page reload
        window.history.pushState({}, '', url);
    }
    
    showLoadingState() {
        const resultsArea = document.querySelector('.results-grid-area');
        const sidebar = document.querySelector('.filter-sidebar');
        
        if (resultsArea) {
            resultsArea.classList.add('loading');
        }
        
        if (sidebar) {
            sidebar.classList.add('loading');
        }
        
        // Add loading overlay to results
        this.addLoadingOverlay();
    }
    
    hideLoadingState() {
        const resultsArea = document.querySelector('.results-grid-area');
        const sidebar = document.querySelector('.filter-sidebar');
        
        if (resultsArea) {
            resultsArea.classList.remove('loading');
        }
        
        if (sidebar) {
            sidebar.classList.remove('loading');
        }
        
        // Remove loading overlay
        this.removeLoadingOverlay();
    }
    
    addLoadingOverlay() {
        const resultsArea = document.querySelector('.results-grid-area');
        if (!resultsArea) return;
        
        // Remove existing overlay
        this.removeLoadingOverlay();
        
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
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    showErrorState() {
        const resultsArea = document.querySelector('.results-grid-area');
        if (!resultsArea) return;
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger text-center';
        errorDiv.innerHTML = `
            <h4>Oops! Something went wrong</h4>
            <p>We couldn't update your search results. Please try again.</p>
            <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">Reload Page</button>
        `;
        
        const row = resultsArea.querySelector('.row');
        if (row) {
            row.innerHTML = '';
            row.appendChild(errorDiv);
        }
    }
    
    scrollToResults() {
        // Only scroll if we're not at the top of the page
        if (window.scrollY > 100) {
            const resultsArea = document.querySelector('.results-grid-area');
            if (resultsArea) {
                resultsArea.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        }
    }
    
    // Public method to reset filters
    resetFilters() {
        window.location.href = '/businesses.php';
    }
    
    // Public method to apply specific filters
    applyFilters(filters) {
        // Update form values
        Object.keys(filters).forEach(key => {
            if (key === 'locations' && Array.isArray(filters[key])) {
                // Handle location checkboxes
                const checkboxes = document.querySelectorAll('input[name="locations[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = filters[key].includes(checkbox.value);
                });
            } else if (key === 'category') {
                const select = document.getElementById('category');
                if (select) select.value = filters[key];
            } else if (key === 'search') {
                const input = document.getElementById('search');
                if (input) input.value = filters[key];
            } else if (key === 'rating') {
                const radios = document.querySelectorAll('input[name="rating"]');
                radios.forEach(radio => {
                    radio.checked = radio.value === filters[key];
                });
            } else if (key === 'sort') {
                const sortSelect = document.getElementById('sort-by');
                if (sortSelect) sortSelect.value = filters[key];
            }
        });
        
        // Trigger filter change
        this.handleFilterChange();
    }
}

// Initialize the filter system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the AJAX filter system
    window.businessFilter = new BusinessFilter();
    
    // Add some additional UX enhancements
    enhanceUserExperience();
});

function enhanceUserExperience() {
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('search');
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                if (window.businessFilter) {
                    window.businessFilter.handleFilterChange();
                }
            }
        }
    });
    
    // Add tooltips for better UX
    const filterElements = document.querySelectorAll('.filter-option, .form-select, .form-control');
    filterElements.forEach(element => {
        if (element.title) {
            element.setAttribute('data-bs-toggle', 'tooltip');
            element.setAttribute('data-bs-placement', 'top');
        }
    });
    
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
} 