<?php
/**
 * Category Functions
 * Helper functions for category page functionality
 */

function getCategoryData($category_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.*, cm.short_description, cm.banner_image, cm.seo_title, cm.seo_description, cm.faq_content
        FROM business_categories c
        LEFT JOIN category_meta cm ON c.id = cm.category_id
        WHERE c.id = ?
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCategoryBusinesses($category_id, $location = null, $sort_by = 'premium_first') {
    global $pdo;
    
    $query = "
        SELECT b.*, c.name as category_name, u.subscription_tier,
               (SELECT COUNT(*) FROM testimonials t WHERE t.business_id = b.id AND t.is_approved = 1) as testimonials_count,
               (SELECT COUNT(*) FROM reviews r WHERE r.business_id = b.id AND r.is_approved = 1) as reviews_count,
               (SELECT AVG(rating) FROM reviews r WHERE r.business_id = b.id AND r.is_approved = 1) as avg_rating
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.status = 'active' AND b.category_id = ?
    ";
    
    $params = [$category_id];
    
    if ($location && $location !== 'All' && $location !== '') {
        $query .= " AND b.address LIKE ?";
        $params[] = "%$location%";
    }
    
    switch ($sort_by) {
        case 'rating':
            $query .= " ORDER BY avg_rating DESC NULLS LAST, b.created_at DESC";
            break;
        case 'most_recent':
            $query .= " ORDER BY b.created_at DESC";
            break;
        case 'most_viewed':
            $query .= " ORDER BY b.views_count DESC, b.created_at DESC";
            break;
        default:
            $query .= " ORDER BY 
                CASE u.subscription_tier 
                    WHEN 'premium_plus' THEN 1 
                    WHEN 'premium' THEN 2 
                    ELSE 3 
                END,
                b.created_at DESC";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($businesses as &$business) {
        $img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
        $img_stmt->execute([$business['id']]);
        $image_result = $img_stmt->fetch();
        $raw_path = $image_result['file_path'] ?? '';
        
        if ($raw_path && strpos($raw_path, '/public_html') !== false) {
            $web_path = substr($raw_path, strpos($raw_path, '/public_html') + strlen('/public_html'));
        } else {
            $web_path = $raw_path;
        }
        
        $business['logo'] = getBusinessLogoUrl($web_path, $business['business_name'] ?? '');
        if (empty($business['logo'])) {
            $business['logo'] = '/images/jshuk-logo.png';
        }
        
        $contact_info = json_decode($business['contact_info'] ?? '{}', true);
        $business['phone'] = $contact_info['phone'] ?? '';
        $business['email'] = $contact_info['email'] ?? '';
        
        $opening_hours = json_decode($business['opening_hours'] ?? '{}', true);
        $business['business_hours'] = '';
        if (!empty($opening_hours['monday']['open']) && !empty($opening_hours['monday']['close'])) {
            $business['business_hours'] = 'Mon-Fri ' . $opening_hours['monday']['open'] . '-' . $opening_hours['monday']['close'];
        }
        
        $business['tagline'] = !empty($business['description']) ? substr($business['description'], 0, 100) . '...' : '';
        $business['is_elite'] = $business['subscription_tier'] === 'premium_plus';
        $business['is_pinned'] = $business['is_featured'] && $business['subscription_tier'] !== 'basic';
        $business['rating'] = $business['avg_rating'] ? round($business['avg_rating'], 1) : null;
    }
    
    return $businesses;
}

function getCategoryTestimonials($category_id, $limit = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT t.*, b.business_name, u.subscription_tier, 
               COALESCE(t.reviewer_name, u.first_name, 'Anonymous') as reviewer_name
        FROM testimonials t
        JOIN businesses b ON t.business_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE b.category_id = ? 
        AND t.is_approved = 1 
        AND u.subscription_tier IN ('premium', 'premium_plus')
        ORDER BY t.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$category_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFeaturedStory($category_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM featured_stories 
        WHERE category_id = ? AND is_active = 1
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getBusinessLogoUrl($file_path, $business_name = '') {
    $default_logo = '/images/jshuk-logo.png';
    
    if (empty($file_path)) {
        return $default_logo;
    }
    
    if (strpos($file_path, 'http') === 0) {
        return $file_path;
    }
    
    if (strpos($file_path, '/') === 0) {
        return $file_path;
    }
    
    return '/uploads/' . $file_path;
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

function generateStarRating($rating) {
    if (!$rating) return '<span class="text-muted">No rating</span>';
    
    $html = '<div class="star-rating">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - $rating < 1) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }
    $html .= '<span class="rating-text ms-1">' . $rating . '</span>';
    $html .= '</div>';
    
    return $html;
}

function getPopularLocations() {
    return [
        'Manchester' => 'Manchester',
        'London' => 'London', 
        'Leeds' => 'Leeds',
        'Liverpool' => 'Liverpool',
        'Birmingham' => 'Birmingham',
        'Glasgow' => 'Glasgow',
        'Edinburgh' => 'Edinburgh',
        'Cardiff' => 'Cardiff',
        'Bristol' => 'Bristol',
        'Newcastle' => 'Newcastle'
    ];
}
?> 