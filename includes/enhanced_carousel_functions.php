<?php
/**
 * Enhanced Carousel Functions
 * JShuk Advanced Carousel Management System
 * Phase 3: Backend Display Logic
 */

/**
 * Get user location from session or geolocation
 */
function getUserLocation($pdo) {
    // Check if location is already in session
    if (isset($_SESSION['user_location'])) {
        return $_SESSION['user_location'];
    }
    
    // Try to get location from IP or geolocation
    $location = detectUserLocation();
    
    // Store in session for future use
    $_SESSION['user_location'] = $location;
    
    return $location;
}

/**
 * Detect user location from IP address
 */
function detectUserLocation() {
    // Get IP address
    $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    
    // Simple IP-based location detection (you can enhance this with a proper geolocation service)
    if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
        return 'all'; // Default for localhost
    }
    
    // For now, return 'all' - you can integrate with a geolocation API here
    // Example: ipapi.co, ipstack.com, or similar services
    return 'all';
}

/**
 * Get carousel slides based on location, zone, and other criteria
 */
function getCarouselSlides($pdo, $zone = 'homepage', $limit = 10, $location = null) {
    try {
        // If no location provided, detect it
        if ($location === null) {
            $location = getUserLocation($pdo);
        }
        
        $today = date('Y-m-d');
        
        // Debug logging to identify the exact issue
        error_log("🔍 getCarouselSlides - Zone: [{$zone}], Location: [{$location}], Today: [{$today}]");
        
        $query = $pdo->prepare("
            SELECT * FROM carousel_slides
            WHERE active = 1
              AND (location = :loc OR location = 'all')
              AND TRIM(zone) = :zone
              AND (start_date IS NULL OR start_date <= :today)
              AND (end_date IS NULL OR end_date >= :today)
            ORDER BY priority DESC, sponsored DESC, created_at DESC
            LIMIT :limit
        ");
        
        $query->bindParam(':loc', $location, PDO::PARAM_STR);
        $query->bindParam(':zone', $zone, PDO::PARAM_STR);
        $query->bindParam(':today', $today, PDO::PARAM_STR);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        
        $slides = $query->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug logging to see what was returned
        error_log("🔍 getCarouselSlides - Found " . count($slides) . " slides");
        if (empty($slides)) {
            error_log("🔍 getCarouselSlides - No slides found, checking raw data...");
            // Let's see what's actually in the database
            $debug_stmt = $pdo->query("SELECT id, title, zone, active, location FROM carousel_slides ORDER BY id DESC LIMIT 5");
            $debug_slides = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("🔍 Raw DB data: " . json_encode($debug_slides));
        }
        
        // Log impressions for analytics
        foreach ($slides as $slide) {
            logCarouselEvent($pdo, $slide['id'], 'impression');
        }
        
        return $slides;
        
    } catch (PDOException $e) {
        error_log("Error fetching carousel slides: " . $e->getMessage());
        return [];
    }
}

/**
 * Log carousel events for analytics
 */
function logCarouselEvent($pdo, $slideId, $eventType) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO carousel_analytics (
                slide_id, event_type, user_location, user_agent, ip_address, session_id
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $slideId,
            $eventType,
            $_SESSION['user_location'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            session_id()
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error logging carousel event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get carousel performance statistics
 */
function getCarouselPerformance($pdo, $days = 30) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cs.id,
                cs.title,
                cs.location,
                cs.sponsored,
                COUNT(CASE WHEN ca.event_type = 'impression' THEN 1 END) as impressions,
                COUNT(CASE WHEN ca.event_type = 'click' THEN 1 END) as clicks,
                ROUND(
                    (COUNT(CASE WHEN ca.event_type = 'click' THEN 1 END) / 
                     NULLIF(COUNT(CASE WHEN ca.event_type = 'impression' THEN 1 END), 0)) * 100, 2
                ) as ctr_percentage
            FROM carousel_slides cs
            LEFT JOIN carousel_analytics ca ON cs.id = ca.slide_id
            WHERE ca.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY cs.id, cs.title, cs.location, cs.sponsored
            ORDER BY impressions DESC
        ");
        
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching carousel performance: " . $e->getMessage());
        return [];
    }
}

/**
 * Get location-based slide recommendations
 */
function getLocationBasedSlides($pdo, $location, $zone = 'homepage', $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM carousel_slides
            WHERE active = 1
              AND (location = :loc OR location = 'all')
              AND TRIM(zone) = :zone
              AND (start_date IS NULL OR start_date <= CURDATE())
              AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY 
                CASE WHEN location = :loc THEN 1 ELSE 2 END,
                priority DESC, 
                sponsored DESC, 
                created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':loc', $location, PDO::PARAM_STR);
        $stmt->bindParam(':zone', $zone, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching location-based slides: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if carousel has active slides for a zone
 */
function hasActiveSlides($pdo, $zone = 'homepage', $location = null) {
    try {
        if ($location === null) {
            $location = getUserLocation($pdo);
        }
        
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM carousel_slides
            WHERE active = 1
              AND (location = :loc OR location = 'all')
              AND TRIM(zone) = :zone
              AND (start_date IS NULL OR start_date <= :today)
              AND (end_date IS NULL OR end_date >= :today)
        ");
        
        $stmt->execute([':loc' => $location, ':zone' => $zone, ':today' => $today]);
        
        return $stmt->fetchColumn() > 0;
        
    } catch (PDOException $e) {
        error_log("Error checking active slides: " . $e->getMessage());
        return false;
    }
}

/**
 * Get sponsored slides
 */
function getSponsoredSlides($pdo, $zone = 'homepage', $limit = 3) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM carousel_slides
            WHERE active = 1
              AND sponsored = 1
              AND TRIM(zone) = :zone
              AND (start_date IS NULL OR start_date <= CURDATE())
              AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY priority DESC, created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':zone', $zone, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching sponsored slides: " . $e->getMessage());
        return [];
    }
}

