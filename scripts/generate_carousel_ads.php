<?php
/**
 * Auto-Generate Carousel Slides from Premium Plus Businesses
 * This script can be run via cron job to automatically populate carousel
 * with slides from Premium Plus businesses when there aren't enough manual slides
 */

require_once '../config/config.php';

// Configuration
$min_slides = 3; // Minimum number of slides to show
$max_auto_slides = 5; // Maximum auto-generated slides to create

try {
    // Count existing active slides
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM carousel_slides 
        WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())
    ");
    $stmt->execute();
    $active_count = $stmt->fetchColumn();
    
    // If we have enough slides, exit
    if ($active_count >= $min_slides) {
        echo "✅ Sufficient carousel slides found ({$active_count}). No action needed.\n";
        exit(0);
    }
    
    // Get Premium Plus businesses that don't have carousel slides yet
    $stmt = $pdo->prepare("
        SELECT b.id, b.business_name, b.description, b.category_id, c.name as category_name
        FROM businesses b
        LEFT JOIN business_categories c ON b.category_id = c.id
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN carousel_slides cs ON b.id = cs.business_id
        WHERE b.status = 'active' 
        AND u.subscription_tier = 'premium_plus'
        AND cs.id IS NULL
        ORDER BY b.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$max_auto_slides]);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($businesses)) {
        echo "⚠️ No Premium Plus businesses found without carousel slides.\n";
        exit(0);
    }
    
    $created_count = 0;
    
    foreach ($businesses as $business) {
        // Create a simple carousel slide for this business
        $title = $business['business_name'];
        $subtitle = "Premium Plus Business - " . ($business['category_name'] ?? 'Local Business');
        
        // Generate a placeholder image path (you might want to use actual business images)
        $image_url = 'uploads/carousel/auto_' . time() . '_' . $business['id'] . '.jpg';
        
        // Create business page URL
        $cta_link = BASE_PATH . 'business.php?id=' . $business['id'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO carousel_slides (
                    title, subtitle, image_url, cta_text, cta_link, 
                    active, sponsored, business_id, priority, zone
                ) VALUES (?, ?, ?, ?, ?, 1, 0, ?, ?, 'homepage')
            ");
            
            $priority = $active_count + $created_count + 1;
            $stmt->execute([
                $title,
                $subtitle,
                $image_url,
                'View Business',
                $cta_link,
                $business['id'],
                $priority
            ]);
            
            $created_count++;
            echo "✅ Created carousel slide for: {$business['business_name']}\n";
            
        } catch (PDOException $e) {
            echo "❌ Error creating slide for {$business['business_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "🎉 Successfully created {$created_count} auto-generated carousel slides.\n";
    echo "📊 Total active slides: " . ($active_count + $created_count) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 