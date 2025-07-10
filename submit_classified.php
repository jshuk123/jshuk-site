<?php
echo "STEP 1: File loaded<br>";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
echo "STEP 2: Session started<br>";

require_once 'config/config.php';
echo "STEP 3: Config loaded<br>";

if (!isset($pdo)) { echo "STEP 3.1: PDO not set<br>"; exit; }

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    echo "STEP 4: Not logged in<br>";
    echo '<div class="container py-5 text-center"><h2>You must be logged in to post a classified.</h2><a href="/auth/login.php" class="btn btn-primary mt-3">Login</a></div>';
    exit;
}
echo "STEP 5: User is logged in<br>";

// Fetch categories
echo "STEP 6: About to fetch categories<br>";
$categories = $pdo->query("SELECT * FROM classifieds_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
echo "STEP 7: Categories fetched: " . count($categories) . "<br>";

// Handle form submission
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "STEP 8: Form submitted<br>";
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = !empty($_POST['price']) ? trim($_POST['price']) : '0.00';
    $location = trim($_POST['location']);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $image_path = null;

    // Handle image upload (optional)
    if (!empty($_FILES['image']['name'])) {
        $targetDir = 'uploads/classifieds/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = uniqid('img_') . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_path = $targetFile;
        } else {
            $error = 'Failed to upload image.';
        }
    }

    // Basic validation
    if (empty($title) || empty($description) || empty($category_id)) {
        $error = 'Title, description, and category are required.';
    } 
    
    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO classifieds (user_id, category_id, title, description, price, location, image_path, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
        if ($stmt->execute([$user_id, $category_id, $title, $description, $price, $location, $image_path])) {
            $success = true;
        } else {
            $error = 'Failed to post classified. Please try again.';
        }
    }
}
echo "STEP 9: About to include header<br>";

$pageTitle = "Post a Classified";
$page_css = "submit_classified.css";
include 'includes/header_main.php';

echo "STEP 10: About to output HTML<br>";
?>
<div class="container py-5">
    <h1 class="mb-4 text-center">Post a Classified</h1>
    <?php if ($success): ?>
        <div class="alert alert-success text-center">Your classified has been posted successfully.</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mx-auto" style="max-width: 600px;" autocomplete="off">
        <div class="mb-3">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" maxlength="255" required>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select a category...</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" id="description" name="description" rows="5" maxlength="2000" required></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="text" class="form-control" id="price" name="price" maxlength="50" placeholder="e.g., 25.00 or 'Free'">
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location" maxlength="255">
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Image (Optional)</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Post Classified</button>
        </div>
    </form>
</div>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places"></script>
<script src="/js/submit_classified.js"></script>
<?php include 'includes/footer_main.php'; ?> 