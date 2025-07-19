<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

// Get available sectors and locations for dropdowns
try {
    $stmt = $pdo->query("SELECT DISTINCT sector FROM salary_data WHERE is_active = 1 ORDER BY sector");
    $sectors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->query("SELECT DISTINCT location FROM salary_data WHERE is_active = 1 ORDER BY location");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Salary Guide Error: " . $e->getMessage());
    $sectors = [];
    $locations = [];
}

$pageTitle = "Salary Guide - Research Job Salaries by Sector and Location";
$page_css = "salary_guide.css";
$metaDescription = "Research salary ranges for different job roles across various sectors and locations. Get accurate salary data to help with your career planning and job negotiations.";
include 'includes/header_main.php';
?>

<div class="salary-guide-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Salary Guide</h1>
                <p class="hero-subtitle">
                    Research salary ranges for different job roles across various sectors and locations. 
                    Get accurate data to help with your career planning and job negotiations.
                </p>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="search-section">
        <div class="container">
            <div class="search-card">
                <form id="salarySearchForm" class="salary-search-form">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="sector" class="form-label">Job Sector</label>
                            <select class="form-select" id="sector" name="sector" required>
                                <option value="">Select a sector</option>
                                <?php foreach ($sectors as $sector): ?>
                                    <option value="<?= htmlspecialchars($sector) ?>">
                                        <?= htmlspecialchars($sector) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <select class="form-select" id="location" name="location" required>
                                <option value="">Select a location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= htmlspecialchars($location) ?>">
                                        <?= htmlspecialchars($location) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="experience" class="form-label">Experience Level</label>
                            <select class="form-select" id="experience" name="experience">
                                <option value="">All levels</option>
                                <option value="entry">Entry Level</option>
                                <option value="mid">Mid Level</option>
                                <option value="senior">Senior Level</option>
                                <option value="executive">Executive Level</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Show Salary Guide
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="results-section" id="resultsSection" style="display: none;">
        <div class="container">
            <div class="results-header">
                <h2 id="resultsTitle">Salary Information</h2>
                <p id="resultsSubtitle" class="text-muted">Based on current market data</p>
            </div>
            
            <div id="salaryResults" class="salary-results">
                <!-- Results will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <!-- Popular Searches -->
    <div class="popular-searches">
        <div class="container">
            <h3 class="section-title">Popular Salary Searches</h3>
            <div class="popular-grid">
                <div class="popular-item" data-sector="Technology" data-location="London">
                    <i class="fas fa-laptop-code"></i>
                    <span>Software Developer - London</span>
                </div>
                <div class="popular-item" data-sector="Finance" data-location="London">
                    <i class="fas fa-chart-line"></i>
                    <span>Financial Analyst - London</span>
                </div>
                <div class="popular-item" data-sector="Healthcare" data-location="Manchester">
                    <i class="fas fa-user-md"></i>
                    <span>Nurse - Manchester</span>
                </div>
                <div class="popular-item" data-sector="Marketing" data-location="London">
                    <i class="fas fa-bullhorn"></i>
                    <span>Marketing Manager - London</span>
                </div>
                <div class="popular-item" data-sector="Education" data-location="Manchester">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teacher - Manchester</span>
                </div>
                <div class="popular-item" data-sector="Legal" data-location="London">
                    <i class="fas fa-balance-scale"></i>
                    <span>Solicitor - London</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Section -->
    <div class="info-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-card">
                        <h4><i class="fas fa-info-circle me-2"></i>About Our Data</h4>
                        <p>
                            Our salary data is compiled from various sources including job postings, 
                            industry reports, and market research. The figures represent typical salary 
                            ranges for each role and location.
                        </p>
                        <p>
                            <strong>Note:</strong> Salaries may vary based on company size, specific 
                            requirements, and individual experience levels.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-card">
                        <h4><i class="fas fa-lightbulb me-2"></i>Salary Negotiation Tips</h4>
                        <ul>
                            <li>Research the market rate for your role and location</li>
                            <li>Consider your experience level and skills</li>
                            <li>Factor in benefits and other compensation</li>
                            <li>Be prepared to discuss your value proposition</li>
                            <li>Practice your negotiation approach</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('salarySearchForm');
    const resultsSection = document.getElementById('resultsSection');
    const salaryResults = document.getElementById('salaryResults');
    const resultsTitle = document.getElementById('resultsTitle');
    const resultsSubtitle = document.getElementById('resultsSubtitle');
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        // Show loading state
        salaryResults.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading salary data...</p></div>';
        resultsSection.style.display = 'block';
        
        // Scroll to results
        resultsSection.scrollIntoView({ behavior: 'smooth' });
        
        // Fetch salary data
        fetch(`/api/get_salary_data.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySalaryResults(data.data, formData.get('sector'), formData.get('location'));
                } else {
                    displayNoResults();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayError();
            });
    });
    
    // Handle popular search clicks
    document.querySelectorAll('.popular-item').forEach(item => {
        item.addEventListener('click', function() {
            const sector = this.dataset.sector;
            const location = this.dataset.location;
            
            document.getElementById('sector').value = sector;
            document.getElementById('location').value = location;
            
            // Trigger form submission
            form.dispatchEvent(new Event('submit'));
        });
    });
    
    function displaySalaryResults(data, sector, location) {
        if (data.length === 0) {
            displayNoResults();
            return;
        }
        
        resultsTitle.textContent = `Salary Guide: ${sector} in ${location}`;
        resultsSubtitle.textContent = `Showing ${data.length} job role${data.length !== 1 ? 's' : ''}`;
        
        let html = '<div class="salary-grid">';
        
        data.forEach(job => {
            const lowFormatted = formatSalary(job.salary_low);
            const avgFormatted = formatSalary(job.salary_average);
            const highFormatted = formatSalary(job.salary_high);
            
            const avgPercentage = ((job.salary_average - job.salary_low) / (job.salary_high - job.salary_low)) * 100;
            
            html += `
                <div class="salary-card">
                    <div class="salary-header">
                        <h4 class="job-title">${escapeHtml(job.job_title)}</h4>
                        <span class="experience-badge experience-${job.experience_level}">
                            ${getExperienceLabel(job.experience_level)}
                        </span>
                    </div>
                    
                    <div class="salary-range">
                        <div class="range-bar">
                            <div class="range-fill" style="left: 0%; width: 100%;"></div>
                            <div class="range-marker average" style="left: ${avgPercentage}%;">
                                <span class="marker-label">Average</span>
                            </div>
                        </div>
                        
                        <div class="salary-figures">
                            <div class="salary-item low">
                                <span class="label">Low</span>
                                <span class="amount">£${lowFormatted}</span>
                            </div>
                            <div class="salary-item average">
                                <span class="label">Average</span>
                                <span class="amount">£${avgFormatted}</span>
                            </div>
                            <div class="salary-item high">
                                <span class="label">High</span>
                                <span class="amount">£${highFormatted}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="salary-meta">
                        <span class="location">
                            <i class="fas fa-map-marker-alt"></i>
                            ${escapeHtml(job.location)}
                        </span>
                        <span class="sector">
                            <i class="fas fa-industry"></i>
                            ${escapeHtml(job.sector)}
                        </span>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        salaryResults.innerHTML = html;
    }
    
    function displayNoResults() {
        resultsTitle.textContent = 'No Results Found';
        resultsSubtitle.textContent = 'Try adjusting your search criteria';
        salaryResults.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>No salary data found</h4>
                <p>We couldn't find salary data for the selected criteria. Try:</p>
                <ul>
                    <li>Selecting a different sector or location</li>
                    <li>Removing the experience level filter</li>
                    <li>Checking our popular searches above</li>
                </ul>
            </div>
        `;
    }
    
    function displayError() {
        resultsTitle.textContent = 'Error Loading Data';
        resultsSubtitle.textContent = 'Please try again';
        salaryResults.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h4>Something went wrong</h4>
                <p>We encountered an error while loading the salary data. Please try again.</p>
            </div>
        `;
    }
    
    function formatSalary(amount) {
        return new Intl.NumberFormat('en-GB').format(amount);
    }
    
    function getExperienceLabel(level) {
        const labels = {
            'entry': 'Entry Level',
            'mid': 'Mid Level',
            'senior': 'Senior Level',
            'executive': 'Executive Level'
        };
        return labels[level] || level;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<style>
/* Salary Guide Styles */
.salary-guide-container {
    background: #f8f9fa;
    min-height: 100vh;
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.search-section {
    padding: 3rem 0;
}

.search-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    max-width: 800px;
    margin: 0 auto;
}

.salary-search-form .form-label {
    font-weight: 600;
    color: #1a3353;
}

.salary-search-form .form-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.2s ease;
}

