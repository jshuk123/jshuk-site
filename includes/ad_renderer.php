<?php
/**
 * Enhanced Ad Renderer for JShuk
 * Handles loading and displaying ads based on zone, category, location, and priority
 */

// Only require config if not already loaded
if (!defined('APP_DEBUG')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Render an ad for a specific zone with optional targeting
 * 
 * @param string $zone The ad zone (header, sidebar, footer, carousel, inline)
 * @param int|null $category_id Optional category ID for targeting
 * @param string|null $location Optional location for targeting
 * @param array $options Additional options for rendering
 * @return string HTML output for the ad
 */
function renderAd($zone, $category_id = null, $location = null, $options = []) {
    global $pdo;
    $now = date('Y-m-d');

    try {
        $sql = "SELECT * FROM ads 
                WHERE zone = :zone 
                  AND status = 'active' 
                  AND start_date <= :start 
                  AND end_date >= :end";

        $params = [
            ':zone' => $zone,
            ':start' => $now,
            ':end' => $now
        ];

        // Only add filters if values are NOT null
        if (!is_null($category_id)) {
            $sql .= " AND (category_id = :cat OR category_id IS NULL)";
            $params[':cat'] = $category_id;
        }

        if (!is_null($location)) {
            $sql .= " AND (location = :loc OR location IS NULL)";
            $params[':loc'] = $location;
        }

        $sql .= " ORDER BY priority DESC LIMIT 1";

        error_log("🛠 renderAd SQL: $sql");
        error_log("🛠 Params: " . json_encode($params));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ad = $stmt->fetch();

        if (!$ad) {
            return '<!-- No matching ad found -->';
        }

        logAdView($ad['id']);
        return generateAdHTML($ad, $zone, $options);

    } catch (PDOException $e) {
        error_log("🔥 renderAd SQL error: " . $e->getMessage());
        return "<!-- DB error: " . $e->getMessage() . " -->";
    }
}

/**
 * Generate HTML for an ad
 * 
 * @param array $ad The ad data
 * @param string $zone The ad zone
 * @param array $options Additional options
 * @return string HTML output
 */
function generateAdHTML($ad, $zone, $options = []) {
    $defaults = [
        'show_label' => true,
        'class' => '',
        'style' => '',
        'track_clicks' => true
    ];
    $options = array_merge($defaults, $options);
    
    // Build image path
    $imagePath = $ad['image_url'];
    if (!preg_match('/^https?:\/\//', $imagePath)) {
        $imagePath = '/uploads/ads/' . ltrim($imagePath, '/');
    }
    
    // DEBUG: Log image path processing
    error_log("Ad Debug - Image path: original='" . ($ad['image_url'] ?? 'NULL') . "', processed='$imagePath'");
    
    // Build link with click tracking
    $linkUrl = $ad['link_url'];
    if ($options['track_clicks']) {
        $linkUrl = BASE_PATH . 'track_ad_click.php?id=' . $ad['id'] . '&url=' . urlencode($ad['link_url']);
    }
    
    // Zone-specific styling
    $zoneClasses = [
        'header' => 'ad-header',
        'sidebar' => 'ad-sidebar',
        'footer' => 'ad-footer',
        'carousel' => 'ad-carousel',
        'inline' => 'ad-inline'
    ];
    
    $zoneClass = $zoneClasses[$zone] ?? 'ad-generic';
    $containerClass = "ad-container {$zoneClass} {$options['class']}";
    
    // Add basic CSS to ensure visibility
    $basicStyle = "display: block; margin: 10px 0; padding: 10px; border-radius: 8px; text-align: center;";
    $finalStyle = $basicStyle . ' ' . $options['style'];
    
    $html = "<figure class='{$containerClass}' style='{$finalStyle}'>";
    if ($options['show_label']) {
        $html .= "<span class='ad-label'>ADVERTISEMENT</span>";
    }
    $html .= "<a href='" . htmlspecialchars($linkUrl) . "' target='_blank' rel='noopener' class='ad-link' style='display: block;'>";
    $html .= "<img src='" . htmlspecialchars($imagePath) . "' alt='" . htmlspecialchars($ad['title']) . "' class='ad-image' style='width: 100%; height: auto;'>";
    $html .= "</a>";
    if (!empty($ad['cta_text'])) {
        $cta = htmlspecialchars($ad['cta_text']);
        $html .= "<figcaption class='text-center mt-2'><a href='" . htmlspecialchars($linkUrl) . "' class='btn btn-sm btn-primary'>{$cta}</a></figcaption>";
    }
    $html .= "</figure>";
    
    // DEBUG: Log the generated HTML
    error_log("Ad Debug - Generated HTML for zone '$zone': " . substr($html, 0, 200) . "...");
    
    return $html;
}

/**
 * Get default ad space when no ads are available
 * 
 * @param string $zone The ad zone
 * @param array $options Additional options
 * @return string HTML for empty ad space
 */
function getDefaultAdSpace($zone, $options = []) {
    $defaults = [
        'show_placeholder' => true,
        'class' => '',
        'style' => ''
    ];
    $options = array_merge($defaults, $options);
    
    if (!$options['show_placeholder']) {
        return '';
    }
    
    $zoneClasses = [
        'header' => 'ad-header-placeholder',
        'sidebar' => 'ad-sidebar-placeholder',
        'footer' => 'ad-footer-placeholder',
        'carousel' => 'ad-carousel-placeholder',
        'inline' => 'ad-inline-placeholder'
    ];
    
    $zoneClass = $zoneClasses[$zone] ?? 'ad-placeholder';
    $containerClass = "ad-placeholder {$zoneClass} {$options['class']}";
    
    // Enhanced placeholder with better visibility
    $placeholderStyle = "display: block; margin: 10px 0; padding: 20px; border: 2px dashed #ccc; background: #f8f9fa; border-radius: 8px; text-align: center; color: #6c757d;";
    $finalStyle = $placeholderStyle . ' ' . $options['style'];
    
    return "<div class='{$containerClass}' style='{$finalStyle}'>
                <div class='ad-placeholder-content'>
                    <i class='fas fa-ad fa-2x mb-2'></i>
                    <div><strong>Ad Space Available</strong></div>
                    <small>Zone: {$zone}</small>
                </div>
            </div>";
}

/**
 * Log ad view for analytics
 * 
 * @param int $adId The ad ID
 */
function logAdView($adId) {
    global $pdo;
    
    try {
        $today = date('Y-m-d');
        
        // Try to update existing record
        $stmt = $pdo->prepare("
            INSERT INTO ad_stats (ad_id, date, views) 
            VALUES (:ad_id, :date, 1)
            ON DUPLICATE KEY UPDATE views = views + 1
        ");
        
        $stmt->execute([
            ':ad_id' => $adId,
            ':date' => $today
        ]);
        
    } catch (PDOException $e) {
        // Silently fail for analytics - don't break the main functionality
        error_log("Failed to log ad view: " . $e->getMessage());
    }
}

/**
 * Get multiple ads for a zone (useful for carousels)
 * 
 * @param string $zone The ad zone
 * @param int $limit Number of ads to return
 * @param int|null $category_id Optional category ID
 * @param string|null $location Optional location
 * @return array Array of ad HTML strings
 */
function renderMultipleAds($zone, $limit = 3, $category_id = null, $location = null) {
    global $pdo;
    
    try {
        $now = date('Y-m-d');
        
        $sql = "SELECT * FROM ads 
                WHERE zone = :zone 
                  AND status = 'active'
                  AND start_date <= :now 
                  AND end_date >= :now";
        
        $params = [':zone' => $zone, ':now' => $now];
        
        // Append category filter only if it's not null
        if (!is_null($category_id)) {
            $sql .= " AND (category_id = :cat OR category_id IS NULL)";
            $params[':cat'] = $category_id;
        }
        
        // Append location filter only if it's not null
        if (!is_null($location)) {
            $sql .= " AND (location = :loc OR location IS NULL)";
            $params[':loc'] = $location;
        }
        
        $sql .= " ORDER BY priority DESC, RAND() LIMIT :limit";
        $params[':limit'] = $limit;
        
        error_log("🛠 Multiple Ads SQL: $sql");
        error_log("🛠 Multiple Ads Params: " . json_encode($params));
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ads = $stmt->fetchAll();
        
        $html = [];
        foreach ($ads as $ad) {
            logAdView($ad['id']);
            $html[] = generateAdHTML($ad, $zone);
        }
        
        return $html;
        
    } catch (PDOException $e) {
        error_log("🔥 Multiple ads renderer error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get ad statistics for admin dashboard
 * 
 * @param int|null $adId Optional specific ad ID
 * @param string|null $dateRange Optional date range (e.g., '7days', '30days')
 * @return array Statistics data
 */
function getAdStats($adId = null, $dateRange = null) {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    a.id,
                    a.title,
                    a.zone,
                    a.status,
                    COALESCE(SUM(s.views), 0) as total_views,
                    COALESCE(SUM(s.clicks), 0) as total_clicks,
                    CASE 
                        WHEN SUM(s.views) > 0 
                        THEN ROUND((SUM(s.clicks) / SUM(s.views)) * 100, 2)
                        ELSE 0 
                    END as ctr
                FROM ads a
                LEFT JOIN ad_stats s ON a.id = s.ad_id";
        
        $params = [];
        
        if (!is_null($adId)) {
            $sql .= " WHERE a.id = :ad_id";
            $params[':ad_id'] = $adId;
        }
        
        if (!is_null($dateRange)) {
            $dateCondition = " AND s.date >= :start_date";
            switch ($dateRange) {
                case '7days':
                    $startDate = date('Y-m-d', strtotime('-7 days'));
                    break;
                case '30days':
                    $startDate = date('Y-m-d', strtotime('-30 days'));
                    break;
                default:
                    $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            $params[':start_date'] = $startDate;
            $sql .= $dateCondition;
        }
        
        $sql .= " GROUP BY a.id ORDER BY total_views DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Ad stats error: " . $e->getMessage());
        return [];
    }
}

function renderWeightedAd($zone, $limit = 5, $category_id = null, $location = null, $options = []) {
    global $pdo;
    $now = date('Y-m-d');
    try {
        $sql = "SELECT * FROM ads WHERE zone = :zone AND status = 'active' AND start_date <= :start AND end_date >= :end";
        $params = [
            ':zone' => $zone,
            ':start' => $now,
            ':end' => $now
        ];
        if (!is_null($category_id)) {
            $sql .= " AND (category_id = :cat OR category_id IS NULL)";
            $params[':cat'] = $category_id;
        }
        if (!is_null($location)) {
            $sql .= " AND (location = :loc OR location IS NULL)";
            $params[':loc'] = $location;
        }
        $sql .= " ORDER BY priority DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute($params);
        $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$ads) {
            return getDefaultAdSpace($zone, $options);
        }
        $weighted = [];
        foreach ($ads as $ad) {
            $priority = max(1, (int)($ad['priority'] ?? 1));
            for ($i = 0; $i < $priority; $i++) {
                $weighted[] = $ad;
            }
        }
        $selectedAd = $weighted[array_rand($weighted)];
        logAdView($selectedAd['id']);
        return generateAdHTML($selectedAd, $zone, $options);
    } catch (PDOException $e) {
        error_log("🔥 renderWeightedAd SQL error: " . $e->getMessage());
        return "<!-- DB error: " . $e->getMessage() . " -->";
    }
}
?> 