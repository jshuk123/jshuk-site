<?php
session_start();
require_once 'config/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'You must be logged in to post a job.';
    header('Location: /auth/login.php?redirect=/submit_job.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve and clear session messages
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['error_message'], $_SESSION['success_message'], $_SESSION['form_data']);

// Fetch job sectors for dropdown
$sectors = $pdo->query("SELECT * FROM job_sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Post a Job";
$page_css = "submit_job.css";
include 'includes/header_main.php';
?>

<div class="container main-content">
    <div class="submit-job-header">
        <h1>Post a Job Opportunity</h1>
        <p class="subtitle">Help our community grow by sharing job opportunities</p>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="alert-content">
                <i class="fa fa-exclamation-circle"></i>
                <div class="alert-text">
                    <?php foreach (explode('<br>', $error_message) as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="alert-content">
                <i class="fa fa-check-circle"></i>
                <div class="alert-text"><?= htmlspecialchars($success_message) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="post" action="process_job.php" class="job-form needs-validation" novalidate autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Job Details Section -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fa fa-briefcase"></i>
                Job Details
            </h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="job_title" class="form-label">Job Title <span class="required">*</span></label>
                    <input type="text" class="form-control" id="job_title" name="job_title"
                           value="<?= htmlspecialchars($form_data['job_title'] ?? '') ?>" required maxlength="100" minlength="3" autofocus
                           placeholder="e.g. Senior Web Developer, Marketing Manager, etc.">
                    <div class="form-text">Please enter a clear and descriptive job title (max 100 characters).</div>
                    <div class="invalid-feedback">Please enter a job title (min 3, max 100 characters).</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="company" class="form-label">Company / Organisation</label>
                    <input type="text" class="form-control" id="company" name="company"
                           value="<?= htmlspecialchars($form_data['company'] ?? '') ?>" maxlength="100"
                           placeholder="e.g. JShuk Ltd, Community Organization, etc.">
                    <div class="form-text">Leave blank if posting as an individual.</div>
                </div>
                
                <div class="form-group">
                    <label for="location" class="form-label">Job Location <span class="required">*</span></label>
                    <input type="text" class="form-control" id="location" name="location"
                           value="<?= htmlspecialchars($form_data['location'] ?? '') ?>" required maxlength="100"
                           placeholder="e.g. Manchester, Remote, Golders Green, etc.">
                    <div class="form-text">Specify the location where the job will be performed.</div>
                    <div class="invalid-feedback">Please enter a job location.</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="job_type" class="form-label">Job Type <span class="required">*</span></label>
                    <select class="form-select" id="job_type" name="job_type" required>
                        <option value="">Select job type</option>
                        <option value="full-time" <?= (isset($form_data['job_type']) && $form_data['job_type'] == 'full-time') ? 'selected' : '' ?>>Full-time</option>
                        <option value="part-time" <?= (isset($form_data['job_type']) && $form_data['job_type'] == 'part-time') ? 'selected' : '' ?>>Part-time</option>
                        <option value="contract" <?= (isset($form_data['job_type']) && $form_data['job_type'] == 'contract') ? 'selected' : '' ?>>Contract</option>
                        <option value="temporary" <?= (isset($form_data['job_type']) && $form_data['job_type'] == 'temporary') ? 'selected' : '' ?>>Temporary</option>
                        <option value="internship" <?= (isset($form_data['job_type']) && $form_data['job_type'] == 'internship') ? 'selected' : '' ?>>Internship</option>
                    </select>
                    <div class="invalid-feedback">Please select a job type.</div>
                </div>
                
                <div class="form-group">
                    <label for="sector_id" class="form-label">Job Sector <span class="required">*</span></label>
                    <select class="form-select" id="sector_id" name="sector_id" required>
                        <option value="">Select a sector</option>
                        <?php foreach ($sectors as $sector): ?>
                            <option value="<?= $sector['id'] ?>" <?= (isset($form_data['sector_id']) && $form_data['sector_id'] == $sector['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sector['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a job sector.</div>
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Job Description <span class="required">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="6" required maxlength="2000" minlength="50"
                          placeholder="Describe the role, responsibilities, duties, and what the job entails. Be specific about what the candidate will be doing on a daily basis."><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                <div class="form-text">Please provide a detailed description of the role (50-2000 characters).</div>
                <div class="invalid-feedback">Please enter a job description (min 50, max 2000 characters).</div>
            </div>
        </div>

        <!-- Requirements & Skills Section -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fa fa-graduation-cap"></i>
                Requirements & Skills
            </h2>
            
            <div class="form-group">
                <label for="requirements" class="form-label">Job Requirements</label>
                <textarea class="form-control" id="requirements" name="requirements" rows="4" maxlength="1000"
                          placeholder="List the qualifications, experience, and requirements needed for this position. e.g., 'Minimum 2 years experience in web development', 'Must have strong communication skills', etc."><?= htmlspecialchars($form_data['requirements'] ?? '') ?></textarea>
                <div class="form-text">Specify the qualifications, experience, and requirements needed.</div>
            </div>

            <div class="form-group">
                <label for="skills" class="form-label">Skills & Qualifications</label>
                <textarea class="form-control" id="skills" name="skills" rows="3" maxlength="500"
                          placeholder="List specific skills, technologies, or qualifications. e.g., 'PHP, JavaScript, MySQL', 'Customer service experience', 'Driving license required', etc."><?= htmlspecialchars($form_data['skills'] ?? '') ?></textarea>
                <div class="form-text">List specific skills, technologies, or qualifications needed.</div>
            </div>
        </div>

        <!-- Compensation & Benefits Section -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fa fa-money-bill"></i>
                Compensation & Benefits
            </h2>
            
            <div class="form-group">
                <label for="salary" class="form-label">Salary / Compensation</label>
                <input type="text" class="form-control" id="salary" name="salary"
                       value="<?= htmlspecialchars($form_data['salary'] ?? '') ?>" maxlength="200"
                       placeholder="e.g. £30,000 - £40,000 per annum, £15 per hour, Competitive salary, etc.">
                <div class="form-text">Specify the salary range, hourly rate, or indicate if salary is competitive/negotiable.</div>
            </div>

            <div class="form-group">
                <label for="benefits" class="form-label">Benefits & Perks</label>
                <textarea class="form-control" id="benefits" name="benefits" rows="3" maxlength="500"
                          placeholder="List any benefits, perks, or additional compensation. e.g., 'Health insurance', 'Flexible working hours', 'Training opportunities', 'Kosher lunch provided', etc."><?= htmlspecialchars($form_data['benefits'] ?? '') ?></textarea>
                <div class="form-text">Mention any benefits, perks, or additional compensation offered.</div>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fa fa-address-book"></i>
                Contact Information
            </h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_email" class="form-label">Contact Email <span class="required">*</span></label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                           value="<?= htmlspecialchars($form_data['contact_email'] ?? '') ?>" required maxlength="255
                           placeholder="e.g. jobs@company.com, hiring@organization.org">
                    <div class="form-text">This email will be used by applicants to contact you.</div>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                
                <div class="form-group">
                    <label for="contact_phone" class="form-label">Contact Phone</label>
                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone"
                           value="<?= htmlspecialchars($form_data['contact_phone'] ?? '') ?>" maxlength="20"
                           placeholder="e.g. 020 1234 5678, +44 20 1234 5678">
                    <div class="form-text">Optional phone number for applicants to contact you.</div>
                </div>
            </div>

            <div class="form-group">
                <label for="application_method" class="form-label">Preferred Application Method</label>
                <select class="form-select" id="application_method" name="application_method">
                    <option value="">Select preferred method</option>
                    <option value="email" <?= (isset($form_data['application_method']) && $form_data['application_method'] == 'email') ? 'selected' : '' ?>>Email</option>
                    <option value="phone" <?= (isset($form_data['application_method']) && $form_data['application_method'] == 'phone') ? 'selected' : '' ?>>Phone</option>
                    <option value="whatsapp" <?= (isset($form_data['application_method']) && $form_data['application_method'] == 'whatsapp') ? 'selected' : '' ?>>WhatsApp</option>
                    <option value="website" <?= (isset($form_data['application_method']) && $form_data['application_method'] == 'website') ? 'selected' : '' ?>>Company Website</option>
                    <option value="multiple" <?= (isset($form_data['application_method']) && $form_data['application_method'] == 'multiple') ? 'selected' : '' ?>>Multiple Methods</option>
                </select>
                <div class="form-text">How would you prefer applicants to contact you?</div>
            </div>
        </div>

        <!-- Cultural & Community Section -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fa fa-heart"></i>
                Cultural & Community Information
            </h2>
            
            <div class="form-group">
                <label class="form-label">Cultural Considerations (Optional)</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="kosher_environment" name="kosher_environment" value="1" 
                               <?= (isset($form_data['kosher_environment']) && $form_data['kosher_environment']) ? 'checked' : '' ?>>
                        <label for="kosher_environment">Kosher environment available</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="flexible_schedule" name="flexible_schedule" value="1"
                               <?= (isset($form_data['flexible_schedule']) && $form_data['flexible_schedule']) ? 'checked' : '' ?>>
                        <label for="flexible_schedule">Flexible schedule for religious commitments</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="community_focused" name="community_focused" value="1"
                               <?= (isset($form_data['community_focused']) && $form_data['community_focused']) ? 'checked' : '' ?>>
                        <label for="community_focused">Community-focused organization</label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="remote_friendly" name="remote_friendly" value="1"
                               <?= (isset($form_data['remote_friendly']) && $form_data['remote_friendly']) ? 'checked' : '' ?>>
                        <label for="remote_friendly">Remote work options available</label>
                    </div>
                </div>
                <div class="form-text">These options help job seekers find positions that align with their cultural and religious needs.</div>
            </div>

            <div class="form-group">
                <label for="additional_info" class="form-label">Additional Information</label>
                <textarea class="form-control" id="additional_info" name="additional_info" rows="3" maxlength="500"
                          placeholder="Any additional information about the role, company culture, or specific requirements that might be relevant to our community."><?= htmlspecialchars($form_data['additional_info'] ?? '') ?></textarea>
                <div class="form-text">Any other information that might be relevant to potential applicants.</div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="form-section submit-section">
            <div class="submit-info">
                <div class="info-item">
                    <i class="fa fa-info-circle"></i>
                    <span>Your job posting will be reviewed and published within 24 hours.</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-eye"></i>
                    <span>Job postings are visible to the entire JShuk community.</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-clock"></i>
                    <span>Job postings remain active for 30 days by default.</span>
                </div>
            </div>
            
            <div class="submit-buttons">
                <button type="submit" class="btn btn-primary btn-submit">
                    <i class="fa fa-paper-plane"></i>
                    Submit Job Posting
                </button>
                <a href="/recruitment.php" class="btn btn-outline-secondary">
                    <i class="fa fa-times"></i>
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<style>
/* Submit Job Form Styles */
.submit-job-header {
    text-align: center;
    margin-bottom: 2rem;
}

.submit-job-header h1 {
    color: #1e3a8a;
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.submit-job-header .subtitle {
    color: #6b7280;
    font-size: 1.1rem;
    margin: 0;
}

.alert {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
}

.alert-content {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.alert-content i {
    font-size: 1.2rem;
    margin-top: 0.1rem;
}

.alert-text {
    flex: 1;
}

.job-form {
    max-width: 800px;
    margin: 0 auto;
}

.form-section {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 2px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.section-title {
    color: #1e3a8a;
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.8rem;
}

.section-title i {
    color: #2563eb;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    color: #374151;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
}

.required {
    color: #dc2626;
}

.form-control, .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.8rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fff;
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-text {
    color: #6b7280;
    font-size: 0.9rem;
    margin-top: 0.3rem;
}

.invalid-feedback {
    color: #dc2626;
    font-size: 0.9rem;
    margin-top: 0.3rem;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 0.5rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #2563eb;
}

.checkbox-item label {
    color: #374151;
    font-weight: 500;
    margin: 0;
    cursor: pointer;
}

.submit-section {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border: 2px solid #2563eb;
}

.submit-info {
    margin-bottom: 2rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1rem;
    color: #6b7280;
    font-size: 0.95rem;
}

.info-item i {
    color: #2563eb;
    font-size: 1rem;
}

.submit-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1e3a8a 100%);
    color: #fff;
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    transform: translateY(-2px);
    color: #fff;
}

.btn-outline-secondary {
    background: transparent;
    border: 2px solid #6b7280;
    color: #6b7280;
}

.btn-outline-secondary:hover {
    background: #6b7280;
    color: #fff;
    transform: translateY(-2px);
}

.btn-submit {
    font-size: 1.1rem;
    padding: 1.2rem 2.5rem;
}

/* Responsive Design */
@media (max-width: 900px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .checkbox-group {
        grid-template-columns: 1fr;
    }
    
    .submit-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 600px) {
    .form-section {
        padding: 1.5rem;
    }
    
    .submit-job-header h1 {
        font-size: 1.8rem;
    }
    
    .section-title {
        font-size: 1.2rem;
    }
}
</style>

<script>
// Bootstrap client-side validation
(function () {
  'use strict';
  var forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();

// Character counter for textareas
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(function(textarea) {
        const maxLength = textarea.getAttribute('maxlength');
        const formText = textarea.parentNode.querySelector('.form-text');
        
        if (formText) {
            const counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.style.color = '#6b7280';
            counter.style.fontSize = '0.85rem';
            counter.style.marginTop = '0.3rem';
            counter.textContent = `0 / ${maxLength} characters`;
            
            textarea.parentNode.insertBefore(counter, formText.nextSibling);
            
            textarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                counter.textContent = `${currentLength} / ${maxLength} characters`;
                
                if (currentLength > maxLength * 0.9) {
                    counter.style.color = '#dc2626';
                } else {
                    counter.style.color = '#6b7280';
                }
            });
        }
    });
});
</script>

<?php include 'includes/footer_main.php'; ?> 