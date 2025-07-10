<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/users/dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$business_id) {
    header('Location: /users/dashboard.php');
    exit;
}

// Fetch business and check ownership
$stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ? AND user_id = ?");
$stmt->execute([$business_id, $user_id]);
$business = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$business) {
    die('Business not found or you do not have permission to edit this business.');
}

// Parse contact info JSON
$contact_info = json_decode($business['contact_info'] ?? '{}', true);
$contact_info = is_array($contact_info) ? $contact_info : [];

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $services = trim($_POST['services'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate required fields
    if ($name === '' || $description === '') {
        $error = 'Business name and description are required.';
    } else {
        // Update contact info JSON
        $contact_info = [
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'address' => $address,
        ];
        try {
            $stmt = $pdo->prepare("UPDATE businesses SET business_name = ?, description = ?, about = ?, services = ?, contact_info = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([
                $name,
                $description,
                $about,
                $services,
                json_encode($contact_info),
                $business_id,
                $user_id
            ]);
            $success = 'Business details updated successfully!';
            // Refresh business data
            $stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ? AND user_id = ?");
            $stmt->execute([$business_id, $user_id]);
            $business = $stmt->fetch(PDO::FETCH_ASSOC);
            $contact_info = json_decode($business['contact_info'] ?? '{}', true);
            $contact_info = is_array($contact_info) ? $contact_info : [];
        } catch (PDOException $e) {
            $error = 'Error updating business: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Business';
$page_css = 'dashboard.css';
include '../includes/header_main.php';
?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="/users/dashboard.php" class="btn btn-secondary mb-3"><i class="fa fa-arrow-left me-1"></i> Back to Dashboard</a>
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Edit Business: <?= htmlspecialchars($business['business_name']) ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Business Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($business['business_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($business['description']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">About</label>
                            <textarea name="about" class="form-control" rows="2"><?= htmlspecialchars($business['about'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Services / Features</label>
                            <textarea name="services" class="form-control" rows="2"><?= htmlspecialchars($business['services'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($contact_info['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($contact_info['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($contact_info['website'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($contact_info['address'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Image upload section (scaffold) -->
                        <div class="mb-3">
                            <label class="form-label">Business Images / Gallery</label>
                            <div class="alert alert-info small">Image upload/management coming soon. Please contact support to update images for now.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer_main.php'; ?> 