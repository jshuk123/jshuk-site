<?php
session_start();
require_once '../config/config.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to create job alerts',
        'action' => 'login_required'
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the search criteria from the request
$sector_id = $_POST['sector_id'] ?? null;
$location = $_POST['location'] ?? null;
$job_type = $_POST['job_type'] ?? null;
$keywords = $_POST['keywords'] ?? null;
$name = $_POST['name'] ?? 'Job Alert';
$email_frequency = $_POST['email_frequency'] ?? 'daily';

// Validate email frequency
$valid_frequencies = ['daily', 'weekly', 'monthly'];
if (!in_array($email_frequency, $valid_frequencies)) {
    $email_frequency = 'daily';
}

// Validate job type if provided
if ($job_type) {
    $valid_job_types = ['full-time', 'part-time', 'contract', 'temporary', 'internship'];
    if (!in_array($job_type, $valid_job_types)) {
        $job_type = null;
    }
}

// Validate sector_id if provided
if ($sector_id && !is_numeric($sector_id)) {
    $sector_id = null;
}

// Sanitize inputs
$name = trim(filter_var($name, FILTER_SANITIZE_STRING));
$location = trim(filter_var($location, FILTER_SANITIZE_STRING));
$keywords = trim(filter_var($keywords, FILTER_SANITIZE_STRING));

// Check if at least one search criteria is provided
if (empty($sector_id) && empty($location) && empty($job_type) && empty($keywords)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please provide at least one search criteria (sector, location, job type, or keywords)'
    ]);
    exit;
}

try {
    // First, check if the job_alerts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'job_alerts'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Job alerts system is not set up yet. Please contact support.',
            'error' => 'job_alerts_table_missing'
        ]);
        exit;
    }
    
    // Check if a similar alert already exists for this user
    $stmt = $pdo->prepare("
        SELECT id FROM job_alerts 
        WHERE user_id = ? 
        AND sector_id = ? 
        AND location = ? 
        AND job_type = ? 
        AND keywords = ?
        AND is_active = 1
    ");
    $stmt->execute([$user_id, $sector_id, $location, $job_type, $keywords]);
    $existing_alert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_alert) {
        echo json_encode([
            'success' => false,
            'message' => 'You already have an active job alert with these criteria',
            'alert_id' => $existing_alert['id']
        ]);
        exit;
    }
    
    // Create the job alert
    $stmt = $pdo->prepare("
        INSERT INTO job_alerts (user_id, name, sector_id, location, job_type, keywords, email_frequency)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $name, $sector_id, $location, $job_type, $keywords, $email_frequency]);
    
    $alert_id = $pdo->lastInsertId();
    
    // Build a description of the alert criteria
    $criteria = [];
    if ($sector_id) {
        $stmt = $pdo->prepare("SELECT name FROM job_sectors WHERE id = ?");
        $stmt->execute([$sector_id]);
        $sector_name = $stmt->fetchColumn();
        if ($sector_name) {
            $criteria[] = "Sector: $sector_name";
        }
    }
    if ($location) {
        $criteria[] = "Location: $location";
    }
    if ($job_type) {
        $criteria[] = "Job Type: " . ucfirst(str_replace('-', ' ', $job_type));
    }
    if ($keywords) {
        $criteria[] = "Keywords: $keywords";
    }
    
    $criteria_text = implode(', ', $criteria);
    
    echo json_encode([
        'success' => true,
        'message' => 'Job alert created successfully',
        'alert_id' => $alert_id,
        'criteria' => $criteria_text,
        'frequency' => ucfirst($email_frequency)
    ]);
    
} catch (PDOException $e) {
    error_log("Create job alert error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the job alert',
        'error' => 'database_error',
        'details' => $e->getMessage()
    ]);
} 