/**
 * AJAX Filter System for JShuk Jobs Page
 * Provides instant filtering without page refresh
 */

class JobsFilter {
    constructor() {
        this.debounceTimer = null;
        this.isLoading = false;
        this.currentFilters = {};
        
        this.init();
    }
    
    init() {
        console.log('🔍 Initializing Jobs Filter System...');
        this.bindEvents();
        console.log('✅ Jobs Filter System initialized');
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
        // Sector dropdown
        const sectorSelect = document.getElementById('sector');
        if (sectorSelect) {
            sectorSelect.addEventListener('change', () => this.handleFilterChange());
        }
        
        // Location dropdown
        const locationSelect = document.getElementById('location');
        if (locationSelect) {
            locationSelect.addEventListener('change', () => this.handleFilterChange());
        }
        
        // Job type dropdown
        const jobTypeSelect = document.getElementById('job_type');
        if (jobTypeSelect) {
            jobTypeSelect.addEventListener('change', () => this.handleFilterChange());
        }
    }
    
    bindSortEvents() {
        const sortSelect = document.getElementById('sort');
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
    
    getCurrentFilterValues() {
        const form = document.getElementById('jobSearchForm');
        if (!form) return {};
        
        const formData = new FormData(form);
        const filters = {};
        
        // Get all form values
        for (let [key, value] of formData.entries()) {
            filters[key] = value;
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
        
        const response = await fetch('/api/ajax_filter_jobs.php', {
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
        const resultsGrid = document.querySelector('.jobs-grid');
        if (resultsGrid) {
            resultsGrid.innerHTML = response.results_html;
        }
        
        // Update result count
        const resultCount = document.querySelector('.jobs-count p');
        if (resultCount) {
            resultCount.textContent = `${response.total_jobs} job${response.total_jobs != 1 ? 's' : ''} found`;
        }
        
        // Re-bind save job events to new elements
        this.bindSaveJobEvents();
        
        // Smooth scroll to results if filters were applied
        this.scrollToResults();
    }
    
    updateURL(filters) {
        const url = new URL(window.location);
        
        // Clear existing filter params
        const paramsToRemove = ['sector', 'location', 'job_type', 'search', 'sort'];
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
        const resultsGrid = document.querySelector('.jobs-grid');
        if (resultsGrid) {
            resultsGrid.style.opacity = '0.6';
            resultsGrid.style.pointerEvents = 'none';
        }
        
        // Add loading spinner
        const loadingSpinner = document.createElement('div');
        loadingSpinner.className = 'loading-spinner';
        loadingSpinner.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Updating job results...</p>
        `;
        loadingSpinner.style.cssText = `
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 1000;
        `;
        
        const jobsSection = document.querySelector('.jobs-section .container');
        if (jobsSection) {
            jobsSection.style.position = 'relative';
            jobsSection.appendChild(loadingSpinner);
        }
    }
    
    hideLoadingState() {
        const resultsGrid = document.querySelector('.jobs-grid');
        if (resultsGrid) {
            resultsGrid.style.opacity = '1';
            resultsGrid.style.pointerEvents = 'auto';
        }
        
        // Remove loading spinner
        const loadingSpinner = document.querySelector('.loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.remove();
        }
    }
    
    showErrorState() {
        const resultsGrid = document.querySelector('.jobs-grid');
        if (resultsGrid) {
            resultsGrid.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Unable to load job results. Please try refreshing the page.
                </div>
            `;
        }
    }
    
    scrollToResults() {
        const jobsSection = document.querySelector('.jobs-section');
        if (jobsSection) {
            jobsSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
    }
    
    bindSaveJobEvents() {
        // Re-bind save job functionality to new elements
        document.querySelectorAll('.save-job-btn').forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.dataset.jobId;
                const button = this;
                
                // Show loading state
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                button.disabled = true;
                
                fetch('/api/save_job.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `job_id=${jobId}&action=toggle`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.is_saved) {
                            // Job is now saved
                            button.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
                            button.classList.remove('btn-outline-secondary');
                            button.classList.add('btn-success');
                            button.title = 'Remove from saved jobs';
                        } else {
                            // Job is now unsaved
                            button.innerHTML = '<i class="fas fa-bookmark"></i> Save';
                            button.classList.remove('btn-success');
                            button.classList.add('btn-outline-secondary');
                            button.title = 'Save this job';
                        }
                        button.disabled = false;
                    } else if (data.action === 'login_required') {
                        // Redirect to login
                        window.location.href = '/auth/login.php?redirect=' + encodeURIComponent(window.location.href);
                    } else {
                        alert('Error saving job: ' + data.message);
                        button.innerHTML = originalContent;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving job. Please try again.');
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
            });
        });
        
        // Re-bind job alert functionality
        document.querySelectorAll('.create-alert-btn').forEach(button => {
            button.addEventListener('click', function() {
                const jobTitle = this.dataset.jobTitle;
                const jobSector = this.dataset.jobSector;
                const jobLocation = this.dataset.jobLocation;
                
                // Pre-fill the modal
                document.getElementById('alertName').value = jobTitle + ' Alert';
                
                // Set sector if available
                if (jobSector) {
                    const sectorSelect = document.getElementById('alertSector');
                    for (let option of sectorSelect.options) {
                        if (option.text === jobSector) {
                            option.selected = true;
                            break;
                        }
                    }
                }
                
                // Set location if available
                if (jobLocation) {
                    const locationSelect = document.getElementById('alertLocation');
                    for (let option of locationSelect.options) {
                        if (option.text === jobLocation) {
                            option.selected = true;
                            break;
                        }
                    }
                }
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('jobAlertModal'));
                modal.show();
            });
        });
    }
    
    // Public method to apply specific filters
    applyFilters(filters) {
        // Update form values
        Object.keys(filters).forEach(key => {
            if (key === 'sector') {
                const select = document.getElementById('sector');
                if (select) select.value = filters[key];
            } else if (key === 'location') {
                const select = document.getElementById('location');
                if (select) select.value = filters[key];
            } else if (key === 'job_type') {
                const select = document.getElementById('job_type');
                if (select) select.value = filters[key];
            } else if (key === 'search') {
                const input = document.getElementById('search');
                if (input) input.value = filters[key];
            } else if (key === 'sort') {
                const select = document.getElementById('sort');
                if (select) select.value = filters[key];
            }
        });
        
        // Trigger filter change
        this.handleFilterChange();
    }
}

// Initialize the filter system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the AJAX filter system
    window.jobsFilter = new JobsFilter();
    
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
            if (searchInput && document.activeElement === searchInput) {
                searchInput.value = '';
                searchInput.blur();
                if (window.jobsFilter) {
                    window.jobsFilter.handleFilterChange();
                }
            }
        }
    });
    
    // Add form submission prevention (use AJAX instead)
    const searchForm = document.getElementById('jobSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (window.jobsFilter) {
                window.jobsFilter.handleFilterChange();
            }
        });
    }
    
    // Add clear filters functionality
    const clearBtn = document.querySelector('.clear-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Clear all form inputs
            const form = document.getElementById('jobSearchForm');
            if (form) {
                form.reset();
                
                // Trigger filter change
                if (window.jobsFilter) {
                    window.jobsFilter.handleFilterChange();
                }
            }
        });
    }
} 