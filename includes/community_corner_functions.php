<?php
/**
 * Community Corner Functions
 * Handles all community corner content management and display
 */

require_once 'config/config.php';

/**
 * Get featured community corner items for homepage display
 * @param int $limit Number of items to return (default: 4)
 * @return array Array of community corner items
 */
function getFeaturedCommunityCornerItems($limit = 4) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, body_text, type, emoji, link_url, link_text, priority
            FROM community_corner 
            WHERE is_featured = 1 
            AND is_active = 1 
            AND (expire_date IS NULL OR expire_date >= CURDATE())
            ORDER BY priority DESC, RAND()
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no featured items, get some active items as fallback
        if (empty($items)) {
            $stmt = $pdo->prepare("
                SELECT id, title, body_text, type, emoji, link_url, link_text, priority
                FROM community_corner 
                WHERE is_active = 1 
                AND (expire_date IS NULL OR expire_date >= CURDATE())
                ORDER BY RAND()
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $items;
        
    } catch (PDOException $e) {
        error_log("Error fetching community corner items: " . $e->getMessage());
        return [];
    }
}

/**
 * Get community corner items by type
 * @param string $type Type of content (gemach, lost_found, etc.)
 * @param int $limit Number of items to return (default: 10)
 * @return array Array of community corner items
 */
function getCommunityCornerItemsByType($type, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, body_text, type, emoji, link_url, link_text, date_added, views_count
            FROM community_corner 
            WHERE type = ? 
            AND is_active = 1 
            AND (expire_date IS NULL OR expire_date >= CURDATE())
            ORDER BY date_added DESC
            LIMIT ?
        ");
        $stmt->execute([$type, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching community corner items by type: " . $e->getMessage());
        return [];
    }
}

/**
 * Increment view count for a community corner item
 * @param int $itemId ID of the community corner item
 */
function incrementCommunityCornerViews($itemId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE community_corner 
            SET views_count = views_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$itemId]);
        
    } catch (PDOException $e) {
        error_log("Error incrementing community corner views: " . $e->getMessage());
    }
}

/**
 * Increment click count for a community corner item
 * @param int $itemId ID of the community corner item
 */
function incrementCommunityCornerClicks($itemId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE community_corner 
            SET clicks_count = clicks_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$itemId]);
        
    } catch (PDOException $e) {
        error_log("Error incrementing community corner clicks: " . $e->getMessage());
    }
}

/**
 * Get type display name and emoji mapping
 * @return array Array mapping type to display name and default emoji
 */
function getCommunityCornerTypeInfo() {
    return [
        'gemach' => ['name' => 'Gemachim', 'emoji' => '🍼', 'color' => '#4CAF50'],
        'lost_found' => ['name' => 'Lost & Found', 'emoji' => '🎒', 'color' => '#FF9800'],
        'simcha' => ['name' => 'Simchas', 'emoji' => '🎉', 'color' => '#E91E63'],
        'charity_alert' => ['name' => 'Charity Alerts', 'emoji' => '❤️', 'color' => '#F44336'],
        'divrei_torah' => ['name' => 'Divrei Torah', 'emoji' => '🕯️', 'color' => '#9C27B0'],
        'ask_rabbi' => ['name' => 'Ask the Rabbi', 'emoji' => '📜', 'color' => '#3F51B5'],
        'volunteer' => ['name' => 'Volunteer Opportunities', 'emoji' => '🤝', 'color' => '#2196F3'],
        'photo_week' => ['name' => 'Photo of the Week', 'emoji' => '📸', 'color' => '#00BCD4']
    ];
}

/**
 * Get random community corner item for display
 * @return array|null Single community corner item or null
 */
function getRandomCommunityCornerItem() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, body_text, type, emoji, link_url, link_text
            FROM community_corner 
            WHERE is_active = 1 
            AND (expire_date IS NULL OR expire_date >= CURDATE())
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching random community corner item: " . $e->getMessage());
        return null;
    }
}

/**
 * Get community corner statistics
 * @return array Array of statistics
 */
function getCommunityCornerStats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total items
        $stmt = $pdo->query("SELECT COUNT(*) FROM community_corner WHERE is_active = 1");
        $stats['total_items'] = $stmt->fetchColumn();
        
        // Items by type
        $stmt = $pdo->query("
            SELECT type, COUNT(*) as count 
            FROM community_corner 
            WHERE is_active = 1 
            GROUP BY type
        ");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total views
        $stmt = $pdo->query("SELECT SUM(views_count) FROM community_corner");
        $stats['total_views'] = $stmt->fetchColumn() ?: 0;
        
        // Total clicks
        $stmt = $pdo->query("SELECT SUM(clicks_count) FROM community_corner");
        $stats['total_clicks'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Error fetching community corner stats: " . $e->getMessage());
        return [
            'total_items' => 0,
            'by_type' => [],
            'total_views' => 0,
            'total_clicks' => 0
        ];
    }
}
?> 