/**
 * Get slides expiring soon (for admin notifications)
 */
function getExpiringSlides($pdo, $days = 7) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM carousel_slides
            WHERE active = 1
              AND end_date IS NOT NULL
              AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY end_date ASC
        ");
        
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error fetching expiring slides: " . $e->getMessage());
        return [];
    }
}

/**
 * Auto-expire slides that have passed their end date
 */
function autoExpireSlides($pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE carousel_slides 
            SET active = 0 
            WHERE end_date IS NOT NULL 
              AND end_date < CURDATE() 
              AND active = 1
        ");
        
        $stmt->execute();
        
        return $stmt->rowCount();
        
    } catch (PDOException $e) {
        error_log("Error auto-expiring slides: " . $e->getMessage());
        return 0;
    }
}

/**
 * Generate carousel HTML with enhanced features
 */
function generateCarouselHTML($slides, $carouselId = 'enhanced-carousel', $options = []) {
    if (empty($slides)) {
        return '<div class="alert alert-info">No carousel slides available.</div>';
    }
    
    $defaultOptions = [
        'autoplay' => true,
        'autoplayDelay' => 5000,
        'showNavigation' => true,
        'showPagination' => true,
        'effect' => 'fade',
        'height' => '600px'
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    $html = '<section class="carousel-section" data-scroll>';
    $html .= '<div class="carousel-wrapper">';
    $html .= '<div class="swiper ' . $carouselId . '">';
    $html .= '<div class="swiper-wrapper">';
    
    foreach ($slides as $slide) {
        // Ensure image_url has a leading slash
        $img = '/' . ltrim($slide['image_url'], '/');
        $html .= '<div class="swiper-slide carousel-slide" style="background-image: url(\'' . htmlspecialchars($img) . '\')">';
        $html .= '<div class="carousel-overlay">';
        $html .= '<div class="carousel-content">';
        $html .= '<h2 class="carousel-title">' . htmlspecialchars($slide['title']) . '</h2>';
        
        if (!empty($slide['subtitle'])) {
            $html .= '<p class="carousel-subtitle">' . htmlspecialchars($slide['subtitle']) . '</p>';
        }
        
        if (!empty($slide['cta_text']) && !empty($slide['cta_link'])) {
            $html .= '<a href="' . htmlspecialchars($slide['cta_link']) . '" class="carousel-cta" data-slide-id="' . $slide['id'] . '">';
            $html .= htmlspecialchars($slide['cta_text']);
            $html .= '</a>';
        }
        
        if ($slide['sponsored']) {
            $html .= '<span class="badge sponsored-badge">Sponsored</span>';
        }
        
        $html .= '</div>'; // carousel-content
        $html .= '</div>'; // carousel-overlay
        $html .= '</div>'; // swiper-slide
    }
    
    $html .= '</div>'; // swiper-wrapper
    
    if ($options['showNavigation']) {
        $html .= '<div class="swiper-button-prev carousel-nav-prev"></div>';
        $html .= '<div class="swiper-button-next carousel-nav-next"></div>';
    }
    
    if ($options['showPagination']) {
        $html .= '<div class="swiper-pagination carousel-pagination"></div>';
    }
    
    $html .= '</div>'; // swiper
    $html .= '</div>'; // carousel-wrapper
    $html .= '</section>';
    
    // Add JavaScript for analytics tracking
    $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Track CTA clicks
            document.querySelectorAll(".carousel-cta").forEach(function(cta) {
                cta.addEventListener("click", function() {
                    const slideId = this.getAttribute("data-slide-id");
                    if (slideId) {
                        fetch("/api/carousel-analytics.php", {
                            method: "POST",
                            headers: {"Content-Type": "application/json"},
                            body: JSON.stringify({
                                slide_id: slideId,
                                event_type: "click"
                            })
                        });
                    }
                });
            });
        });
    </script>';
    
    return $html;
}

