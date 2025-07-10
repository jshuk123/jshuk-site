<?php
/**
 * Subscription Tier Functions for JShuk
 * 
 * This file contains helper functions for managing subscription tiers,
 * rendering badges, and controlling feature access based on tier levels.
 */

/**
 * Get subscription tier limits
 */
function getSubscriptionTierLimits($tier) {
    $limits = [
        'basic' => [
            'images' => 1,
            'testimonials' => 0,
            'description_length' => 200,
            'can_feature' => false,
            'can_promote' => false,
            'homepage_visibility' => false,
            'priority_search' => false,
            'whatsapp_features' => false,
            'unlimited_testimonials' => false,
            'pinned_results' => false,
            'beta_features' => false,
            'animated_effects' => false,
            'elite_ribbon' => false
        ],
        'premium' => [
            'images' => 5,
            'testimonials' => 5,
            'description_length' => 500,
            'can_feature' => true,
            'can_promote' => true,
            'homepage_visibility' => true,
            'priority_search' => true,
            'whatsapp_features' => true,
            'unlimited_testimonials' => false,
            'pinned_results' => false,
            'beta_features' => false,
            'animated_effects' => false,
            'elite_ribbon' => false
        ],
        'premium_plus' => [
            'images' => null, // unlimited
            'testimonials' => null, // unlimited
            'description_length' => null, // unlimited
            'can_feature' => true,
            'can_promote' => true,
            'homepage_visibility' => true,
            'priority_search' => true,
            'whatsapp_features' => true,
            'unlimited_testimonials' => true,
            'pinned_results' => true,
            'beta_features' => true,
            'animated_effects' => true,
            'elite_ribbon' => true
        ]
    ];
    
    return $limits[$tier] ?? $limits['basic'];
}

/**
 * Get user's subscription tier
 */
function getUserSubscriptionTier($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT subscription_tier FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() ?: 'basic';
    } catch (PDOException $e) {
        return 'basic';
    }
}

/**
 * Check if a business can perform a specific action based on their tier
 */
function canBusinessPerformAction($business_tier, $action) {
    $limits = getSubscriptionTierLimits($business_tier);
    
    switch ($action) {
        case 'add_testimonial':
            if ($limits['unlimited_testimonials']) return true;
            return $limits['testimonials'] > 0;
            
        case 'add_image':
            return $limits['images'] > 0;
            
        case 'feature_on_homepage':
            return $limits['homepage_visibility'];
            
        case 'priority_search':
            return $limits['priority_search'];
            
        case 'pinned_results':
            return $limits['pinned_results'];
            
        case 'beta_features':
            return $limits['beta_features'];
            
        case 'whatsapp_features':
            return $limits['whatsapp_features'];
            
        case 'animated_effects':
            return $limits['animated_effects'];
            
        case 'elite_ribbon':
            return $limits['elite_ribbon'];
            
        default:
            return false;
    }
}

/**
 * Get current image count for a business
 */
