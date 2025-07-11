<?php
require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

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

$gemach = null;
$error_message = '';
$success_message = '';

try {
    if (isset($pdo) && $pdo) {
        $gemach_id = $_GET['id'] ?? 0;
        
        if (!$gemach_id) {
            throw new Exception('No gemach specified.');
        }
        
        // Load gemach details
        $stmt = $pdo->prepare("
            SELECT g.*, gc.name as category_name, gc.icon_class as category_icon
            FROM gemachim g
            LEFT JOIN gemach_categories gc ON g.category_id = gc.id
            WHERE g.id = ? AND g.status = 'active' AND g.verified = 1 AND g.donation_enabled = 1
        ");
        $stmt->execute([$gemach_id]);
        $gemach = $stmt->fetch();
        
        if (!$gemach) {
            throw new Exception('Gemach not found or donations not enabled.');
        }
        
        // Handle donation submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid request. Please try again.');
            }
            
            // Validate required fields
            $required_fields = ['amount', 'donor_name', 'donor_email'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Please fill in all required fields.");
                }
            }
            
            // Validate email
            if (!filter_var($_POST['donor_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }
            
            // Validate amount
            $amount = floatval($_POST['amount']);
            if ($amount < 1) {
                throw new Exception("Minimum donation amount is £1.");
            }
            
            // Insert donation record
            $stmt = $pdo->prepare("
                INSERT INTO gemach_donations (
                    gemach_id, donor_name, donor_email, amount, 
                    payment_method, status, notes
                ) VALUES (?, ?, ?, ?, 'stripe', 'pending', ?)
            ");
            
            $stmt->execute([
                $gemach_id,
                $_POST['donor_name'],
                $_POST['donor_email'],
                $amount,
                $_POST['notes'] ?? null
            ]);
            
            $donation_id = $pdo->lastInsertId();
            
            // Redirect to Stripe checkout or show success
            if (defined('STRIPE_PUBLISHABLE_KEY') && STRIPE_PUBLISHABLE_KEY) {
                // Store donation ID in session for Stripe webhook
                $_SESSION['pending_donation_id'] = $donation_id;
                
                // Redirect to Stripe checkout
                header("Location: /payment/stripe_checkout.php?donation_id=" . $donation_id);
                exit;
            } else {
                // Mark as completed if no Stripe
                $stmt = $pdo->prepare("UPDATE gemach_donations SET status = 'completed' WHERE id = ?");
                $stmt->execute([$donation_id]);
                
                $success_message = "Thank you for your donation! Your contribution will help support this gemach.";
            }
        }
        
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$pageTitle = "Donate to " . ($gemach['name'] ?? 'Gemach') . " | JShuk";
$page_css = "donate.css";
$metaDescription = "Support this gemach with your donation. Help maintain and expand community resources for those in need.";
$metaKeywords = "donate, gemach, charity, jewish community, mitzvah";

include 'includes/header_main.php';
?>

<?php if (!$gemach): ?>
<!-- Error State -->
<section class="donate-error">
    <div class="container">
        <div class="error-card text-center">
            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
            <h2>Donation Not Available</h2>
            <p class="text-muted"><?= htmlspecialchars($error_message) ?></p>
            <a href="/gemachim.php" class="btn-jshuk-primary">
                <i class="fas fa-arrow-left"></i>
                Back to Gemachim
            </a>
        </div>
    </div>
</section>
<?php else: ?>
<!-- Donation Form -->
<section class="donate-hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="donate-card">
                    <div class="gemach-info">
                        <div class="gemach-header">
                            <div class="gemach-image">
                                <?php if ($gemach['image_paths']): ?>
                                    <?php $images = json_decode($gemach['image_paths'], true); ?>
                                    <img src="<?= htmlspecialchars($images[0] ?? '/images/elite-placeholder.svg') ?>" 
                                         alt="<?= htmlspecialchars($gemach['name']) ?>">
                                <?php else: ?>
                                    <img src="/images/elite-placeholder.svg" 
                                         alt="<?= htmlspecialchars($gemach['name']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="gemach-details">
                                <h1 class="gemach-title"><?= htmlspecialchars($gemach['name']) ?></h1>
                                <div class="gemach-category">
                                    <i class="<?= htmlspecialchars($gemach['category_icon']) ?>"></i>
                                    <?= htmlspecialchars($gemach['category_name']) ?>
                                </div>
                                <div class="gemach-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($gemach['location']) ?>
                                </div>
                                <?php if ($gemach['in_memory_of']): ?>
                                <div class="memory-badge">
                                    <i class="fas fa-dove"></i>
                                    In memory of <?= htmlspecialchars($gemach['in_memory_of']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="gemach-description">
                            <p><?= htmlspecialchars($gemach['description']) ?></p>
                        </div>
                    </div>
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="donation-form">
                        <h2 class="form-title">Make a Donation</h2>
                        <p class="form-subtitle">Your contribution helps maintain and expand this gemach for the community.</p>
                        
                        <form method="POST" id="donation-form">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="donor_name" class="form-label">Your Name *</label>
                                        <input type="text" id="donor_name" name="donor_name" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['donor_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="donor_email" class="form-label">Email Address *</label>
                                        <input type="email" id="donor_email" name="donor_email" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['donor_email'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount" class="form-label">Donation Amount *</label>
                                <div class="amount-options">
                                    <div class="amount-buttons">
                                        <button type="button" class="amount-btn" data-amount="5">£5</button>
                                        <button type="button" class="amount-btn" data-amount="10">£10</button>
                                        <button type="button" class="amount-btn" data-amount="20">£20</button>
                                        <button type="button" class="amount-btn" data-amount="50">£50</button>
                                        <button type="button" class="amount-btn" data-amount="100">£100</button>
                                    </div>
                                    <div class="custom-amount">
                                        <label for="custom_amount">Or enter custom amount:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">£</span>
                                            <input type="number" id="custom_amount" name="amount" class="form-control" 
                                                   min="1" step="0.01" placeholder="Enter amount" 
                                                   value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes" class="form-label">Message (Optional)</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3" 
                                          placeholder="Leave a message of support..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="donation-summary">
                                <h4>Donation Summary</h4>
                                <div class="summary-item">
                                    <span>Gemach:</span>
                                    <span><?= htmlspecialchars($gemach['name']) ?></span>
                                </div>
                                <div class="summary-item">
                                    <span>Amount:</span>
                                    <span id="summary-amount">£0.00</span>
                                </div>
                                <div class="summary-total">
                                    <span>Total:</span>
                                    <span id="summary-total">£0.00</span>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-jshuk-primary btn-lg">
                                    <i class="fas fa-heart"></i>
                                    Complete Donation
                                </button>
                                <a href="/gemachim.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left"></i>
                                    Back to Gemachim
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Trust Indicators -->
                <div class="trust-indicators">
                    <div class="trust-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Payment</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-lock"></i>
                        <span>SSL Encrypted</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-handshake"></i>
                        <span>100% Secure</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountButtons = document.querySelectorAll('.amount-btn');
    const customAmountInput = document.getElementById('custom_amount');
    const summaryAmount = document.getElementById('summary-amount');
    const summaryTotal = document.getElementById('summary-total');
    
    // Handle amount button clicks
    amountButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            amountButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Set the amount in the input
            const amount = this.dataset.amount;
            customAmountInput.value = amount;
            
            // Update summary
            updateSummary(amount);
        });
    });
    
    // Handle custom amount input
    customAmountInput.addEventListener('input', function() {
        // Remove active class from buttons when typing
        amountButtons.forEach(btn => btn.classList.remove('active'));
        
        // Update summary
        updateSummary(this.value);
    });
    
    function updateSummary(amount) {
        const numAmount = parseFloat(amount) || 0;
        summaryAmount.textContent = `£${numAmount.toFixed(2)}`;
        summaryTotal.textContent = `£${numAmount.toFixed(2)}`;
    }
    
    // Form validation
    const form = document.getElementById('donation-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const amount = parseFloat(customAmountInput.value);
            if (amount < 1) {
                e.preventDefault();
                alert('Please enter a valid donation amount (minimum £1).');
                customAmountInput.focus();
            }
        });
    }
    
    // Initialize summary
    updateSummary(customAmountInput.value);
});
</script>

<?php include 'includes/footer_main.php'; ?> 