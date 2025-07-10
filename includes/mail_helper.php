<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/smtp_config.php';

function sendVerificationEmail($email, $name, $token) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USERNAME, 'Business Directory');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        
        $verificationLink = "https://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $token;
        
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Welcome to Business Directory!</h2>
                <p>Dear {$name},</p>
                <p>Thank you for registering. Please click the button below to verify your email address:</p>
                <p style='text-align: center;'>
                    <a href='{$verificationLink}' 
                       style='background-color: #007bff; 
                              color: white; 
                              padding: 10px 20px; 
                              text-decoration: none; 
                              border-radius: 5px; 
                              display: inline-block;'>
                        Verify Email Address
                    </a>
                </p>
                <p>Or copy and paste this link in your browser:</p>
                <p>{$verificationLink}</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not create an account, no further action is required.</p>
                <br>
                <p>Best regards,<br>Business Directory Team</p>
            </body>
            </html>
        ";
        
        $mail->AltBody = "
            Welcome to Business Directory!
            
            Dear {$name},
            
            Thank you for registering. Please click the link below to verify your email address:
            
            {$verificationLink}
            
            This link will expire in 24 hours.
            
            If you did not create an account, no further action is required.
            
            Best regards,
            Business Directory Team
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

function resendVerificationEmail($email) {
    // Get user details
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, verification_token 
        FROM users 
        WHERE email = ? AND email_verified = 0
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate new token and expiry
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update token in database
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET verification_token = ?, 
                verification_expires = ? 
            WHERE email = ?
        ");
        $updateStmt->execute([$token, $expires, $email]);

        // Send new verification email
        $fullName = $user['first_name'] . ' ' . $user['last_name'];
        return sendVerificationEmail($email, $fullName, $token);
    }
    return false;
}
?> 