/**
 * Get carousel statistics for admin dashboard
 */
function getCarouselStats($pdo) {
    try {
        $stats = [];
        
        // Total slides
        $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides");
        $stats['total_slides'] = $stmt->fetchColumn();
        
        // Active slides
        $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides WHERE active = 1");
        $stats['active_slides'] = $stmt->fetchColumn();
        
        // Sponsored slides
        $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides WHERE sponsored = 1 AND active = 1");
        $stats['sponsored_slides'] = $stmt->fetchColumn();
        
        // Total impressions (last 30 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM carousel_analytics 
            WHERE event_type = 'impression' 
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['total_impressions'] = $stmt->fetchColumn();
        
        // Total clicks (last 30 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM carousel_analytics 
            WHERE event_type = 'click' 
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['total_clicks'] = $stmt->fetchColumn();
        
        // Overall CTR
        $stats['overall_ctr'] = $stats['total_impressions'] > 0 
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2) 
            : 0;
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Error fetching carousel stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate slide data
 */
function validateSlideData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'Title is required';
    }
    
    if (empty($data['image_url'])) {
        $errors[] = 'Image is required';
    }
    
    if (!empty($data['start_date']) && !empty($data['end_date'])) {
        if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
            $errors[] = 'Start date cannot be after end date';
        }
    }
    
    if (isset($data['priority']) && ($data['priority'] < 0 || $data['priority'] > 100)) {
        $errors[] = 'Priority must be between 0 and 100';
    }
    
    return $errors;
}

/**
 * Clean up old analytics data (for performance)
 */
function cleanupOldAnalytics($pdo, $daysToKeep = 90) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM carousel_analytics 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        
        $stmt->bindParam(':days', $daysToKeep, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount();
        
    } catch (PDOException $e) {
        error_log("Error cleaning up old analytics: " . $e->getMessage());
        return 0;
    }
}
?> 