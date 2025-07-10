<?php
session_start();
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['stripe_customer_id']) {
    header('Location: /jshuk/users/settings.php');
    exit();
}

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Get customer's invoices
    $invoices = \Stripe\Invoice::all([
        'customer' => $user['stripe_customer_id'],
        'limit' => 12 // Show last 12 invoices
    ]);

    $pageTitle = "Billing History";
    $page_css = "billing_history.css";
    include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0">Billing History</h4>
                        <a href="/jshuk/users/settings.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Back to Settings
                        </a>
                    </div>

                    <?php if (empty($invoices->data)): ?>
                        <div class="alert alert-info">
                            No billing history found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices->data as $invoice): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', $invoice->created); ?></td>
                                            <td>
                                                <?php
                                                if ($invoice->subscription) {
                                                    $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
                                                    $plan = \Stripe\Plan::retrieve($subscription->items->data[0]->plan->id);
                                                    echo htmlspecialchars($plan->nickname ?? $plan->id);
                                                } else {
                                                    echo 'One-time payment';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $amount = $invoice->amount_paid / 100;
                                                echo '$' . number_format($amount, 2);
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $invoice->status === 'paid' ? 'success' : 
                                                        ($invoice->status === 'open' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($invoice->status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($invoice->invoice_pdf): ?>
                                                    <a href="<?php echo $invoice->invoice_pdf; ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    include '../includes/footer_main.php';
} catch (Exception $e) {
    error_log('Billing History Error: ' . $e->getMessage());
    header('Location: /jshuk/users/settings.php?error=billing_history_failed');
    exit();
}
?> 