function getBusinessImageCount($business_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM business_images WHERE business_id = ?");
        $stmt->execute([$business_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get current testimonial count for a business
 */
function getBusinessTestimonialCount($business_id, $pdo) {
    try {
        // Try testimonials table first
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials WHERE business_id = ? AND status = 'approved'");
        $stmt->execute([$business_id]);
        $count = $stmt->fetchColumn();
        if ($count !== false) return $count;
        
        // Fallback to reviews table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE business_id = ? AND is_approved = 1");
        $stmt->execute([$business_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Check if business can add more images
 */
function canAddMoreImages($business_id, $business_tier, $pdo) {
    $limits = getSubscriptionTierLimits($business_tier);
    if ($limits['images'] === null) return true; // unlimited
    
    $current_count = getBusinessImageCount($business_id, $pdo);
    return $current_count < $limits['images'];
}

/**
 * Check if business can add more testimonials
 */
function canAddMoreTestimonials($business_id, $business_tier, $pdo) {
    $limits = getSubscriptionTierLimits($business_tier);
    if ($limits['unlimited_testimonials']) return true;
    
    $current_count = getBusinessTestimonialCount($business_id, $pdo);
    return $current_count < $limits['testimonials'];
}

/**
 * Render subscription tier badge
 */
function renderSubscriptionBadge($tier, $show_text = true) {
    $badges = [
        'basic' => [
            'class' => 'badge-basic',
            'text' => 'Basic',
            'icon' => 'fa-circle'
        ],
        'premium' => [
            'class' => 'badge-premium',
            'text' => 'Premium',
            'icon' => 'fa-star'
        ],
        'premium_plus' => [
            'class' => 'badge-premium-plus',
            'text' => 'Premium+',
            'icon' => 'fa-crown'
        ]
    ];
    
    $badge = $badges[$tier] ?? $badges['basic'];
    
    $html = '<span class="subscription-badge ' . $badge['class'] . '" title="' . ucfirst($tier) . ' Tier">';
    $html .= '<i class="fas ' . $badge['icon'] . '"></i>';
    if ($show_text) {
        $html .= '<span class="badge-text">' . $badge['text'] . '</span>';
    }
    $html .= '</span>';
    
    return $html;
}

/**
 * Render featured ribbon for premium tiers
 */
function renderFeaturedRibbon($tier, $is_featured = false) {
    if ($tier === 'basic' || !$is_featured) {
        return '';
    }
    
    $ribbon_class = $tier === 'premium_plus' ? 'ribbon-premium-plus' : 'ribbon-premium';
    $ribbon_text = $tier === 'premium_plus' ? 'Elite' : 'Featured';
    
    return '<div class="featured-ribbon ' . $ribbon_class . '">' . $ribbon_text . '</div>';
}

/**
 * Render elite ribbon for Premium+ users
 */
function renderEliteRibbon($tier) {
    if ($tier !== 'premium_plus') {
        return '';
    }
    // Both pill badge and ribbon
    return '<span class="elite-badge glow"><i class="fa-solid fa-crown"></i> ELITE</span>' .
           '<div class="elite-ribbon"><i class="fa-solid fa-crown"></i> ELITE</div>';
}

/**
 * Get subscription tier display name
 */
function getTierDisplayName($tier) {
    $names = [
        'basic' => 'JShuk Basic',
        'premium' => 'JShuk Premium',
        'premium_plus' => 'JShuk Premium+'
    ];
    
    return $names[$tier] ?? 'JShuk Basic';
}

/**
 * Get subscription tier pricing
 */
function getTierPricing($tier) {
    $pricing = [
        'basic' => [
            'monthly' => 0,
            'annual' => 0,
            'currency' => '£'
        ],
        'premium' => [
            'monthly' => 15,
            'annual' => 150,
            'currency' => '£'
        ],
        'premium_plus' => [
            'monthly' => 30,
            'annual' => 300,
            'currency' => '£'
        ]
    ];
    
    return $pricing[$tier] ?? $pricing['basic'];
}

/**
 * Get businesses for homepage display (premium and premium_plus only)
 */
function getHomepageBusinesses($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT b.*, c.name AS category_name, u.subscription_tier
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
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get businesses with pinned results for Premium+ users
 */
function getPinnedBusinesses($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT b.*, c.name AS category_name, u.subscription_tier
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.status = 'active' 
        AND u.subscription_tier = 'premium_plus'
        ORDER BY b.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get tier upgrade benefits for display
 */
function getTierUpgradeBenefits($current_tier, $target_tier) {
    $benefits = [
        'basic_to_premium' => [
            'Up to 5 gallery images (vs 1)',
            'Up to 5 testimonials (vs 0)',
            'Homepage carousel visibility',
            'Gold Premium badge',
            'Priority in search results',
            'WhatsApp-ready sign-up graphic',
            'Can offer promotions'
        ],
        'basic_to_premium_plus' => [
            'Unlimited gallery images (vs 1)',
            'Unlimited testimonials (vs 0)',
            'Pinned in search results',
            'Blue Premium+ badge with crown',
            'Animated glow/border on listing',
            'Top Pick/Elite ribbon',
            'Access to beta features',
            'Included in WhatsApp highlight messages'
        ],
        'premium_to_premium_plus' => [
            'Unlimited gallery images (vs 5)',
            'Unlimited testimonials (vs 5)',
            'Pinned in search results',
            'Blue Premium+ badge with crown (vs gold)',
            'Animated glow/border on listing',
            'Top Pick/Elite ribbon',
            'Access to beta features',
            'Included in WhatsApp highlight messages'
        ]
    ];
    
    $key = $current_tier . '_to_' . $target_tier;
    return $benefits[$key] ?? [];
}

/**
 * Check if user has access to beta features
 */
function hasBetaAccess($user_id, $pdo) {
    $tier = getUserSubscriptionTier($user_id, $pdo);
    return canBusinessPerformAction($tier, 'beta_features');
}

/**
 * Get CSS classes for premium effects
 */
function getPremiumCssClasses($tier) {
    $classes = [];
    
    if ($tier === 'premium') {
        $classes[] = 'premium-tier';
    } elseif ($tier === 'premium_plus') {
        $classes[] = 'premium-plus-tier';
        $classes[] = 'animated-glow';
    }
    
    return implode(' ', $classes);
}
?> 