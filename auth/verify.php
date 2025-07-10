<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Mail helper functions
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

// Verification logic
$message = '';
$messageType = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = urldecode(trim($_GET['token']));
    
    // For debugging (remove in production)
    error_log("Received token: " . $token);
    
    // Find user with this token
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, username, email_verified 
        FROM users 
        WHERE verification_token = ? 
        AND (email_verified = 0 OR email_verified IS NULL)
    ");
    
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Update user as verified
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET email_verified = 1,
                verification_token = NULL
            WHERE id = ?
        ");
        
        if ($updateStmt->execute([$user['id']])) {
            // Log the user in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            $message = "Email verified successfully! You are now logged in.";
            $messageType = "success";
            
            // Redirect after 3 seconds
            header("refresh:3;url=/index.php");
        } else {
            $message = "Error verifying email. Please try again.";
            $messageType = "danger";
        }
    } else {
        $message = "Invalid or expired verification link.";
        $messageType = "danger";
    }
} else {
    $message = "No verification token provided.";
    $messageType = "danger";
}

$pageTitle = "Email Verification";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Email Verification</h2>
                    
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <?php if ($messageType === 'danger'): ?>
                            <a href="/auth/login.php" class="btn btn-primary">Back to Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_main.php'; ?> 