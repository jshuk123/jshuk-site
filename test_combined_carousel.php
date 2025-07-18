<?php
/**
 * Test Combined Carousel Functionality
 * This script tests the combined carousel that merges sponsored slides and featured businesses
 */

require_once 'config/config.php';

echo "<h1>🧪 Testing Combined Carousel Functionality</h1>";
echo "<p>This test verifies that the Community Highlights carousel can fetch and combine data from both sources.</p>";

try {
    // Test 1: Check carousel_slides table
    echo "<h2>📊 Test 1: Carousel Slides (Enhanced Carousel Manager)</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            subtitle,
            image_url,
            cta_text,
            cta_link,
            priority,
            sponsored,
            active,
            zone
        FROM carousel_slides
        WHERE active = 1
          AND zone = 'homepage'
        ORDER BY priority DESC, id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $carousel_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Found " . count($carousel_slides) . " active carousel slides:</strong></p>";
    if (!empty($carousel_slides)) {
        echo "<ul>";
        foreach ($carousel_slides as $slide) {
            echo "<li><strong>{$slide['title']}</strong> (Priority: {$slide['priority']}, Sponsored: " . ($slide['sponsored'] ? 'Yes' : 'No') . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>⚠️ No carousel slides found</p>";
    }
    
    // Test 2: Check featured businesses
    echo "<h2>🏢 Test 2: Featured Businesses (Directory)</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.business_name,
            c.name as category_name,
            u.subscription_tier,
            b.status
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.status = 'active' 
        AND u.subscription_tier IN ('premium', 'premium_plus')
        ORDER BY 
            CASE u.subscription_tier 
                WHEN 'premium_plus' THEN 1 
                WHEN 'premium' THEN 2 
                ELSE 3 
            END,
            b.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $featured_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Found " . count($featured_businesses) . " featured businesses:</strong></p>";
    if (!empty($featured_businesses)) {
        echo "<ul>";
        foreach ($featured_businesses as $business) {
            echo "<li><strong>{$business['business_name']}</strong> - {$business['category_name']} ({$business['subscription_tier']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>⚠️ No featured businesses found</p>";
    }
    
    // Test 3: Simulate the combined logic
    echo "<h2>🔄 Test 3: Combined Logic Simulation</h2>";
    
    $all_slides = [];
    
    // Add carousel slides
    foreach ($carousel_slides as $slide) {
        $slide['priority'] = $slide['priority'] ?? 0;
        $slide['slide_type'] = 'carousel_slide';
        $all_slides[] = $slide;
    }
    
    // Add featured businesses with priority mapping
    foreach ($featured_businesses as $business) {
        $priority = 0;
        if ($business['subscription_tier'] === 'premium_plus') {
            $priority = 6;
        } elseif ($business['subscription_tier'] === 'premium') {
            $priority = 5;
        } else {
            $priority = 4;
        }
        
        $all_slides[] = [
            'id' => $business['id'],
            'title' => $business['business_name'],
            'subtitle' => $business['category_name'],
            'priority' => $priority,
            'slide_type' => 'featured_business',
            'subscription_tier' => $business['subscription_tier']
        ];
    }
    
    // Sort by priority
    usort($all_slides, function($a, $b) {
        return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
    });
    
    echo "<p><strong>Combined result (" . count($all_slides) . " total items):</strong></p>";
    if (!empty($all_slides)) {
        echo "<ol>";
        foreach ($all_slides as $slide) {
            $type_icon = $slide['slide_type'] === 'carousel_slide' ? '🎠' : '🏢';
            $priority_display = $slide['priority'] ?? 0;
            echo "<li>{$type_icon} <strong>{$slide['title']}</strong> (Priority: {$priority_display}, Type: {$slide['slide_type']})</li>";
        }
        echo "</ol>";
    } else {
        echo "<p style='color:red'>❌ No combined slides found</p>";
    }
    
    // Test 4: Check if the featured_showcase.php file exists
    echo "<h2>📁 Test 4: File Structure</h2>";
    
    $featured_showcase_file = 'sections/featured_showcase.php';
    if (file_exists($featured_showcase_file)) {
        echo "<p style='color:green'>✅ Featured showcase file exists: {$featured_showcase_file}</p>";
        
        // Check if it includes the combined logic
        $file_content = file_get_contents($featured_showcase_file);
        if (strpos($file_content, 'QUERY A: Get sponsored slides') !== false) {
            echo "<p style='color:green'>✅ Contains sponsored slides query</p>";
        } else {
            echo "<p style='color:red'>❌ Missing sponsored slides query</p>";
        }
        
        if (strpos($file_content, 'QUERY B: Get featured businesses') !== false) {
            echo "<p style='color:green'>✅ Contains featured businesses query</p>";
        } else {
            echo "<p style='color:red'>❌ Missing featured businesses query</p>";
        }
        
        if (strpos($file_content, 'slide_type === \'featured_business\'') !== false) {
            echo "<p style='color:green'>✅ Contains conditional logic for featured businesses</p>";
        } else {
            echo "<p style='color:red'>❌ Missing conditional logic for featured businesses</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Featured showcase file not found: {$featured_showcase_file}</p>";
    }
    
    echo "<h2>✅ Test Summary</h2>";
    echo "<p>The combined carousel should now display:</p>";
    echo "<ul>";
    echo "<li>🎠 Sponsored slides from your Enhanced Carousel Manager (with custom priority)</li>";
    echo "<li>🏢 Featured businesses from your directory (Premium/Premium+ tier)</li>";
    echo "<li>📊 All sorted by priority (highest first)</li>";
    echo "<li>🏷️ 'Featured' tag only on business slides</li>";
    echo "<li>🔗 Proper links to business profiles or custom URLs</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php'>🏠 View Homepage</a> | <a href='admin/enhanced_carousel_manager.php'>⚙️ Carousel Manager</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ General error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 