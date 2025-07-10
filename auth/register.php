<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../vendor/autoload.php';
require_once '../config/stripe_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // --- Validation Rules ---
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (!$email) $errors[] = "A valid email is required.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters long.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    // --- Check if email already exists ---
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email already exists.";
        }
    }

    // --- Process Registration if No Errors ---
    if (empty($errors)) {
        $username = strstr($email, '@', true);
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        try {
            $pdo->beginTransaction();
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, first_name, last_name, password, verification_token, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $first_name, $last_name, $password_hashed, $token]);
            $user_id = $pdo->lastInsertId();
            
            // --- Temporarily Disabled for Debugging ---
            /*
            // Create Stripe customer
            $customer = \Stripe\Customer::create(['email' => $email, 'name' => "$first_name $last_name", 'metadata' => ['user_id' => $user_id]]);
            $pdo->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?")->execute([$customer->id, $user_id]);
            
            // Send verification email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_USERNAME, 'JShuk');
            $mail->addAddress($email, "$first_name $last_name");
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your JShuk Account';
            $verificationLink = BASE_URL . "/auth/verify.php?token=" . urlencode($token);
            $mail->Body = "<h2>Welcome to JShuk!</h2><p>Please click the link to verify your email: <a href='{$verificationLink}'>{$verificationLink}</a></p>";
            $mail->send();
            */
            // --- End of Disabled Code ---

            $pdo->commit();
            
            // --- Manually log in the user and redirect to dashboard ---
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $first_name;
            $_SESSION['is_admin'] = false; // Default to non-admin
            
            $_SESSION['success_message'] = "Registration successful! You are now logged in.";
            header("Location: /users/dashboard.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            // Log the detailed error for the admin
            error_log("Registration failed: " . $e->getMessage());
            // Show a generic error to the user
            $errors[] = "An unexpected error occurred. Please try again later or contact support.";
        }
    }
}

$pageTitle = "Register";
$page_css = "register.css";
include '../includes/header_main.php';
?>

<div class="container py-5" style="max-width: 500px;">
    <div class="card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <h2 class="text-center mb-4 fw-bold">Create Account</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Must be at least 8 characters long.</div>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer_main.php'; ?> 