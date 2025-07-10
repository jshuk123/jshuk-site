<?php
/**
 * Auto-Generate Carousel Ads from Premium Plus Businesses
 * This script can be run via cron job to automatically populate carousel
 * with ads from Premium Plus businesses when there aren't enough manual ads
 */

require_once '../config/config.php';

// Configuration
$min_ads = 3; // Minimum number of ads to show
$max_auto_ads = 5; // Maximum auto-generated ads to create

try {
    // Count existing active ads
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM carousel_ads 
        WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW())
    ");
    $stmt->execute();
    $active_count = $stmt->fetchColumn();
    
    // If we have enough ads, exit
    if ($active_count >= $min_ads) {
        echo "✅ Sufficient carousel ads found ({$active_count}). No action needed.\n";
        exit(0);
    }
    
    // Get Premium Plus businesses that don't have carousel ads yet
    $stmt = $pdo->prepare("
        SELECT b.id, b.business_name, b.description, b.category_id, c.name as category_name
        FROM businesses b
        LEFT JOIN business_categories c ON b.category_id = c.id
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN carousel_ads ca ON b.id = ca.business_id
        WHERE b.status = 'active' 
        AND u.subscription_tier = 'premium_plus'
        AND ca.id IS NULL
        ORDER BY b.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$max_auto_ads]);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($businesses)) {
        echo "⚠️ No Premium Plus businesses found without carousel ads.\n";
        exit(0);
    }
    
    $created_count = 0;
    
    foreach ($businesses as $business) {
        // Create a simple carousel ad for this business
        $title = $business['business_name'];
        $subtitle = "Premium Plus Business - " . ($business['category_name'] ?? 'Local Business');
        
        // Generate a placeholder image path (you might want to use actual business images)
        $image_path = 'uploads/carousel/auto_' . time() . '_' . $business['id'] . '.jpg';
        
        // Create business page URL
        $cta_url = BASE_PATH . 'business.php?id=' . $business['id'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO carousel_ads (
                    title, subtitle, image_path, cta_text, cta_url, 
                    active, is_auto_generated, business_id, position
                ) VALUES (?, ?, ?, ?, ?, 1, 1, ?, ?)
            ");
            
            $position = $active_count + $created_count + 1;
            $stmt->execute([
                $title,
                $subtitle,
                $image_path,
                'View Business',
                $cta_url,
                $business['id'],
                $position
            ]);
            
            $created_count++;
            echo "✅ Created carousel ad for: {$business['business_name']}\n";
            
        } catch (PDOException $e) {
            echo "❌ Error creating ad for {$business['business_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "🎉 Successfully created {$created_count} auto-generated carousel ads.\n";
    echo "📊 Total active ads: " . ($active_count + $created_count) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 