.salary-search-form .form-select:focus {
    border-color: #ffd700;
    box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
}

.btn-primary {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    border: none;
    color: #1a3353;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.results-section {
    padding: 3rem 0;
}

.results-header {
    text-align: center;
    margin-bottom: 3rem;
}

.results-header h2 {
    color: #1a3353;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.salary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.salary-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.salary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border-color: #ffd700;
}

.salary-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.job-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a3353;
    margin: 0;
}

.experience-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.experience-entry {
    background: #e3f2fd;
    color: #1976d2;
}

.experience-mid {
    background: #fff3e0;
    color: #f57c00;
}

.experience-senior {
    background: #e8f5e8;
    color: #388e3c;
}

.experience-executive {
    background: #fce4ec;
    color: #c2185b;
}

.salary-range {
    margin-bottom: 1.5rem;
}

.range-bar {
    position: relative;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.range-fill {
    position: absolute;
    top: 0;
    height: 100%;
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    border-radius: 4px;
}

.range-marker {
    position: absolute;
    top: -8px;
    width: 4px;
    height: 24px;
    background: #1a3353;
    border-radius: 2px;
    transform: translateX(-50%);
}

.range-marker.average {
    background: #dc3545;
}

.marker-label {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: #1a3353;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    white-space: nowrap;
}

.salary-figures {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.salary-item {
    text-align: center;
}

.salary-item .label {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.salary-item .amount {
    display: block;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a3353;
}

.salary-item.low .amount {
    color: #28a745;
}

.salary-item.average .amount {
    color: #dc3545;
}

.salary-item.high .amount {
    color: #007bff;
}

.salary-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #6c757d;
}

.salary-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.popular-searches {
    padding: 3rem 0;
    background: white;
}

.section-title {
    text-align: center;
    color: #1a3353;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 2rem;
}

.popular-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.popular-item {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.popular-item:hover {
    border-color: #ffd700;
    background: #fff3cd;
    transform: translateY(-2px);
}

.popular-item i {
    font-size: 1.5rem;
    color: #ffd700;
    margin-bottom: 0.5rem;
    display: block;
}

.popular-item span {
    font-weight: 500;
    color: #1a3353;
}

.info-section {
    padding: 3rem 0;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    height: 100%;
}

.info-card h4 {
    color: #1a3353;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.info-card ul {
    margin: 0;
    padding-left: 1.2rem;
}

.info-card li {
    margin-bottom: 0.5rem;
    color: #495057;
}

.no-results,
.error-message {
    text-align: center;
    padding: 3rem 1rem;
}

.no-results h4,
.error-message h4 {
    color: #1a3353;
    margin-bottom: 1rem;
}

.no-results ul {
    text-align: left;
    max-width: 400px;
    margin: 1rem auto;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .salary-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .salary-figures {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .popular-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer_main.php'; ?> 