<?php
session_start();
require_once '../config/config.php';
require_once '../includes/category_presets.php';

// Check admin access
function checkAdminAccess() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}
checkAdminAccess();

$message = '';
$messageType = '';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? null;
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("INSERT INTO business_categories (name, description, icon) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $description, $icon]);
                    $category_id = $pdo->lastInsertId();
                    
                    // Add category metadata
                    $short_description = $_POST['short_description'] ?? '';
                    $seo_title = $_POST['seo_title'] ?? '';
                    $seo_description = $_POST['seo_description'] ?? '';
                    
                    $meta_stmt = $pdo->prepare("INSERT INTO category_meta (category_id, short_description, seo_title, seo_description) VALUES (?, ?, ?, ?)");
                    $meta_stmt->execute([$category_id, $short_description, $seo_title, $seo_description]);
                    
                    $message = "Category added successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error adding category: " . $e->getMessage();
                    $messageType = "danger";
                }
                break;
            case 'edit':
                $id = $_POST['category_id'] ?? null;
                try {
                    $stmt = $pdo->prepare("UPDATE business_categories SET name = ?, description = ?, icon = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $icon, $id]);
                    
                    // Update category metadata
                    $short_description = $_POST['short_description'] ?? '';
                    $seo_title = $_POST['seo_title'] ?? '';
                    $seo_description = $_POST['seo_description'] ?? '';
                    
                    $meta_stmt = $pdo->prepare("INSERT INTO category_meta (category_id, short_description, seo_title, seo_description) 
                                               VALUES (?, ?, ?, ?) 
                                               ON DUPLICATE KEY UPDATE 
                                               short_description = VALUES(short_description),
                                               seo_title = VALUES(seo_title),
                                               seo_description = VALUES(seo_description)");
                    $meta_stmt->execute([$id, $short_description, $seo_title, $seo_description]);
                    
                    $message = "Category updated successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error updating category: " . $e->getMessage();
                    $messageType = "danger";
                }
                break;
            case 'delete':
                $id = $_POST['category_id'] ?? null;
                try {
                    $stmt = $pdo->prepare("DELETE FROM business_categories WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "Category deleted successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error deleting category: " . $e->getMessage();
                    $messageType = "danger";
                }
                break;
            case 'clear_category_cache':
                require_once __DIR__ . '/../includes/cache.php';
                cache_delete('query:business_categories');
                $message = "Category cache cleared!";
                $messageType = "success";
                break;
        }
    }
}

// Handle inline description add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'], $_POST['description'])) {
    $id = (int)$_POST['category_id'];
    $desc = trim($_POST['description']);
    $stmt = $pdo->prepare("UPDATE business_categories SET description = ? WHERE id = ?");
    $stmt->execute([$desc, $id]);
    header('Location: /admin/categories.php?desc_updated=1');
    exit;
}

