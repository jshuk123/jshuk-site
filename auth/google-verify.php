<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Google token verification and login handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Test endpoint for debugging
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Google verification endpoint is working',
        'client_id' => '718581742318-e4q3putg0b10e08eab4ma2sr9urbqb31.apps.googleusercontent.com',
        'domain' => $_SERVER['HTTP_HOST'],
        'protocol' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http'
    ]);
    exit;
}

header('Content-Type: application/json');

// Get posted JSON
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['credential'] ?? '';
if (!$token) {
    error_log("Google login: No token provided in request");
    echo json_encode(['success' => false, 'message' => 'No token provided.']);
    exit;
}

// Log the attempt for debugging
error_log("Google sign-in attempt with token: " . substr($token, 0, 20) . "...");

// Verify token with Google
$client_id = '718581742318-e4q3putg0b10e08eab4ma2sr9urbqb31.apps.googleusercontent.com';
$verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
$ch = curl_init($verify_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$google_response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    error_log("Google API curl error: " . $curl_error);
    echo json_encode(['success' => false, 'message' => 'Failed to verify with Google.']);
    exit;
}

$google_data = json_decode($google_response, true);

if (!isset($google_data['email']) || $google_data['aud'] !== $client_id) {
    error_log("Google token verification failed: " . json_encode($google_data));
    error_log("Expected client_id: " . $client_id . ", received aud: " . ($google_data['aud'] ?? 'null'));
    echo json_encode(['success' => false, 'message' => 'Invalid Google token.']);
    exit;
}

$email = $google_data['email'];
$first_name = $google_data['given_name'] ?? '';
$last_name = $google_data['family_name'] ?? '';
$google_id = $google_data['sub'];

error_log("Google sign-in successful for email: " . $email);

// Check if user exists
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // User exists, log them in
    // Note: Removed is_active check since this field doesn't exist in current schema
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['is_admin'] = ($user['role'] === 'admin');

    // Check subscription status - handle case where subscription tables don't exist
    try {
        $subscription = getUserSubscription($user['id']);
        if (!$subscription && $user['role'] !== 'admin') {
            echo json_encode(['success' => true, 'redirect' => '/payment/subscription.php']);
            exit;
        }
        if ($subscription) {
            $_SESSION['subscription'] = [
                'plan_id' => $subscription['plan_id'],
                'plan_name' => $subscription['name'],
                'status' => $subscription['status'],
                'current_period_end' => $subscription['current_period_end']
            ];
        }
    } catch (PDOException $e) {
        // If subscription tables don't exist, just log the user in without subscription check
        error_log("Subscription tables not found, skipping subscription check: " . $e->getMessage());
    }
    
    error_log("Existing user logged in via Google: " . $user['id']);
    echo json_encode(['success' => true, 'redirect' => '/index.php']);
    exit;
} else {
    // Create new user
    $username = explode('@', $email)[0];
    $random_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');
    
    // Updated to match current users table schema (removed is_active field)
    $insert = $pdo->prepare('INSERT INTO users (username, password, email, first_name, last_name, role, created_at, updated_at, email_verified) VALUES (?, ?, ?, ?, ?, "user", ?, ?, 1)');
    $insert->execute([
        $username,
        $random_password,
        $email,
        $first_name,
        $last_name,
        $now,
        $now
    ]);
    $user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user';
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    $_SESSION['is_admin'] = false;
    error_log("New user created via Google: " . $user_id);
    
    // Check if subscription tables exist before redirecting to subscription page
    try {
        $pdo->query("SELECT 1 FROM subscription_plans LIMIT 1");
        echo json_encode(['success' => true, 'redirect' => '/payment/subscription.php']);
    } catch (PDOException $e) {
        // If subscription tables don't exist, redirect to dashboard instead
        error_log("Subscription tables not found, redirecting to dashboard: " . $e->getMessage());
        echo json_encode(['success' => true, 'redirect' => '/index.php']);
    }
    exit;
} 