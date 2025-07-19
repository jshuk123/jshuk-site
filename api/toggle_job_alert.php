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
        'message' => 'Please log in to manage job alerts'
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
$alert_id = $_POST['alert_id'] ?? null;
$is_active = $_POST['is_active'] ?? null;

if (!$alert_id || !is_numeric($alert_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid alert ID'
    ]);
    exit;
}

if (!in_array($is_active, ['0', '1'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
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
    
    // Check if the alert belongs to the user
    $stmt = $pdo->prepare("SELECT id, name FROM job_alerts WHERE id = ? AND user_id = ?");
    $stmt->execute([$alert_id, $user_id]);
    $alert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$alert) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Job alert not found'
        ]);
        exit;
    }
    
    // Update the alert status
    $stmt = $pdo->prepare("UPDATE job_alerts SET is_active = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$is_active, $alert_id, $user_id]);
    
    $status_text = $is_active ? 'activated' : 'paused';
    
    echo json_encode([
        'success' => true,
        'message' => "Job alert '{$alert['name']}' has been $status_text",
        'is_active' => (bool)$is_active
    ]);
    
} catch (PDOException $e) {
    error_log("Toggle job alert error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the job alert',
        'error' => 'database_error',
        'details' => $e->getMessage()
    ]);
} 