// Always fetch categories before rendering
$categories = $pdo->query("SELECT c.*, cm.short_description, cm.seo_title, cm.seo_description, COUNT(b.id) AS business_count 
                           FROM business_categories c 
                           LEFT JOIN category_meta cm ON c.id = cm.category_id 
                           LEFT JOIN businesses b ON b.category_id = c.id 
                           GROUP BY c.id, c.name, c.description, c.icon, cm.short_description, cm.seo_title, cm.seo_description 
                           ORDER BY c.name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if (!empty($_GET['desc_updated'])): ?>
  <div class="alert alert-success">Category description updated!</div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Category Management - JSHUK Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://code.iconify.design/3/iconify.min.js"></script>
  <style>
    body { background: #f4f6fa; }
    .sidebar { min-height: 100vh; background: #212529; }
    .sidebar .nav-link { color: #fff; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #343a40; color: #ffc107; }
    .table thead th { vertical-align: middle; }
    .icon-preview { font-size: 1.5rem; vertical-align: middle; }
    .badge-category { background: #ffc107; color: #23272b; font-size: 1rem; }
    .action-btns .btn { margin-right: 0.25rem; }
    .modal-header { background: #212529; color: #fff; }
    .modal-title { color: #ffc107; }
    @media (max-width: 991px) { .sidebar { min-height: auto; } }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-lg-2 col-md-3 d-md-block sidebar py-4 px-3">
      <div class="d-flex flex-column align-items-start">
        <a href="index.php" class="mb-4 text-white text-decoration-none fs-4 fw-bold"><i class="fa fa-crown me-2"></i>Admin Panel</a>
        <ul class="nav nav-pills flex-column w-100 mb-auto">
          <li class="nav-item mb-1"><a href="index.php" class="nav-link"><i class="fas fa-home me-2"></i>Dashboard</a></li>
          <li class="nav-item mb-1"><a href="users.php" class="nav-link"><i class="fas fa-users me-2"></i>Users</a></li>
          <li class="nav-item mb-1"><a href="businesses.php" class="nav-link"><i class="fas fa-store me-2"></i>Businesses</a></li>
          <li class="nav-item mb-1"><a href="categories.php" class="nav-link active"><i class="fas fa-tags me-2"></i>Categories</a></li>
          <li class="nav-item mb-1"><a href="reviews.php" class="nav-link"><i class="fas fa-star me-2"></i>Reviews</a></li>
        </ul>
        <hr class="text-white w-100">
        <div class="dropdown w-100">
          <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle me-2"></i>
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
          </a>
          <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">Sign out</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- Main content -->
    <main class="col-lg-10 col-md-9 ms-sm-auto px-4 py-4">
      <?php if (!empty($_GET['updated'])): ?>
        <div class="alert alert-success">Descriptions updated successfully!</div>
      <?php endif; ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Category Management</h1>
        <div class="d-flex gap-2">
          <a href="/scripts/apply_category_presets.php"
             class="btn btn-outline-success"
             onclick="return confirm('Apply comprehensive category presets to all categories?')">
            🎯 Apply Presets
          </a>
          <a href="/admin/scripts/populate_category_descriptions.php"
             class="btn btn-outline-secondary"
             onclick="return confirm('Auto-fill all empty category descriptions?')">
            🔁 Fill Missing Descriptions
          </a>
        </div>
      </div>
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Category Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fa fa-plus me-2"></i>Add Category</button>
      </div>
      <?php if ($message): ?>
      <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>
      <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary">Categories</h6>
          <div class="form-group mb-0">
            <input type="text" class="form-control" id="searchCategory" placeholder="Search categories...">
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <form method="POST" style="display:inline-block;margin-bottom:1rem;">
              <input type="hidden" name="action" value="clear_category_cache">
              <button class="btn btn-warning" type="submit"><i class="fa fa-sync"></i> Clear Category Cache</button>
            </form>
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Description</th>
                  <th>Icon</th>
                  <th>Businesses</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                  <td><span class="fw-bold"><?php echo htmlspecialchars($cat['name'] ?? ''); ?></span></td>
                  <td>
                    <?php if (empty(trim($cat['description']))): ?>
                      <form method="post" action="/admin/categories.php" class="d-flex align-items-center gap-2 mb-0">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Add description..." required>
                        <button type="submit" class="btn btn-sm btn-success">Save</button>
                      </form>
                    <?php else: ?>
                      <?= htmlspecialchars($cat['description']) ?>
                      <a href="/admin/edit_category.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-secondary ms-2">Edit</a>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="iconify icon-preview" data-icon="<?php echo htmlspecialchars($cat['icon'] ?? 'mdi:folder'); ?>"></span>
                    <div class="text-muted small"><?php echo htmlspecialchars($cat['icon'] ?? 'mdi:folder'); ?></div>
                  </td>
                  <td><span class="badge bg-info"> <?php echo $cat['business_count']; ?> </span></td>
                  <td class="action-btns">
                    <button class="btn btn-sm btn-primary" title="Edit" onclick='editCategory(<?php echo json_encode($cat); ?>)'><i class="fa fa-edit"></i></button>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                      <button class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')"><i class="fa fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title"><i class="fa fa-plus me-2"></i>Add Category</h5></div>
      <div class="modal-body">
        <input class="form-control mb-2" name="name" id="addName" placeholder="Name" required onchange="checkPreset(this.value)">
        <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
        <input class="form-control mb-2" name="short_description" id="addShortDescription" placeholder="Short Description (for category pages)">
        <input class="form-control mb-2" name="seo_title" id="addSeoTitle" placeholder="SEO Title">
        <textarea class="form-control mb-2" name="seo_description" id="addSeoDescription" placeholder="SEO Description" rows="3"></textarea>
        <div class="mb-2">
          <button type="button" class="btn btn-outline-info btn-sm" onclick="loadPreset()">
            <i class="fas fa-magic"></i> Load Preset
          </button>
          <small class="text-muted ms-2">Auto-fill metadata from presets</small>
        </div>
        <div class="mb-2">
          <label for="iconSelectAdd" class="form-label">Icon</label>
          <select class="form-select" name="icon" id="iconSelectAdd" onchange="document.getElementById('iconPreviewAdd').className='fa-solid '+this.value">
            <optgroup label="General">
              <option value="fa-folder">Folder (default)</option>
              <option value="fa-store">Store</option>
              <option value="fa-briefcase">Briefcase</option>
              <option value="fa-building">Building</option>
              <option value="fa-users">Users</option>
              <option value="fa-user">User</option>
              <option value="fa-user-tie">User Tie</option>
              <option value="fa-user-graduate">User Graduate</option>
              <option value="fa-user-md">Doctor</option>
              <option value="fa-user-nurse">Nurse</option>
              <option value="fa-user-shield">User Shield</option>
              <option value="fa-user-cog">User Cog</option>
              <option value="fa-user-friends">User Friends</option>
            </optgroup>
            <optgroup label="Food & Hospitality">
              <option value="fa-utensils">Restaurant</option>
              <option value="fa-birthday-cake">Catering</option>
              <option value="fa-coffee">Coffee</option>
              <option value="fa-ice-cream">Ice Cream</option>
              <option value="fa-pizza-slice">Pizza</option>
              <option value="fa-hamburger">Hamburger</option>
              <option value="fa-wine-glass">Wine Glass</option>
              <option value="fa-hotel">Hotel</option>
            </optgroup>
            <optgroup label="Retail & Shopping">
              <option value="fa-shopping-bag">Shopping Bag</option>
              <option value="fa-shopping-cart">Shopping Cart</option>
              <option value="fa-tshirt">Clothing</option>
              <option value="fa-gem">Jewelry</option>
              <option value="fa-gift">Gift</option>
              <option value="fa-couch">Furniture</option>
              <option value="fa-mobile-alt">Mobile</option>
              <option value="fa-laptop">Electronics</option>
            </optgroup>
            <optgroup label="Education">
              <option value="fa-graduation-cap">Education</option>
              <option value="fa-book">Book</option>
              <option value="fa-chalkboard-teacher">Teacher</option>
              <option value="fa-school">School</option>
              <option value="fa-pencil-alt">Pencil</option>
            </optgroup>
            <optgroup label="Healthcare & Wellness">
              <option value="fa-heartbeat">Healthcare</option>
              <option value="fa-stethoscope">Stethoscope</option>
              <option value="fa-ambulance">Ambulance</option>
              <option value="fa-spa">Spa</option>
              <option value="fa-dumbbell">Fitness</option>
              <option value="fa-medkit">Medkit</option>
              <option value="fa-tooth">Dentist</option>
              <option value="fa-capsules">Pharmacy</option>
            </optgroup>
            <optgroup label="Professional Services">
              <option value="fa-balance-scale">Legal</option>
              <option value="fa-money-bill-wave">Finance</option>
              <option value="fa-hammer">Construction</option>
              <option value="fa-gavel">Gavel</option>
              <option value="fa-briefcase-medical">Medical Briefcase</option>
              <option value="fa-chart-line">Chart Line</option>
              <option value="fa-cogs">Cogs</option>
              <option value="fa-lightbulb">Consulting</option>
              <option value="fa-paint-brush">Design</option>
              <option value="fa-camera">Photography</option>
              <option value="fa-microphone">Media</option>
              <option value="fa-bullhorn">Marketing</option>
              <option value="fa-globe">Web/IT</option>
              <option value="fa-code">Developer</option>
            </optgroup>
            <optgroup label="Real Estate & Home">
              <option value="fa-home">Home</option>
              <option value="fa-door-open">Door</option>
              <option value="fa-key">Key</option>
              <option value="fa-city">City</option>
              <option value="fa-tree">Landscaping</option>
              <option value="fa-broom">Cleaning</option>
              <option value="fa-tools">Repairs</option>
            </optgroup>
            <optgroup label="Events & Entertainment">
              <option value="fa-calendar-alt">Events</option>
              <option value="fa-film">Film</option>
              <option value="fa-music">Music</option>
              <option value="fa-futbol">Sports</option>
              <option value="fa-theater-masks">Theater</option>
              <option value="fa-birthday-cake">Party</option>
              <option value="fa-gift">Gift</option>
            </optgroup>
            <optgroup label="Travel & Automotive">
              <option value="fa-plane">Travel</option>
              <option value="fa-car">Car</option>
              <option value="fa-bus">Bus</option>
              <option value="fa-ship">Ship</option>
              <option value="fa-taxi">Taxi</option>
              <option value="fa-bicycle">Bicycle</option>
              <option value="fa-motorcycle">Motorcycle</option>
              <option value="fa-train">Train</option>
            </optgroup>
            <optgroup label="Community & Religion">
              <option value="fa-synagogue">Synagogue</option>
              <option value="fa-hands-helping">Charity</option>
              <option value="fa-praying-hands">Praying Hands</option>
              <option value="fa-star-of-david">Star of David</option>
              <option value="fa-people-carry">Community</option>
            </optgroup>
            <optgroup label="Other">
              <option value="fa-store">Other</option>
              <option value="fa-question">Question</option>
              <option value="fa-info">Info</option>
            </optgroup>
          </select>
          <div class="mt-2"><i id="iconPreviewAdd" class="fa-solid fa-folder fa-2x"></i> <span class="text-muted small">Live Preview</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" id="editId" name="category_id">
      <div class="modal-header"><h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit Category</h5></div>
      <div class="modal-body">
        <input class="form-control mb-2" id="editName" name="name" placeholder="Name" required onchange="checkPreset(this.value, 'edit')">
        <textarea class="form-control mb-2" id="editDescription" name="description" placeholder="Description"></textarea>
        <input class="form-control mb-2" id="editShortDescription" name="short_description" placeholder="Short Description (for category pages)">
        <input class="form-control mb-2" id="editSeoTitle" name="seo_title" placeholder="SEO Title">
        <textarea class="form-control mb-2" id="editSeoDescription" name="seo_description" placeholder="SEO Description" rows="3"></textarea>
        <div class="mb-2">
          <button type="button" class="btn btn-outline-info btn-sm" onclick="loadPreset('edit')">
            <i class="fas fa-magic"></i> Load Preset
          </button>
          <small class="text-muted ms-2">Auto-fill metadata from presets</small>
        </div>
        <div class="mb-2">
          <label for="iconSelectEdit" class="form-label">Icon</label>
          <select class="form-select" name="icon" id="iconSelectEdit" onchange="document.getElementById('iconPreviewEdit').className='fa-solid '+this.value">
            <optgroup label="General">
              <option value="fa-folder">Folder (default)</option>
              <option value="fa-store">Store</option>
              <option value="fa-briefcase">Briefcase</option>
              <option value="fa-building">Building</option>
              <option value="fa-users">Users</option>
              <option value="fa-user">User</option>
              <option value="fa-user-tie">User Tie</option>
              <option value="fa-user-graduate">User Graduate</option>
              <option value="fa-user-md">Doctor</option>
              <option value="fa-user-nurse">Nurse</option>
              <option value="fa-user-shield">User Shield</option>
              <option value="fa-user-cog">User Cog</option>
              <option value="fa-user-friends">User Friends</option>
            </optgroup>
            <optgroup label="Food & Hospitality">
              <option value="fa-utensils">Restaurant</option>
              <option value="fa-birthday-cake">Catering</option>
              <option value="fa-coffee">Coffee</option>
              <option value="fa-ice-cream">Ice Cream</option>
              <option value="fa-pizza-slice">Pizza</option>
              <option value="fa-hamburger">Hamburger</option>
              <option value="fa-wine-glass">Wine Glass</option>
              <option value="fa-hotel">Hotel</option>
            </optgroup>
            <optgroup label="Retail & Shopping">
              <option value="fa-shopping-bag">Shopping Bag</option>
              <option value="fa-shopping-cart">Shopping Cart</option>
              <option value="fa-tshirt">Clothing</option>
              <option value="fa-gem">Jewelry</option>
              <option value="fa-gift">Gift</option>
              <option value="fa-couch">Furniture</option>
              <option value="fa-mobile-alt">Mobile</option>
              <option value="fa-laptop">Electronics</option>
            </optgroup>
            <optgroup label="Education">
              <option value="fa-graduation-cap">Education</option>
              <option value="fa-book">Book</option>
              <option value="fa-chalkboard-teacher">Teacher</option>
              <option value="fa-school">School</option>
              <option value="fa-pencil-alt">Pencil</option>
            </optgroup>
            <optgroup label="Healthcare & Wellness">
              <option value="fa-heartbeat">Healthcare</option>
              <option value="fa-stethoscope">Stethoscope</option>
              <option value="fa-ambulance">Ambulance</option>
              <option value="fa-spa">Spa</option>
              <option value="fa-dumbbell">Fitness</option>
              <option value="fa-medkit">Medkit</option>
              <option value="fa-tooth">Dentist</option>
              <option value="fa-capsules">Pharmacy</option>
            </optgroup>
            <optgroup label="Professional Services">
              <option value="fa-balance-scale">Legal</option>
              <option value="fa-money-bill-wave">Finance</option>
              <option value="fa-hammer">Construction</option>
              <option value="fa-gavel">Gavel</option>
              <option value="fa-briefcase-medical">Medical Briefcase</option>
              <option value="fa-chart-line">Chart Line</option>
              <option value="fa-cogs">Cogs</option>
              <option value="fa-lightbulb">Consulting</option>
              <option value="fa-paint-brush">Design</option>
              <option value="fa-camera">Photography</option>
              <option value="fa-microphone">Media</option>
              <option value="fa-bullhorn">Marketing</option>
              <option value="fa-globe">Web/IT</option>
              <option value="fa-code">Developer</option>
            </optgroup>
            <optgroup label="Real Estate & Home">
              <option value="fa-home">Home</option>
              <option value="fa-door-open">Door</option>
              <option value="fa-key">Key</option>
              <option value="fa-city">City</option>
              <option value="fa-tree">Landscaping</option>
              <option value="fa-broom">Cleaning</option>
              <option value="fa-tools">Repairs</option>
            </optgroup>
            <optgroup label="Events & Entertainment">
              <option value="fa-calendar-alt">Events</option>
              <option value="fa-film">Film</option>
              <option value="fa-music">Music</option>
              <option value="fa-futbol">Sports</option>
              <option value="fa-theater-masks">Theater</option>
              <option value="fa-birthday-cake">Party</option>
              <option value="fa-gift">Gift</option>
            </optgroup>
            <optgroup label="Travel & Automotive">
              <option value="fa-plane">Travel</option>
              <option value="fa-car">Car</option>
              <option value="fa-bus">Bus</option>
              <option value="fa-ship">Ship</option>
              <option value="fa-taxi">Taxi</option>
              <option value="fa-bicycle">Bicycle</option>
              <option value="fa-motorcycle">Motorcycle</option>
              <option value="fa-train">Train</option>
            </optgroup>
            <optgroup label="Community & Religion">
              <option value="fa-synagogue">Synagogue</option>
              <option value="fa-hands-helping">Charity</option>
              <option value="fa-praying-hands">Praying Hands</option>
              <option value="fa-star-of-david">Star of David</option>
              <option value="fa-people-carry">Community</option>
            </optgroup>
            <optgroup label="Other">
              <option value="fa-store">Other</option>
              <option value="fa-question">Question</option>
              <option value="fa-info">Info</option>
            </optgroup>
          </select>
          <div class="mt-2"><i id="iconPreviewEdit" class="fa-solid fa-folder fa-2x"></i> <span class="text-muted small">Live Preview</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editCategory(cat) {
  document.getElementById('editId').value = cat.id;
  document.getElementById('editName').value = cat.name;
  document.getElementById('editDescription').value = cat.description;
  document.getElementById('editShortDescription').value = cat.short_description || '';
  document.getElementById('editSeoTitle').value = cat.seo_title || '';
  document.getElementById('editSeoDescription').value = cat.seo_description || '';
  document.getElementById('iconSelectEdit').value = cat.icon || 'fa-folder';
  document.getElementById('iconPreviewEdit').className = 'fa-solid ' + (cat.icon || 'fa-folder');
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Category preset functionality
const categoryPresets = <?php echo json_encode($categoryPresets); ?>;

function checkPreset(categoryName, mode = 'add') {
  const preset = categoryPresets[categoryName];
  if (preset) {
    const prefix = mode === 'edit' ? 'edit' : 'add';
    document.getElementById(prefix + 'ShortDescription').value = preset.shortDesc;
    document.getElementById(prefix + 'SeoTitle').value = preset.seoTitle;
    document.getElementById(prefix + 'SeoDescription').value = preset.seoDesc;
    
    // Show success message
    showPresetMessage('Preset loaded automatically!', 'success');
  }
}

function loadPreset(mode = 'add') {
  const prefix = mode === 'edit' ? 'edit' : 'add';
  const categoryName = document.getElementById(prefix + 'Name').value;
  
  if (!categoryName) {
    showPresetMessage('Please enter a category name first', 'warning');
    return;
  }
  
  const preset = categoryPresets[categoryName];
  if (preset) {
    document.getElementById(prefix + 'ShortDescription').value = preset.shortDesc;
    document.getElementById(prefix + 'SeoTitle').value = preset.seoTitle;
    document.getElementById(prefix + 'SeoDescription').value = preset.seoDesc;
    showPresetMessage('Preset loaded successfully!', 'success');
  } else {
    showPresetMessage('No preset found for this category name', 'info');
  }
}

function showPresetMessage(message, type) {
  // Create temporary alert
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  // Insert at top of modal body
  const modalBody = document.querySelector('.modal-body');
  modalBody.insertBefore(alertDiv, modalBody.firstChild);
  
  // Auto-remove after 3 seconds
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove();
    }
  }, 3000);
}
// Simple search functionality
const searchInput = document.getElementById('searchCategory');
if (searchInput) {
  searchInput.addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchText) ? '' : 'none';
    });
  });
}
</script>
</body>
</html> 