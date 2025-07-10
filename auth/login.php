<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../vendor/autoload.php';
require_once '../config/stripe_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function resendVerificationEmail($email) {
    global $pdo;
    $verification_token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $updateStmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_expires = ? WHERE email = ?");
    $updateStmt->execute([$verification_token, $expires, $email]);
    $stmt = $pdo->prepare("SELECT first_name, last_name, verification_token FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_USERNAME, 'JSHUK');
            $mail->addAddress($email, $user['first_name'] . ' ' . $user['last_name']);
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - JSHUK';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $verificationLink = $protocol . $_SERVER['HTTP_HOST'] . "/auth/verify.php?token=" . urlencode($verification_token);
            $mail->Body = "<h2>Welcome to JSHUK!</h2><p>Hi {$user['first_name']},</p><p>Please click the button below to verify your email address:</p><p><a href='{$verificationLink}' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Verify Email</a></p><p>Or copy this link: {$verificationLink}</p>";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Failed to send verification email: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if user is banned
            if (isset($user['is_banned']) && $user['is_banned']) {
                $error = "Your account has been banned. Contact support for help.";
            } elseif (isset($user['email_verified']) && $user['email_verified']) {
                // Set all session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['is_admin'] = ($user['role'] === 'admin');

                // Check subscription status
                $subscription = getUserSubscription($user['id']);
                if (!$subscription && $user['role'] !== 'admin') {
                    header("Location: /payment/subscription.php");
                    exit();
                }
                if ($subscription) {
                    $_SESSION['subscription'] = [
                        'plan_id' => $subscription['plan_id'],
                        'plan_name' => $subscription['name'],
                        'status' => $subscription['status'],
                        'current_period_end' => $subscription['current_period_end']
                    ];
                }
                error_log('User Login - ID: ' . $user['id'] . ', Role: ' . ($user['role'] ?? 'not set'));
                header("Location: /index.php");
                exit();
            } else {
                if (resendVerificationEmail($email)) {
                    $error = "Please verify your email address before logging in. A new verification link has been sent to your email.";
                } else {
                    $error = "Your email is not verified. There was a problem sending a new verification email. Please contact support.";
                }
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}

$pageTitle = "Login";
$page_css = "login.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Login</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <!-- Google Sign-In Error Display -->
                    <div id="login-error" class="alert alert-danger" style="display: none;"></div>
                    <form method="POST" action="/auth/login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <!-- Google Sign-In Button -->
                    <div class="text-center my-3">
                        <div id="g_id_onload"
                             data-client_id="718581742318-e4q3putg0b10e08eab4ma2sr9urbqb31.apps.googleusercontent.com"
                             data-context="signin"
                             data-ux_mode="popup"
                             data-callback="handleGoogleSignIn"
                             data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin"
                             data-type="standard"
                             data-shape="pill"
                             data-theme="filled_blue"
                             data-text="continue_with"
                             data-size="large"
                             data-logo_alignment="left">
                        </div>
                    </div>
                    
                    <!-- Debug Information (only show in development) -->
                    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted">
                            <strong>Debug Info:</strong><br>
                            Domain: <?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?><br>
                            Protocol: <?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP'; ?><br>
                            Client ID: 718581742318-e4q3putg0b10e08eab4ma2sr9urbqb31.apps.googleusercontent.com<br>
                            <a href="/auth/google-verify.php?test=1" target="_blank">Test Google Endpoint</a>
                        </small>
                    </div>
                    <?php endif; ?>
                    <div class="text-center mt-4">
                        <p class="mb-0">Don't have an account? <a href="/auth/register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="/js/login.js"></script>
<script>
// Check if Google Sign-In library loaded properly
window.addEventListener('load', function() {
    if (typeof google === 'undefined') {
        console.error('Google Sign-In library failed to load');
        const errorDiv = document.getElementById('login-error');
        if (errorDiv) {
            errorDiv.textContent = 'Google Sign-In library failed to load. Please refresh the page.';
            errorDiv.style.display = 'block';
        }
    } else {
        console.log('Google Sign-In library loaded successfully');
    }
});
</script>
<?php include '../includes/footer_main.php'; ?> 