<?php
require_once '../../config/config.php';
require_once '../admin_auth_check.php';

$id = $_GET['id'] ?? null;
$editing = false;
$message = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM advertising_slots WHERE id = ?");
    $stmt->execute([$id]);
    $slot = $stmt->fetch();
    if ($slot) {
        $editing = true;
    } else {
        header("Location: index.php?error=slot_not_found");
        exit();
    }
} else {
    $slot = [
        'name' => '', 
        'description' => '',
        'monthly_price' => '', 
        'annual_price' => '',
        'current_slots' => 0,
        'max_slots' => 1,
        'duration_days' => 30,
        'position' => 'header',
        'status' => 'active'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $monthly_price = floatval($_POST['monthly_price']);
    $annual_price = !empty($_POST['annual_price']) ? floatval($_POST['annual_price']) : null;
    $current_slots = intval($_POST['current_slots']);
    $max_slots = intval($_POST['max_slots']);
    $duration_days = intval($_POST['duration_days']);
    $position = $_POST['position'];
    $status = $_POST['status'];

    try {
        if ($editing) {
            $stmt = $pdo->prepare("
                UPDATE advertising_slots 
                SET name = ?, description = ?, monthly_price = ?, annual_price = ?, 
                    current_slots = ?, max_slots = ?, duration_days = ?, position = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $description, $monthly_price, $annual_price, 
                $current_slots, $max_slots, $duration_days, $position, $status, $id
            ]);
            $message = "Advertising slot updated successfully!";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO advertising_slots 
                (name, description, monthly_price, annual_price, current_slots, 
                 max_slots, duration_days, position, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $description, $monthly_price, $annual_price, 
                $current_slots, $max_slots, $duration_days, $position, $status
            ]);
            $message = "Advertising slot created successfully!";
        }
        
        header("Location: index.php?success=1");
        exit();
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

$pageTitle = ($editing ? 'Edit' : 'Add') . " Advertising Slot";
include '../admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-ad me-2"></i>
                    <?= $editing ? 'Edit' : 'Add' ?> Advertising Slot
                </h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Pricing
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-info">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Basic Information</h5>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Slot Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($slot['name']) ?>" required>
                                    <div class="invalid-feedback">Please provide a slot name.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                              placeholder="Describe the advertising slot and its benefits"><?= htmlspecialchars($slot['description']) ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <select class="form-select" id="position" name="position">
                                        <option value="header" <?= $slot['position'] === 'header' ? 'selected' : '' ?>>Header</option>
                                        <option value="sidebar" <?= $slot['position'] === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                                        <option value="footer" <?= $slot['position'] === 'footer' ? 'selected' : '' ?>>Footer</option>
                                        <option value="homepage" <?= $slot['position'] === 'homepage' ? 'selected' : '' ?>>Homepage</option>
                                        <option value="category" <?= $slot['position'] === 'category' ? 'selected' : '' ?>>Category Pages</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?= $slot['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $slot['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Pricing & Availability</h5>
                                
                                <div class="mb-3">
                                    <label for="monthly_price" class="form-label">Monthly Price (£) *</label>
                                    <input type="number" class="form-control" id="monthly_price" name="monthly_price" 
                                           value="<?= $slot['monthly_price'] ?>" step="0.01" min="0" required>
                                    <div class="invalid-feedback">Please provide a valid monthly price.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="annual_price" class="form-label">Annual Price (£)</label>
                                    <input type="number" class="form-control" id="annual_price" name="annual_price" 
                                           value="<?= $slot['annual_price'] ?>" step="0.01" min="0">
                                    <small class="form-text text-muted">Leave empty if no annual option</small>
                                </div>

                                <div class="mb-3">
                                    <label for="max_slots" class="form-label">Maximum Slots Available</label>
                                    <input type="number" class="form-control" id="max_slots" name="max_slots" 
                                           value="<?= $slot['max_slots'] ?>" min="1" required>
                                    <div class="invalid-feedback">Please provide a valid number of slots.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="current_slots" class="form-label">Currently Occupied Slots</label>
                                    <input type="number" class="form-control" id="current_slots" name="current_slots" 
                                           value="<?= $slot['current_slots'] ?>" min="0" max="<?= $slot['max_slots'] ?>">
                                    <small class="form-text text-muted">How many slots are currently booked</small>
                                </div>

                                <div class="mb-3">
                                    <label for="duration_days" class="form-label">Duration (Days)</label>
                                    <input type="number" class="form-control" id="duration_days" name="duration_days" 
                                           value="<?= $slot['duration_days'] ?>" min="1" max="365" required>
                                    <small class="form-text text-muted">How long each booking lasts</small>
                                    <div class="invalid-feedback">Please provide a valid duration.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?= $editing ? 'Update' : 'Create' ?> Slot
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Update max value for current_slots when max_slots changes
document.getElementById('max_slots').addEventListener('change', function() {
    document.getElementById('current_slots').max = this.value;
});
</script>

<?php include '../admin_footer.php'; ?> 