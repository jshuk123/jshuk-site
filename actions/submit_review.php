<?php
/**
 * Contact Form Submission Handler
 * Processes contact form submissions from business pages
 */

// Start session and include necessary files
session_start();
require_once '../config/config.php';
require_once '../config/db_connect.php';
require_once '../includes/mail_functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate required fields
$required_fields = ['name', 'email', 'subject', 'message', 'business_id', 'business_name'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    exit;
}

// Sanitize and validate input
$name = trim($_POST['name']);
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject']);
$message = trim($_POST['message']);
$business_id = (int)$_POST['business_id'];
$business_name = trim($_POST['business_name']);
$newsletter = isset($_POST['newsletter']) && $_POST['newsletter'] === 'on';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate business exists
try {
    $business_stmt = $pdo->prepare("SELECT id, business_name, user_id FROM businesses WHERE id = ? AND biz_status = 'active'");
    $business_stmt->execute([$business_id]);
    $business = $business_stmt->fetch();
    
    if (!$business) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Business not found']);
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in submit_contact.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}

// Get business owner email
$owner_email = '';
try {
    $owner_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $owner_stmt->execute([$business['user_id']]);
    $owner_email = $owner_stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching owner email: " . $e->getMessage());
}

// Store contact inquiry in database
try {
    $insert_stmt = $pdo->prepare("
        INSERT INTO contact_inquiries (
            business_id, 
            name, 
            email, 
            phone, 
            subject, 
            message, 
            newsletter_subscription,
            ip_address,
            user_agent,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $insert_stmt->execute([
        $business_id,
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $newsletter ? 1 : 0,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);
    
    $inquiry_id = $pdo->lastInsertId();
    
} catch (PDOException $e) {
    error_log("Error storing contact inquiry: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving your message']);
    exit;
}

// Send email notifications
$email_sent = false;
$admin_notified = false;

try {
    // Email to business owner
    if ($owner_email) {
        $owner_subject = "New Contact Inquiry: " . $subject;
        $owner_message = "
            <h2>New Contact Inquiry</h2>
            <p><strong>From:</strong> {$name} ({$email})</p>
            <p><strong>Phone:</strong> " . ($phone ?: 'Not provided') . "</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            <hr>
            <p><small>This inquiry was sent from your business page on JShuk.com</small></p>
        ";
        
        $email_sent = sendEmail($owner_email, $owner_subject, $owner_message);
    }
    
    // Email to admin
    $admin_subject = "New Contact Inquiry - {$business_name}";
    $admin_message = "
        <h2>New Contact Inquiry</h2>
        <p><strong>Business:</strong> {$business_name}</p>
        <p><strong>From:</strong> {$name} ({$email})</p>
        <p><strong>Phone:</strong> " . ($phone ?: 'Not provided') . "</p>
        <p><strong>Subject:</strong> {$subject}</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($message)) . "</p>
        <hr>
        <p><small>Inquiry ID: {$inquiry_id}</small></p>
    ";
    
    $admin_notified = sendEmail(ADMIN_EMAIL, $admin_subject, $admin_message);
    
} catch (Exception $e) {
    error_log("Error sending contact emails: " . $e->getMessage());
}

// Handle newsletter subscription
if ($newsletter) {
    try {
        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $check_stmt->execute([$email]);
        
        if (!$check_stmt->fetch()) {
            // Add to newsletter
            $newsletter_stmt = $pdo->prepare("
                INSERT INTO newsletter_subscribers (email, name, source, created_at) 
                VALUES (?, ?, 'contact_form', NOW())
            ");
            $newsletter_stmt->execute([$email, $name]);
        }
    } catch (PDOException $e) {
        error_log("Error handling newsletter subscription: " . $e->getMessage());
    }
}

// Log the contact submission
$log_message = date('Y-m-d H:i:s') . " - Contact inquiry from {$name} ({$email}) for business {$business_name} (ID: {$business_id})";
file_put_contents('../logs/contact_submissions.log', $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Thank you! Your message has been sent successfully.',
    'inquiry_id' => $inquiry_id,
    'email_sent' => $email_sent,
    'admin_notified' => $admin_notified
]); 