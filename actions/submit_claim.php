<?php
require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Invalid request');
}

$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $required_fields = ['post_id', 'claimant_name', 'simanim', 'claim_description', 'claim_date', 'contact_email'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill in all required fields.");
        }
    }
    
    // Validate post ID
    $post_id = (int)$_POST['post_id'];
    if ($post_id <= 0) {
        throw new Exception("Invalid post ID.");
    }
    
    // Validate email
    if (!filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address.");
    }
    
    // Validate date
    $claim_date = DateTime::createFromFormat('Y-m-d', $_POST['claim_date']);
    if (!$claim_date) {
        throw new Exception("Invalid date format.");
    }
    
    // Check if post exists and is active
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection failed.");
    }
    
    $stmt = $pdo->prepare("SELECT id, post_type, title, contact_email, contact_phone, contact_whatsapp, 
                                  hide_contact_until_verified, user_id 
                           FROM lostfound_posts 
                           WHERE id = ? AND status = 'active'");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        throw new Exception("Post not found or no longer active.");
    }
    
    // Check if user has already submitted a claim for this post
    $stmt = $pdo->prepare("SELECT id FROM lostfound_claims 
                           WHERE post_id = ? AND contact_email = ? AND status != 'rejected'");
    $stmt->execute([$post_id, $_POST['contact_email']]);
    if ($stmt->fetch()) {
        throw new Exception("You have already submitted a claim for this item.");
    }
    
    // Prepare claim data
    $claim_data = [
        'post_id' => $post_id,
        'claimant_name' => trim($_POST['claimant_name']),
        'simanim' => trim($_POST['simanim']),
        'claim_description' => trim($_POST['claim_description']),
        'claim_date' => $_POST['claim_date'],
        'contact_email' => trim($_POST['contact_email']),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'status' => 'pending'
    ];
    
    // Insert claim into database
    $columns = implode(', ', array_keys($claim_data));
    $placeholders = ':' . implode(', :', array_keys($claim_data));
    
    $query = "INSERT INTO lostfound_claims ({$columns}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($query);
    $stmt->execute($claim_data);
    
    $claim_id = $pdo->lastInsertId();
    
    // Send email notification to post owner
    if ($post['contact_email']) {
        sendClaimNotification($post, $claim_data, $claim_id);
    }
    
    // Send confirmation email to claimant
    sendClaimConfirmation($claim_data, $post);
    
    $response = [
        'success' => true,
        'message' => 'Your claim has been submitted successfully! The item owner will review your simanim and contact you if it matches.',
        'claim_id' => $claim_id
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

/**
 * Send email notification to post owner about new claim
 */
function sendClaimNotification($post, $claim_data, $claim_id) {
    $subject = "New Claim for Your {$post['post_type']} Item";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a3353; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .claim-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #ffd000; }
            .btn { display: inline-block; padding: 10px 20px; background: #ffd000; color: #333; text-decoration: none; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Claim Submitted</h2>
                <p>Someone thinks they found your {$post['post_type']} item!</p>
            </div>
            
            <div class='content'>
                <h3>Item Details:</h3>
                <p><strong>Title:</strong> {$post['title']}</p>
                
                <h3>Claim Details:</h3>
                <div class='claim-details'>
                    <p><strong>Claimant Name:</strong> {$claim_data['claimant_name']}</p>
                    <p><strong>Claim Date:</strong> {$claim_data['claim_date']}</p>
                    <p><strong>Contact Email:</strong> {$claim_data['contact_email']}</p>
                    " . ($claim_data['contact_phone'] ? "<p><strong>Contact Phone:</strong> {$claim_data['contact_phone']}</p>" : "") . "
                </div>
                
                <h3>Claim Description:</h3>
                <p>" . nl2br(htmlspecialchars($claim_data['claim_description'])) . "</p>
                
                <h3>Simanim (Identifying Signs):</h3>
                <p>" . nl2br(htmlspecialchars($claim_data['simanim'])) . "</p>
                
                <p><strong>Please review the simanim carefully. If they match your item, you can approve the claim and contact the claimant.</strong></p>
                
                <p>
                    <a href='https://jshuk.com/admin/lostfound.php?action=review&claim_id={$claim_id}' class='btn'>
                        Review Claim
                    </a>
                </p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message from JShuk Lost & Found system.</p>
                <p>If you have any questions, please contact support@jshuk.com</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using PHPMailer or mail() function
    sendEmail($post['contact_email'], $subject, $message);
}

/**
 * Send confirmation email to claimant
 */
function sendClaimConfirmation($claim_data, $post) {
    $subject = "Claim Submitted - {$post['title']}";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #28a745; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Claim Submitted Successfully</h2>
                <p>Your claim has been received and is under review</p>
            </div>
            
            <div class='content'>
                <h3>Claim Details:</h3>
                <div class='info-box'>
                    <p><strong>Item:</strong> {$post['title']}</p>
                    <p><strong>Your Name:</strong> {$claim_data['claimant_name']}</p>
                    <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                </div>
                
                <h3>What happens next?</h3>
                <ol>
                    <li>The item owner will review your simanim (identifying signs)</li>
                    <li>If the simanim match, they will contact you</li>
                    <li>You may be asked to provide additional identifying information</li>
                    <li>Once verified, arrangements will be made for return</li>
                </ol>
                
                <div class='info-box'>
                    <p><strong>Important:</strong> Please keep this email for your records. The item owner may contact you at: {$claim_data['contact_email']}</p>
                </div>
                
                <p>Thank you for using JShuk's Lost & Found system!</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message from JShuk Lost & Found system.</p>
                <p>If you have any questions, please contact support@jshuk.com</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using PHPMailer or mail() function
    sendEmail($claim_data['contact_email'], $subject, $message);
}

/**
 * Send email using available method
 */
function sendEmail($to, $subject, $message) {
    // Check if PHPMailer is available
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_USERNAME, 'JShuk Lost & Found');
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            if (APP_DEBUG) {
                error_log("Email sending failed: " . $e->getMessage());
            }
            return false;
        }
    } else {
        // Fallback to mail() function
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: JShuk Lost & Found <noreply@jshuk.com>" . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
}
?> 