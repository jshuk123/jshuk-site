<?php
/**
 * Mail Functions
 * Provides email sending functionality using PHPMailer
 */

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @param string $from_name Sender name (optional)
 * @param string $from_email Sender email (optional)
 * @param array $attachments Array of file paths to attach (optional)
 * @return bool True if email sent successfully, false otherwise
 */
function sendEmail($to, $subject, $message, $from_name = null, $from_email = null, $attachments = []) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Set charset
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom($from_email ?? SMTP_FROM_EMAIL, $from_name ?? SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Add reply-to if different from from address
        if ($from_email && $from_email !== SMTP_FROM_EMAIL) {
            $mail->addReplyTo($from_email, $from_name);
        }
        
        // Attachments
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // Plain text version
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        
        // Log successful email
        $log_message = date('Y-m-d H:i:s') . " - Email sent successfully to {$to} - Subject: {$subject}";
        file_put_contents(__DIR__ . '/../logs/email_sent.log', $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        return true;
        
    } catch (Exception $e) {
        // Log email error
        $error_message = date('Y-m-d H:i:s') . " - Email error to {$to}: " . $e->getMessage();
        file_put_contents(__DIR__ . '/../logs/email_errors.log', $error_message . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a notification email to admin
 * 
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @param array $attachments Array of file paths to attach (optional)
 * @return bool True if email sent successfully, false otherwise
 */
function sendAdminNotification($subject, $message, $attachments = []) {
    return sendEmail(ADMIN_EMAIL, $subject, $message, 'JShuk System', SMTP_FROM_EMAIL, $attachments);
}

/**
 * Send a welcome email to new users
 * 
 * @param string $email User email address
 * @param string $name User name
 * @param string $activation_link Activation link (if required)
 * @return bool True if email sent successfully, false otherwise
 */
function sendWelcomeEmail($email, $name, $activation_link = null) {
    $subject = "Welcome to JShuk!";
    
    $message = "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <div style='background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; padding: 2rem; text-align: center;'>
                <h1 style='margin: 0; font-size: 2rem;'>Welcome to JShuk!</h1>
                <p style='margin: 0.5rem 0 0 0; font-size: 1.1rem;'>Your business directory platform</p>
            </div>
            
            <div style='padding: 2rem; background: #f8f9fa;'>
                <h2 style='color: #333; margin-bottom: 1rem;'>Hi {$name},</h2>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    Thank you for joining JShuk! We're excited to have you as part of our community.
                </p>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    With JShuk, you can:
                </p>
                
                <ul style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    <li>Create and manage your business profile</li>
                    <li>Connect with customers and grow your business</li>
                    <li>Showcase your products and services</li>
                    <li>Receive customer reviews and feedback</li>
                </ul>
                
                <div style='text-align: center; margin: 2rem 0;'>
                    <a href='" . BASE_URL . "/users/dashboard.php' 
                       style='background: #0d6efd; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px; display: inline-block;'>
                        Go to Your Dashboard
                    </a>
                </div>
                
                " . ($activation_link ? "
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    <strong>Important:</strong> Please click the link below to activate your account:
                </p>
                
                <div style='text-align: center; margin: 2rem 0;'>
                    <a href='{$activation_link}' 
                       style='background: #28a745; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px; display: inline-block;'>
                        Activate Account
                    </a>
                </div>
                " : "") . "
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    If you have any questions or need assistance, feel free to contact our support team.
                </p>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 0;'>
                    Best regards,<br>
                    The JShuk Team
                </p>
            </div>
            
            <div style='background: #333; color: white; padding: 1rem; text-align: center; font-size: 0.9rem;'>
                <p style='margin: 0;'>
                    © " . date('Y') . " JShuk. All rights reserved.<br>
                    <a href='" . BASE_URL . "/privacy.php' style='color: #fff;'>Privacy Policy</a> | 
                    <a href='" . BASE_URL . "/terms.php' style='color: #fff;'>Terms of Service</a>
                </p>
            </div>
        </div>
    ";
    
    return sendEmail($email, $subject, $message, 'JShuk Team', SMTP_FROM_EMAIL);
}

/**
 * Send a password reset email
 * 
 * @param string $email User email address
 * @param string $name User name
 * @param string $reset_link Password reset link
 * @return bool True if email sent successfully, false otherwise
 */
function sendPasswordResetEmail($email, $name, $reset_link) {
    $subject = "Password Reset Request - JShuk";
    
    $message = "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <div style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 2rem; text-align: center;'>
                <h1 style='margin: 0; font-size: 2rem;'>Password Reset</h1>
                <p style='margin: 0.5rem 0 0 0; font-size: 1.1rem;'>JShuk Account Security</p>
            </div>
            
            <div style='padding: 2rem; background: #f8f9fa;'>
                <h2 style='color: #333; margin-bottom: 1rem;'>Hi {$name},</h2>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    We received a request to reset your password for your JShuk account.
                </p>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    Click the button below to reset your password:
                </p>
                
                <div style='text-align: center; margin: 2rem 0;'>
                    <a href='{$reset_link}' 
                       style='background: #dc3545; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    <strong>Important:</strong> This link will expire in 1 hour for security reasons.
                </p>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>
                    If you didn't request a password reset, please ignore this email or contact our support team if you have concerns.
                </p>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 0;'>
                    Best regards,<br>
                    The JShuk Team
                </p>
            </div>
            
            <div style='background: #333; color: white; padding: 1rem; text-align: center; font-size: 0.9rem;'>
                <p style='margin: 0;'>
                    © " . date('Y') . " JShuk. All rights reserved.<br>
                    <a href='" . BASE_URL . "/privacy.php' style='color: #fff;'>Privacy Policy</a> | 
                    <a href='" . BASE_URL . "/terms.php' style='color: #fff;'>Terms of Service</a>
                </p>
            </div>
        </div>
    ";
    
    return sendEmail($email, $subject, $message, 'JShuk Security', SMTP_FROM_EMAIL);
}

/**
 * Send a newsletter email
 * 
 * @param array $subscribers Array of subscriber emails
 * @param string $subject Newsletter subject
 * @param string $content Newsletter content (HTML)
 * @param string $unsubscribe_link Base unsubscribe link
 * @return array Array with success count and failed emails
 */
function sendNewsletter($subscribers, $subject, $content, $unsubscribe_link) {
    $success_count = 0;
    $failed_emails = [];
    
    foreach ($subscribers as $subscriber) {
        $email = $subscriber['email'];
        $name = $subscriber['name'] ?? 'Subscriber';
        
        // Add unsubscribe link to content
        $newsletter_content = $content . "
            <hr style='margin: 2rem 0; border: none; border-top: 1px solid #ddd;'>
            <p style='text-align: center; font-size: 0.9rem; color: #666;'>
                <a href='{$unsubscribe_link}?email=" . urlencode($email) . "' style='color: #666;'>
                    Unsubscribe from this newsletter
                </a>
            </p>
        ";
        
        if (sendEmail($email, $subject, $newsletter_content, 'JShuk Newsletter', SMTP_FROM_EMAIL)) {
            $success_count++;
        } else {
            $failed_emails[] = $email;
        }
        
        // Small delay to avoid overwhelming the mail server
        usleep(100000); // 0.1 second delay
    }
    
    return [
        'success_count' => $success_count,
        'failed_emails' => $failed_emails,
        'total_sent' => count($subscribers)
    ];
}

/**
 * Test email configuration
 * 
 * @param string $test_email Email address to send test to
 * @return array Array with success status and any error messages
 */
function testEmailConfiguration($test_email) {
    $subject = "JShuk Email Configuration Test";
    $message = "
        <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'>
            <div style='background: #28a745; color: white; padding: 2rem; text-align: center;'>
                <h1 style='margin: 0; font-size: 2rem;'>Email Test Successful!</h1>
            </div>
            
            <div style='padding: 2rem; background: #f8f9fa;'>
                <p style='color: #666; line-height: 1.6;'>
                    This is a test email to verify that your JShuk email configuration is working correctly.
                </p>
                
                <p style='color: #666; line-height: 1.6;'>
                    <strong>Test Details:</strong><br>
                    Time: " . date('Y-m-d H:i:s') . "<br>
                    Server: " . $_SERVER['SERVER_NAME'] . "
                </p>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 0;'>
                    If you received this email, your email configuration is working properly!
                </p>
            </div>
        </div>
    ";
    
    $result = sendEmail($test_email, $subject, $message, 'JShuk System Test', SMTP_FROM_EMAIL);
    
    return [
        'success' => $result,
        'message' => $result ? 'Email sent successfully' : 'Failed to send email',
        'timestamp' => date('Y-m-d H:i:s')
    ];
} 