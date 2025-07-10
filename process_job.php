<?php
session_start();
require_once 'config/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    die('You must be logged in to post a job.');
}
$user_id = $_SESSION['user_id'];

// CSRF Protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token.');
}

// Input validation
$required_fields = ['job_title', 'description', 'location', 'job_type', 'sector_id', 'contact_email'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
    }
}

// Validate email format
if (!empty($_POST['contact_email']) && !filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Validate sector_id exists
if (!empty($_POST['sector_id'])) {
    try {
        $sector_stmt = $pdo->prepare("SELECT id FROM job_sectors WHERE id = ?");
        $sector_stmt->execute([$_POST['sector_id']]);
        if (!$sector_stmt->fetch()) {
            $errors[] = 'Invalid job sector selected.';
        }
    } catch (PDOException $e) {
        $errors[] = 'Error validating job sector.';
    }
}

// Sanitize inputs
$job_title = htmlspecialchars(trim($_POST['job_title']));
$job_description = htmlspecialchars(trim($_POST['description']));
$job_location = htmlspecialchars(trim($_POST['location']));
$company = htmlspecialchars(trim($_POST['company'] ?? ''));
$job_type = in_array($_POST['job_type'], ['full-time', 'part-time', 'contract', 'temporary', 'internship']) ? $_POST['job_type'] : 'full-time';
$sector_id = isset($_POST['sector_id']) && !empty($_POST['sector_id']) ? (int)$_POST['sector_id'] : null;

// New fields
$requirements = htmlspecialchars(trim($_POST['requirements'] ?? ''));
$skills = htmlspecialchars(trim($_POST['skills'] ?? ''));
$salary = htmlspecialchars(trim($_POST['salary'] ?? ''));
$benefits = htmlspecialchars(trim($_POST['benefits'] ?? ''));
$contact_email = htmlspecialchars(trim($_POST['contact_email']));
$contact_phone = htmlspecialchars(trim($_POST['contact_phone'] ?? ''));
$application_method = htmlspecialchars(trim($_POST['application_method'] ?? ''));
$additional_info = htmlspecialchars(trim($_POST['additional_info'] ?? ''));

// Cultural options (checkboxes)
$kosher_environment = isset($_POST['kosher_environment']) ? 1 : 0;
$flexible_schedule = isset($_POST['flexible_schedule']) ? 1 : 0;
$community_focused = isset($_POST['community_focused']) ? 1 : 0;
$remote_friendly = isset($_POST['remote_friendly']) ? 1 : 0;

// Validate field lengths
if (strlen($job_title) > 100) {
    $errors[] = 'Job title must be 100 characters or less.';
}

if (strlen($job_description) > 2000) {
    $errors[] = 'Job description must be 2000 characters or less.';
}

if (strlen($requirements) > 1000) {
    $errors[] = 'Requirements must be 1000 characters or less.';
}

if (strlen($skills) > 500) {
    $errors[] = 'Skills must be 500 characters or less.';
}

if (strlen($salary) > 200) {
    $errors[] = 'Salary must be 200 characters or less.';
}

if (strlen($benefits) > 500) {
    $errors[] = 'Benefits must be 500 characters or less.';
}

if (strlen($contact_phone) > 20) {
    $errors[] = 'Contact phone must be 20 characters or less.';
}

if (strlen($additional_info) > 500) {
    $errors[] = 'Additional information must be 500 characters or less.';
}

if (empty($errors)) {
    try {
        // First, let's check if we need to add new columns to the recruitment table
        $check_columns = $pdo->query("SHOW COLUMNS FROM recruitment LIKE 'requirements'");
        $has_requirements = $check_columns->rowCount() > 0;
        
        if (!$has_requirements) {
            // Add new columns to the recruitment table
            $alter_sql = "
                ALTER TABLE recruitment 
                ADD COLUMN requirements TEXT NULL AFTER job_description,
                ADD COLUMN skills TEXT NULL AFTER requirements,
                ADD COLUMN salary VARCHAR(200) NULL AFTER skills,
                ADD COLUMN benefits TEXT NULL AFTER salary,
                ADD COLUMN contact_phone VARCHAR(20) NULL AFTER contact_email,
                ADD COLUMN application_method VARCHAR(50) NULL AFTER contact_phone,
                ADD COLUMN additional_info TEXT NULL AFTER application_method,
                ADD COLUMN kosher_environment TINYINT(1) DEFAULT 0 AFTER additional_info,
                ADD COLUMN flexible_schedule TINYINT(1) DEFAULT 0 AFTER kosher_environment,
                ADD COLUMN community_focused TINYINT(1) DEFAULT 0 AFTER flexible_schedule,
                ADD COLUMN remote_friendly TINYINT(1) DEFAULT 0 AFTER community_focused,
                ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER remote_friendly,
                ADD COLUMN views_count INT DEFAULT 0 AFTER is_featured,
                ADD COLUMN applications_count INT DEFAULT 0 AFTER views_count
            ";
            $pdo->exec($alter_sql);
        }
        
        // Insert the job posting
        $stmt = $pdo->prepare("
            INSERT INTO recruitment (
                user_id, job_title, job_description, requirements, skills, salary, benefits,
                job_location, company, job_type, sector_id, contact_email, contact_phone,
                application_method, additional_info, kosher_environment, flexible_schedule,
                community_focused, remote_friendly, is_active, created_at, updated_at
            )
            VALUES (
                :user_id, :job_title, :job_description, :requirements, :skills, :salary, :benefits,
                :job_location, :company, :job_type, :sector_id, :contact_email, :contact_phone,
                :application_method, :additional_info, :kosher_environment, :flexible_schedule,
                :community_focused, :remote_friendly, 1, NOW(), NOW()
            )
        ");

        $stmt->execute([
            ':user_id' => $user_id,
            ':job_title' => $job_title,
            ':job_description' => $job_description,
            ':requirements' => $requirements,
            ':skills' => $skills,
            ':salary' => $salary,
            ':benefits' => $benefits,
            ':job_location' => $job_location,
            ':company' => $company,
            ':job_type' => $job_type,
            ':sector_id' => $sector_id,
            ':contact_email' => $contact_email,
            ':contact_phone' => $contact_phone,
            ':application_method' => $application_method,
            ':additional_info' => $additional_info,
            ':kosher_environment' => $kosher_environment,
            ':flexible_schedule' => $flexible_schedule,
            ':community_focused' => $community_focused,
            ':remote_friendly' => $remote_friendly
        ]);

        $job_id = $pdo->lastInsertId();

        // Log the job posting
        error_log("New job posted: ID {$job_id}, Title: {$job_title}, User: {$user_id}");

        // Send notification email to admin (optional)
        $admin_email = 'admin@jshuk.com'; // Replace with actual admin email
        $subject = "New Job Posted: {$job_title}";
        $message = "
            A new job has been posted on JShuk:
            
            Job Title: {$job_title}
            Company: {$company}
            Location: {$job_location}
            Posted by: User ID {$user_id}
            
            View job: " . $_SERVER['HTTP_HOST'] . "/job_view.php?id={$job_id}
        ";
        
        // Uncomment to enable admin notifications
        // mail($admin_email, $subject, $message);

        $_SESSION['success_message'] = 'Job listing submitted successfully! It will be reviewed and published within 24 hours.';
        header('Location: /recruitment.php');
        exit;
        
    } catch (PDOException $e) {
        error_log("Job Posting Error: " . $e->getMessage());
        $errors[] = 'A database error occurred. Please try again later.';
    }
}

// If there are errors, redirect back to the form
$_SESSION['error_message'] = implode('<br>', $errors);
$_SESSION['form_data'] = $_POST;
header('Location: /submit_job.php');
exit;