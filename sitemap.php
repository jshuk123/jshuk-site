<?php
header('Content-Type: application/xml; charset=utf-8');
require_once 'config/config.php';
require_once 'config/constants.php';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    <!-- Main Pages -->
    <url>
        <loc>https://jshuk.com/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/businesses.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/about.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/recruitment.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/classifieds.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/categories.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/london.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Category Pages -->
    <?php
    try {
        $stmt = $pdo->query("SELECT id, name, slug FROM business_categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as $category) {
            echo '<url>' . "\n";
            echo '    <loc>https://jshuk.com/category.php?id=' . $category['id'] . '&amp;slug=' . urlencode($category['slug']) . '</loc>' . "\n";
            echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>0.7</priority>' . "\n";
            echo '</url>' . "\n";
        }
    } catch (PDOException $e) {
        // Handle error silently
    }
    ?>

    <!-- Business Pages -->
    <?php
    try {
        $stmt = $pdo->query("SELECT id, business_name, slug, updated_at FROM businesses WHERE status = 'active' ORDER BY updated_at DESC");
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($businesses as $business) {
            $lastmod = $business['updated_at'] ? date('Y-m-d', strtotime($business['updated_at'])) : date('Y-m-d');
            echo '<url>' . "\n";
            echo '    <loc>https://jshuk.com/business.php?id=' . $business['id'] . '&amp;slug=' . urlencode($business['slug']) . '</loc>' . "\n";
            echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>0.6</priority>' . "\n";
            echo '</url>' . "\n";
        }
    } catch (PDOException $e) {
        // Handle error silently
    }
    ?>

    <!-- Location-specific pages -->
    <url>
        <loc>https://jshuk.com/businesses.php?location=london</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/businesses.php?location=manchester</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>https://jshuk.com/businesses.php?location=stamford-hill</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

</urlset> 