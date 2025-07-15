<?php
/**
 * Volunteer Post Form
 * Submit new volunteer opportunities
 */

require_once 'config/config.php';
require_once 'includes/volunteer_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/auth/login.php?redirect=' . urlencode('/volunteer_post.php'));
}

$user_id = getCurrentUserId();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        // Validate and sanitize input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $frequency = $_POST['frequency'] ?? 'one_time';
        $date_needed = $_POST['date_needed'] ?? '';
        $time_needed = $_POST['time_needed'] ?? '';
        $chessed_hours = intval($_POST['chessed_hours'] ?? 0);
        $urgent = isset($_POST['urgent']) ? 1 : 0;
        $contact_method = $_POST['contact_method'] ?? 'internal';
        $contact_info = trim($_POST['contact_info'] ?? '');
        $tags = $_POST['tags'] ?? [];
        
        // Validation
        $errors = [];
        
        if (empty($title)) {
            $errors[] = 'Title is required';
        } elseif (strlen($title) > 255) {
            $errors[] = 'Title must be less than 255 characters';
        }
        
        if (empty($description)) {
            $errors[] = 'Description is required';
        }
        
        if (empty($summary)) {
            $errors[] = 'Summary is required';
        } elseif (strlen($summary) > 500) {
            $errors[] = 'Summary must be less than 500 characters';
        }
        
        if (empty($location)) {
            $errors[] = 'Location is required';
        }
        
        if (!in_array($frequency, ['one_time', 'weekly', 'monthly', 'flexible'])) {
            $errors[] = 'Invalid frequency selected';
        }
        
        if ($contact_method !== 'internal' && empty($contact_info)) {
            $errors[] = 'Contact information is required when not using internal messaging';
        }
        
        if (empty($errors)) {
            // Prepare data
            $data = [
                'title' => $title,
                'description' => $description,
                'summary' => $summary,
                'location' => $location,
                'frequency' => $frequency,
                'date_needed' => $date_needed ?: null,
                'time_needed' => $time_needed ?: null,
                'chessed_hours' => $chessed_hours,
                'urgent' => $urgent,
                'contact_method' => $contact_method,
                'contact_info' => $contact_info,
                'tags' => json_encode($tags),
                'preferred_times' => json_encode($_POST['preferred_times'] ?? []),
                'posted_by' => $user_id
            ];
            
            // Create opportunity
            if (createVolunteerOpportunity($data)) {
                $success_message = 'Your volunteer opportunity has been submitted successfully! It will be reviewed by our team and published soon.';
                
                // Clear form data
                $_POST = [];
            } else {
                $error_message = 'There was an error submitting your opportunity. Please try again.';
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Get volunteer types for tag suggestions
$volunteer_types = getVolunteerTypes();

// SEO Meta
$page_title = "Post Volunteer Opportunity - JShuk Volunteer Hub";
$page_description = "Share your volunteer opportunity with the Jewish community. Post requests for help with tutoring, elderly care, food delivery, and more.";
$page_keywords = "post volunteer opportunity, chesed, Jewish community, help needed";

// Include header
include 'includes/header_main.php';
?>

<!-- Page Header -->
<section class="page-header bg-primary text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fa fa-plus-circle"></i> Post Volunteer Opportunity
                </h1>
                <p class="lead mb-0">Share your need for help with the Jewish community</p>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="/volunteer.php" class="btn btn-outline-light">
                    <i class="fa fa-arrow-left"></i> Back to Volunteer Hub
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Form Section -->
<section class="volunteer-form py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fa fa-edit"></i> Opportunity Details
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/volunteer_post.php" id="volunteerForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <!-- Basic Information -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fa fa-info-circle"></i> Basic Information
                                </h5>
                                
                                <div class="form-group">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo h($_POST['title'] ?? ''); ?>" 
                                           placeholder="e.g., Homework Help for GCSE Student" required>
                                    <small class="form-text text-muted">A clear, descriptive title for your opportunity</small>
                                </div>

                                <div class="form-group">
                                    <label for="summary" class="form-label">Summary *</label>
                                    <textarea class="form-control" id="summary" name="summary" rows="2" 
                                              placeholder="Brief summary of what help is needed" required><?php echo h($_POST['summary'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">A short summary that appears in listings (max 500 characters)</small>
                                </div>

                                <div class="form-group">
                                    <label for="description" class="form-label">Full Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="6" 
                                              placeholder="Detailed description of the opportunity, requirements, and what volunteers can expect" required><?php echo h($_POST['description'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Provide detailed information about the opportunity</small>
                                </div>
                            </div>

                            <!-- Location & Timing -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fa fa-map-marker-alt"></i> Location & Timing
                                </h5>
                                
                                <div class="form-group">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo h($_POST['location'] ?? ''); ?>" 
                                           placeholder="e.g., Golders Green, Hendon, Stamford Hill" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="frequency" class="form-label">Frequency *</label>
                                            <select class="form-control" id="frequency" name="frequency" required>
                                                <option value="one_time" <?php echo ($_POST['frequency'] ?? '') === 'one_time' ? 'selected' : ''; ?>>One Time</option>
                                                <option value="weekly" <?php echo ($_POST['frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo ($_POST['frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                <option value="flexible" <?php echo ($_POST['frequency'] ?? '') === 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="chessed_hours" class="form-label">Estimated Hours</label>
                                            <input type="number" class="form-control" id="chessed_hours" name="chessed_hours" 
                                                   value="<?php echo h($_POST['chessed_hours'] ?? ''); ?>" 
                                                   min="0" max="100" placeholder="e.g., 2">
                                            <small class="form-text text-muted">Estimated hours per session/opportunity</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="date_needed" class="form-label">Date Needed</label>
                                            <input type="date" class="form-control" id="date_needed" name="date_needed" 
                                                   value="<?php echo h($_POST['date_needed'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="time_needed" class="form-label">Time Needed</label>
                                            <input type="time" class="form-control" id="time_needed" name="time_needed" 
                                                   value="<?php echo h($_POST['time_needed'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Preferred Times</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="preferred_times[]" value="morning" 
                                                       <?php echo in_array('morning', $_POST['preferred_times'] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Morning</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="preferred_times[]" value="afternoon" 
                                                       <?php echo in_array('afternoon', $_POST['preferred_times'] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Afternoon</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="preferred_times[]" value="evening" 
                                                       <?php echo in_array('evening', $_POST['preferred_times'] ?? []) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Evening</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Categories & Tags -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fa fa-tags"></i> Categories & Tags
                                </h5>
                                
                                <div class="form-group">
                                    <label class="form-label">Select Categories</label>
                                    <div class="row">
                                        <?php foreach ($volunteer_types as $key => $type): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $key; ?>" 
                                                           <?php echo in_array($key, $_POST['tags'] ?? []) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">
                                                        <i class="fa <?php echo $type['icon']; ?>"></i>
                                                        <?php echo $type['name']; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="custom_tags" class="form-label">Additional Tags</label>
                                    <input type="text" class="form-control" id="custom_tags" name="custom_tags" 
                                           value="<?php echo h($_POST['custom_tags'] ?? ''); ?>" 
                                           placeholder="e.g., gcse, maths, elderly, shabbat">
                                    <small class="form-text text-muted">Separate tags with commas</small>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fa fa-phone"></i> Contact Information
                                </h5>
                                
                                <div class="form-group">
                                    <label for="contact_method" class="form-label">Contact Method</label>
                                    <select class="form-control" id="contact_method" name="contact_method">
                                        <option value="internal" <?php echo ($_POST['contact_method'] ?? '') === 'internal' ? 'selected' : ''; ?>>Internal Messaging (Recommended)</option>
                                        <option value="email" <?php echo ($_POST['contact_method'] ?? '') === 'email' ? 'selected' : ''; ?>>Email</option>
                                        <option value="phone" <?php echo ($_POST['contact_method'] ?? '') === 'phone' ? 'selected' : ''; ?>>Phone</option>
                                        <option value="whatsapp" <?php echo ($_POST['contact_method'] ?? '') === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                                    </select>
                                    <small class="form-text text-muted">How volunteers should contact you</small>
                                </div>

                                <div class="form-group" id="contact_info_group" style="display: none;">
                                    <label for="contact_info" class="form-label">Contact Information</label>
                                    <input type="text" class="form-control" id="contact_info" name="contact_info" 
                                           value="<?php echo h($_POST['contact_info'] ?? ''); ?>" 
                                           placeholder="Your email, phone, or WhatsApp number">
                                </div>
                            </div>

                            <!-- Urgency -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fa fa-exclamation-triangle"></i> Urgency
                                </h5>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="urgent" name="urgent" value="1" 
                                               <?php echo isset($_POST['urgent']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="urgent">
                                            <i class="fa fa-exclamation-triangle text-danger"></i> 
                                            Mark as Urgent
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Only check this if the need is truly urgent and time-sensitive</small>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-paper-plane"></i> Submit Opportunity
                                </button>
                                <a href="/volunteer.php" class="btn btn-outline-secondary btn-lg ml-2">
                                    <i class="fa fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fa fa-question-circle"></i> Tips for a Great Post
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                <strong>Be specific:</strong> Include details about what help is needed
                            </li>
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                <strong>Set clear expectations:</strong> Mention time commitment and requirements
                            </li>
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                <strong>Use relevant tags:</strong> Help volunteers find your opportunity
                            </li>
                            <li class="mb-2">
                                <i class="fa fa-check text-success mr-2"></i>
                                <strong>Be honest about urgency:</strong> Only mark as urgent if truly needed
                            </li>
                            <li>
                                <i class="fa fa-check text-success mr-2"></i>
                                <strong>Respond promptly:</strong> Reply to interested volunteers quickly
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Volunteer Form CSS -->
<link rel="stylesheet" href="/css/pages/volunteer_post.css">

<!-- Form JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactMethod = document.getElementById('contact_method');
    const contactInfoGroup = document.getElementById('contact_info_group');
    const contactInfo = document.getElementById('contact_info');
    
    // Show/hide contact info field based on method
    function toggleContactInfo() {
        if (contactMethod.value === 'internal') {
            contactInfoGroup.style.display = 'none';
            contactInfo.removeAttribute('required');
        } else {
            contactInfoGroup.style.display = 'block';
            contactInfo.setAttribute('required', 'required');
        }
    }
    
    contactMethod.addEventListener('change', toggleContactInfo);
    toggleContactInfo(); // Initial state
    
    // Character counter for summary
    const summary = document.getElementById('summary');
    const summaryCounter = document.createElement('small');
    summaryCounter.className = 'form-text text-muted';
    summary.parentNode.appendChild(summaryCounter);
    
    function updateSummaryCounter() {
        const remaining = 500 - summary.value.length;
        summaryCounter.textContent = `${remaining} characters remaining`;
        summaryCounter.className = remaining < 50 ? 'form-text text-warning' : 'form-text text-muted';
    }
    
    summary.addEventListener('input', updateSummaryCounter);
    updateSummaryCounter(); // Initial state
    
    // Form validation
    const form = document.getElementById('volunteerForm');
    form.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const summary = document.getElementById('summary').value.trim();
        const location = document.getElementById('location').value.trim();
        
        if (!title || !description || !summary || !location) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        if (summary.length > 500) {
            e.preventDefault();
            alert('Summary must be 500 characters or less.');
            return false;
        }
    });
});
</script>

<?php include 'includes/footer_main.php'; ?> 