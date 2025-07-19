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
        'message' => 'Please log in to save jobs',
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

// Get the job ID from the request
$job_id = $_POST['job_id'] ?? null;
$action = $_POST['action'] ?? 'toggle'; // 'save', 'unsave', or 'toggle'

if (!$job_id || !is_numeric($job_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid job ID'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // First, check if the job exists and is active
    $stmt = $pdo->prepare("SELECT id, job_title FROM recruitment WHERE id = ? AND is_active = 1");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Job not found or no longer available'
        ]);
        exit;
    }
    
    // Check if the job is already saved
    $stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$user_id, $job_id]);
    $saved_job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_saved = !empty($saved_job);
    
    // Determine the action to take
    if ($action === 'save' || ($action === 'toggle' && !$is_saved)) {
        // Save the job
        if (!$is_saved) {
            $stmt = $pdo->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $job_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Job saved successfully',
                'is_saved' => true,
                'job_title' => $job['job_title']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Job already saved',
                'is_saved' => true,
                'job_title' => $job['job_title']
            ]);
        }
    } elseif ($action === 'unsave' || ($action === 'toggle' && $is_saved)) {
        // Unsave the job
        if ($is_saved) {
            $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
            $stmt->execute([$user_id, $job_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Job removed from saved jobs',
                'is_saved' => false,
                'job_title' => $job['job_title']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Job not in saved jobs',
                'is_saved' => false,
                'job_title' => $job['job_title']
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Save job error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving the job'
    ]);
} 