<?php
require_once 'config/config.php';
require_once 'includes/database.php';

// Get categories for the demo
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name LIMIT 10");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback categories if database fails
    $categories = [
        ['id' => 1, 'name' => 'Restaurants'],
        ['id' => 2, 'name' => 'Healthcare'],
        ['id' => 3, 'name' => 'Education'],
        ['id' => 4, 'name' => 'Shopping'],
        ['id' => 5, 'name' => 'Services']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Search Bar Demo - Jshuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/css/components/search-bar.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f7faff 0%, #e3e6ed 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .demo-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .demo-title {
            color: #1a3353;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }
        .demo-description {
            color: #6c757d;
            text-align: center;
            margin-bottom: 2rem;
        }
        .hero-demo {
            background: linear-gradient(135deg, #1a3353 0%, #2C4E6D 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        .hero-demo h2 {
            color: #FFD700;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="text-center mb-5" style="color: #1a3353; font-weight: 700;">
                    🎨 Unified Search Bar Design Demo
                </h1>
                
                <!-- Hero Demo Section -->
                <div class="hero-demo">
                    <h2 class="text-center">Hero Section with Frosted Glass Effect</h2>
                    <p class="text-center mb-4">The unified search bar adapts beautifully to hero backgrounds with a modern frosted glass effect.</p>
                    
                    <form action="/businesses.php" method="GET" class="unified-search-bar hero-unified-search" role="search">
                        <div class="search-segment location-segment">
                            <i class="fas fa-map-marker-alt"></i>
                            <select name="location" class="form-select" aria-label="Select location">
                                <option value="" disabled selected>Select a Location</option>
                                <option value="manchester">Manchester</option>
                                <option value="london">London</option>
                                <option value="stamford-hill">Stamford Hill</option>
                            </select>
                        </div>
                        <div class="search-segment category-segment">
                            <i class="fas fa-folder"></i>
                            <select name="category" class="form-select" aria-label="Select category">
                                <option value="" disabled selected>Select a Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="search-segment keyword-segment">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search businesses..." />
                        </div>
                        <button type="submit" class="search-button-unified" aria-label="Search">
                            <i class="fa fa-search"></i>
                            <span class="d-none d-md-inline">Search</span>
                        </button>
                    </form>
                </div>

                <!-- Standard Demo Section -->
                <div class="demo-section">
                    <h3 class="demo-title">Standard Search Bar</h3>
                    <p class="demo-description">Clean, modern unified design that looks like one seamless component.</p>
                    
                    <form action="/businesses.php" method="GET" class="unified-search-bar" role="search">
                        <div class="search-segment location-segment">
                            <i class="fas fa-map-marker-alt"></i>
                            <select name="location" class="form-select" aria-label="Select location">
                                <option value="" disabled selected>Select a Location</option>
                                <option value="manchester">Manchester</option>
                                <option value="london">London</option>
                                <option value="stamford-hill">Stamford Hill</option>
                            </select>
                        </div>
                        <div class="search-segment category-segment">
                            <i class="fas fa-folder"></i>
                            <select name="category" class="form-select" aria-label="Select category">
                                <option value="" disabled selected>Select a Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="search-segment keyword-segment">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search businesses..." />
                        </div>
                        <button type="submit" class="search-button-unified" aria-label="Search">
                            <i class="fa fa-search"></i>
                            <span class="d-none d-md-inline">Search</span>
                        </button>
                    </form>
                </div>

                <!-- Classifieds Demo Section -->
                <div class="demo-section">
                    <h3 class="demo-title">Classifieds Search Bar</h3>
                    <p class="demo-description">Same unified design adapted for classifieds with category-first layout.</p>
                    
                    <form action="/classifieds.php" method="GET" class="unified-search-bar" role="search">
                        <div class="search-segment category-segment">
                            <i class="fas fa-tags"></i>
                            <select name="category" class="form-select" aria-label="Select category">
                                <option value="" disabled selected>Select Category</option>
                                <option value="free-stuff">♻️ Free Stuff</option>
                                <option value="furniture">Furniture</option>
                                <option value="electronics">Electronics</option>
                                <option value="books-seforim">Books & Seforim</option>
                                <option value="clothing">Clothing</option>
                            </select>
                        </div>
                        <div class="search-segment location-segment">
                            <i class="fas fa-map-marker-alt"></i>
                            <select name="location" class="form-select" aria-label="Select location">
                                <option value="" disabled selected>Select Location</option>
                                <option value="manchester">Manchester</option>
                                <option value="london">London</option>
                                <option value="leeds">Leeds</option>
                            </select>
                        </div>
                        <div class="search-segment keyword-segment">
                            <i class="fas fa-search"></i>
                            <input type="text" name="q" class="form-control" placeholder="Search classifieds..." />
                        </div>
                        <button type="submit" class="search-button-unified" aria-label="Search">
                            <i class="fa fa-search"></i>
                            <span class="d-none d-md-inline">Search</span>
                        </button>
                    </form>
                </div>

                <!-- Design Comparison -->
                <div class="demo-section">
                    <h3 class="demo-title">Design Transformation</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-danger">❌ Old Design (Cramped)</h5>
                            <p class="text-muted">Separate boxes that felt disconnected and cramped.</p>
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex flex-wrap gap-2">
                                    <select class="form-select" style="width: auto;">
                                        <option>📍 Location</option>
                                    </select>
                                    <select class="form-select" style="width: auto;">
                                        <option>🗂 Category</option>
                                    </select>
                                    <input type="text" class="form-control" style="width: auto;" placeholder="🔍 Search...">
                                    <button class="btn btn-warning">Search</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-success">✅ New Design (Unified)</h5>
                            <p class="text-muted">One seamless component with subtle dividers and modern styling.</p>
                            <div class="border rounded p-3 bg-light">
                                <div class="unified-search-bar" style="max-width: 100%;">
                                    <div class="search-segment location-segment">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <select class="form-select">
                                            <option>Location</option>
                                        </select>
                                    </div>
                                    <div class="search-segment category-segment">
                                        <i class="fas fa-folder"></i>
                                        <select class="form-select">
                                            <option>Category</option>
                                        </select>
                                    </div>
                                    <div class="search-segment keyword-segment">
                                        <i class="fas fa-search"></i>
                                        <input type="text" class="form-control" placeholder="Search...">
                                    </div>
                                    <button class="search-button-unified">
                                        <i class="fa fa-search"></i>
                                        <span>Search</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features List -->
                <div class="demo-section">
                    <h3 class="demo-title">Key Features</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-paint-brush fa-2x text-primary mb-3"></i>
                                <h5>Modern Design</h5>
                                <p class="text-muted">Sleek, unified appearance that looks like one component</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-mobile-alt fa-2x text-success mb-3"></i>
                                <h5>Responsive</h5>
                                <p class="text-muted">Adapts beautifully to mobile and tablet screens</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-magic fa-2x text-warning mb-3"></i>
                                <h5>Frosted Glass</h5>
                                <p class="text-muted">Special hero styling with backdrop blur effects</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Initialize search bar functionality
        document.addEventListener('DOMContentLoaded', function() {
            initializeSearchBar();
        });
    </script>
</body>
</html> 