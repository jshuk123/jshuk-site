<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/stripe_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $username = strstr($email, '@', true);
    $selected_plan = $_POST['plan'] ?? null;
    
    // Generate token and ensure consistent length
    $token = bin2hex(random_bytes(32));

    if ($password !== $confirm_password) {
        header('Location: register.php?error=password_mismatch');
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: register.php?error=invalid_email');
        exit();
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=email_exists');
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, email, first_name, last_name, password,
                verification_token, verification_expires, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())
        ");

        if ($stmt->execute([$username, $email, $first_name, $last_name, $hashed_password, $token])) {
            $user_id = $pdo->lastInsertId();
            
            // Get the basic plan ID
            $stmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = 'Basic'");
            $stmt->execute();
            $basic_plan = $stmt->fetch();
            
            if ($basic_plan) {
                // Create a subscription for the basic plan
                $stmt = $pdo->prepare("
                    INSERT INTO user_subscriptions (
                        user_id, plan_id, status, created_at
                    ) VALUES (?, ?, 'active', NOW())
                ");
                $stmt->execute([$user_id, $basic_plan['id']]);
            }

            // Send verification email
            require_once '../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;

                $mail->setFrom(SMTP_USERNAME, SITE_NAME);
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email';
                $mail->Body = "
                    <h2>Welcome to JSHUK!</h2>
                    <p>Please click the link below to verify your email address:</p>
                    <p><a href='https://{$_SERVER['HTTP_HOST']}/auth/verify.php?token={$token}'>Verify Email</a></p>
                    <p>This link will expire in 24 hours.</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log('Email Error: ' . $mail->ErrorInfo);
            }

            $pdo->commit();

            // Log the user in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;

            // Set subscription info in session
            if ($basic_plan) {
                $_SESSION['subscription'] = [
                    'plan_id' => $basic_plan['id'],
                    'plan_name' => 'Basic',
                    'status' => 'active'
                ];
            }

            // Redirect to subscription page if a plan was selected
            if ($selected_plan) {
                header('Location: /payment/subscription.php?plan=' . urlencode($selected_plan));
            } else {
                header('Location: /index.php?welcome=1');
            }
            exit();
        } else {
            throw new Exception('Failed to create user account');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Registration Error: ' . $e->getMessage());
        header('Location: register.php?error=registration_failed');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?> 