<?php
/**
 * Volunteer Hub Functions for JShuk
 * Provides functions for managing volunteer opportunities, profiles, and gamification
 */

// Prevent direct access
if (!defined('APP_DEBUG')) {
    die('Direct access not allowed');
}

/**
 * Get volunteer opportunities with filters
 */
function getVolunteerOpportunities($filters = [], $limit = 20, $offset = 0) {
    global $pdo;
    
    if (!$pdo) return [];
    
    $where_conditions = ['vo.status = "active"'];
    $params = [];
    
    // Location filter
    if (!empty($filters['location'])) {
        $where_conditions[] = 'vo.location LIKE :location';
        $params[':location'] = '%' . $filters['location'] . '%';
    }
    
    // Frequency filter
    if (!empty($filters['frequency'])) {
        $where_conditions[] = 'vo.frequency = :frequency';
        $params[':frequency'] = $filters['frequency'];
    }
    
    // Urgent filter
    if (isset($filters['urgent'])) {
        $where_conditions[] = 'vo.urgent = :urgent';
        $params[':urgent'] = $filters['urgent'] ? 1 : 0;
    }
    
    // Date range filter
    if (!empty($filters['date_from'])) {
        $where_conditions[] = 'vo.date_needed >= :date_from';
        $params[':date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = 'vo.date_needed <= :date_to';
        $params[':date_to'] = $filters['date_to'];
    }
    
    // Search filter
    if (!empty($filters['search'])) {
        $where_conditions[] = '(vo.title LIKE :search OR vo.description LIKE :search OR vo.tags LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    // Tag filter
    if (!empty($filters['tags'])) {
        $tag_conditions = [];
        foreach ($filters['tags'] as $i => $tag) {
            $tag_conditions[] = 'vo.tags LIKE :tag' . $i;
            $params[':tag' . $i] = '%"' . $tag . '"%';
        }
        $where_conditions[] = '(' . implode(' OR ', $tag_conditions) . ')';
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "SELECT vo.*, u.name as posted_by_name, u.email as posted_by_email
            FROM volunteer_opportunities vo
            LEFT JOIN users u ON vo.posted_by = u.id
            WHERE {$where_clause}
            ORDER BY vo.urgent DESC, vo.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error fetching volunteer opportunities: " . $e->getMessage());
        }
        return [];
    }
}

/**
 * Get a single volunteer opportunity by ID or slug
 */
function getVolunteerOpportunity($identifier) {
    global $pdo;
    
    if (!$pdo) return null;
    
    $sql = "SELECT vo.*, u.name as posted_by_name, u.email as posted_by_email
            FROM volunteer_opportunities vo
            LEFT JOIN users u ON vo.posted_by = u.id
            WHERE vo.id = :id OR vo.slug = :slug";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $identifier);
        $stmt->bindParam(':slug', $identifier);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error fetching volunteer opportunity: " . $e->getMessage());
        }
        return null;
    }
}

/**
 * Create a new volunteer opportunity
 */
function createVolunteerOpportunity($data) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "INSERT INTO volunteer_opportunities (
                title, description, summary, location, tags, contact_method, 
                contact_info, frequency, preferred_times, date_needed, time_needed,
                chessed_hours, urgent, posted_by, slug
            ) VALUES (
                :title, :description, :summary, :location, :tags, :contact_method,
                :contact_info, :frequency, :preferred_times, :date_needed, :time_needed,
                :chessed_hours, :urgent, :posted_by, :slug
            )";
    
    try {
        $stmt = $pdo->prepare($sql);
        
        // Generate slug from title
        $slug = generateSlug($data['title']);
        $original_slug = $slug;
        $counter = 1;
        
        // Ensure unique slug
        while (slugExists($slug, 'volunteer_opportunities')) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':summary', $data['summary']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':tags', $data['tags']);
        $stmt->bindParam(':contact_method', $data['contact_method']);
        $stmt->bindParam(':contact_info', $data['contact_info']);
        $stmt->bindParam(':frequency', $data['frequency']);
        $stmt->bindParam(':preferred_times', $data['preferred_times']);
        $stmt->bindParam(':date_needed', $data['date_needed']);
        $stmt->bindParam(':time_needed', $data['time_needed']);
        $stmt->bindParam(':chessed_hours', $data['chessed_hours']);
        $stmt->bindParam(':urgent', $data['urgent']);
        $stmt->bindParam(':posted_by', $data['posted_by']);
        $stmt->bindParam(':slug', $slug);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error creating volunteer opportunity: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Check if a slug exists in a table
 */
function slugExists($slug, $table) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "SELECT COUNT(*) FROM {$table} WHERE slug = :slug";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Express interest in a volunteer opportunity
 */
function expressInterest($opportunity_id, $user_id, $message = '') {
    global $pdo;
    
    if (!$pdo) return false;
    
    // Check if already interested
    $check_sql = "SELECT id FROM volunteer_interests WHERE opportunity_id = :opportunity_id AND user_id = :user_id";
    
    try {
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':opportunity_id', $opportunity_id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->fetch()) {
            return false; // Already interested
        }
        
        // Add interest
        $sql = "INSERT INTO volunteer_interests (opportunity_id, user_id, message) VALUES (:opportunity_id, :user_id, :message)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':opportunity_id', $opportunity_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':message', $message);
        
        if ($stmt->execute()) {
            // Update interest count
            updateInterestCount($opportunity_id);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error expressing interest: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Update interest count for an opportunity
 */
function updateInterestCount($opportunity_id) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "UPDATE volunteer_opportunities 
            SET interests_count = (SELECT COUNT(*) FROM volunteer_interests WHERE opportunity_id = :opportunity_id)
            WHERE id = :opportunity_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':opportunity_id', $opportunity_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get volunteer profile for a user
 */
function getVolunteerProfile($user_id) {
    global $pdo;
    
    if (!$pdo) return null;
    
    $sql = "SELECT * FROM volunteer_profiles WHERE user_id = :user_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Create or update volunteer profile
 */
function saveVolunteerProfile($data) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $existing = getVolunteerProfile($data['user_id']);
    
    if ($existing) {
        // Update existing profile
        $sql = "UPDATE volunteer_profiles SET 
                display_name = :display_name,
                bio = :bio,
                availability = :availability,
                preferred_roles = :preferred_roles,
                contact_method = :contact_method,
                contact_info = :contact_info,
                experience_level = :experience_level,
                is_public = :is_public
                WHERE user_id = :user_id";
    } else {
        // Create new profile
        $sql = "INSERT INTO volunteer_profiles (
                    user_id, display_name, bio, availability, preferred_roles,
                    contact_method, contact_info, experience_level, is_public
                ) VALUES (
                    :user_id, :display_name, :bio, :availability, :preferred_roles,
                    :contact_method, :contact_info, :experience_level, :is_public
                )";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':display_name', $data['display_name']);
        $stmt->bindParam(':bio', $data['bio']);
        $stmt->bindParam(':availability', $data['availability']);
        $stmt->bindParam(':preferred_roles', $data['preferred_roles']);
        $stmt->bindParam(':contact_method', $data['contact_method']);
        $stmt->bindParam(':contact_info', $data['contact_info']);
        $stmt->bindParam(':experience_level', $data['experience_level']);
        $stmt->bindParam(':is_public', $data['is_public']);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error saving volunteer profile: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Get badges for a user
 */
function getUserBadges($user_id) {
    global $pdo;
    
    if (!$pdo) return [];
    
    $sql = "SELECT vb.*, vbe.earned_at, vbe.earned_for
            FROM volunteer_badges vb
            INNER JOIN volunteer_badge_earnings vbe ON vb.id = vbe.badge_id
            WHERE vbe.user_id = :user_id AND vb.is_active = 1
            ORDER BY vbe.earned_at DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Award a badge to a user
 */
function awardBadge($user_id, $badge_id, $earned_for = '') {
    global $pdo;
    
    if (!$pdo) return false;
    
    // Check if already earned
    $check_sql = "SELECT id FROM volunteer_badge_earnings WHERE user_id = :user_id AND badge_id = :badge_id";
    
    try {
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':badge_id', $badge_id);
        $check_stmt->execute();
        
        if ($check_stmt->fetch()) {
            return false; // Already earned
        }
        
        // Award badge
        $sql = "INSERT INTO volunteer_badge_earnings (user_id, badge_id, earned_for) VALUES (:user_id, :badge_id, :earned_for)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':badge_id', $badge_id);
        $stmt->bindParam(':earned_for', $earned_for);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error awarding badge: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Check and award badges based on user activity
 */
function checkAndAwardBadges($user_id) {
    global $pdo;
    
    if (!$pdo) return;
    
    // Get user's volunteer statistics
    $stats = getUserVolunteerStats($user_id);
    
    // Get all active badges
    $badges_sql = "SELECT * FROM volunteer_badges WHERE is_active = 1";
    $badges_stmt = $pdo->prepare($badges_sql);
    $badges_stmt->execute();
    $badges = $badges_stmt->fetchAll();
    
    foreach ($badges as $badge) {
        $criteria = json_decode($badge['criteria'], true);
        
        if (checkBadgeCriteria($stats, $criteria)) {
            awardBadge($user_id, $badge['id'], "Earned through volunteer activity");
        }
    }
}

/**
 * Get user's volunteer statistics
 */
function getUserVolunteerStats($user_id) {
    global $pdo;
    
    if (!$pdo) return [];
    
    $sql = "SELECT 
                COUNT(DISTINCT vi.opportunity_id) as opportunities_completed,
                COUNT(CASE WHEN vo.urgent = 1 THEN 1 END) as urgent_responses,
                COUNT(CASE WHEN vo.tags LIKE '%tutoring%' THEN 1 END) as tutoring_opportunities,
                COUNT(CASE WHEN vo.tags LIKE '%elderly%' THEN 1 END) as elderly_opportunities,
                SUM(vh.hours) as total_hours,
                COUNT(CASE WHEN DAYOFWEEK(vo.date_needed) IN (1, 7) THEN 1 END) as weekend_opportunities
            FROM volunteer_interests vi
            LEFT JOIN volunteer_opportunities vo ON vi.opportunity_id = vo.id
            LEFT JOIN volunteer_hours vh ON vi.opportunity_id = vh.opportunity_id AND vh.user_id = vi.user_id
            WHERE vi.user_id = :user_id AND vi.status = 'completed'";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Check if user meets badge criteria
 */
function checkBadgeCriteria($stats, $criteria) {
    switch ($criteria['type']) {
        case 'first_opportunity':
            return $stats['opportunities_completed'] >= 1;
            
        case 'opportunity_count':
            return $stats['opportunities_completed'] >= $criteria['count'];
            
        case 'urgent_responses':
            return $stats['urgent_responses'] >= $criteria['count'];
            
        case 'category_count':
            $category_field = $criteria['category'] . '_opportunities';
            return isset($stats[$category_field]) && $stats[$category_field] >= $criteria['count'];
            
        case 'weekend_opportunities':
            return $stats['weekend_opportunities'] >= $criteria['count'];
            
        default:
            return false;
    }
}

/**
 * Get popular volunteer tags
 */
function getPopularVolunteerTags($limit = 20) {
    global $pdo;
    
    if (!$pdo) return [];
    
    $sql = "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(tags, '$[*]')) as tag,
                COUNT(*) as count
            FROM volunteer_opportunities 
            WHERE status = 'active' AND tags IS NOT NULL
            GROUP BY tag
            ORDER BY count DESC
            LIMIT :limit";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get volunteer locations
 */
function getVolunteerLocations() {
    global $pdo;
    
    if (!$pdo) return [];
    
    $sql = "SELECT DISTINCT location, COUNT(*) as count
            FROM volunteer_opportunities 
            WHERE status = 'active'
            GROUP BY location
            ORDER BY count DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get volunteer opportunity types (from tags)
 */
function getVolunteerTypes() {
    $types = [
        'tutoring' => ['name' => 'Tutoring & Education', 'icon' => 'fa-graduation-cap'],
        'elderly' => ['name' => 'Elderly Care', 'icon' => 'fa-heart'],
        'food' => ['name' => 'Food & Meals', 'icon' => 'fa-utensils'],
        'transport' => ['name' => 'Transportation', 'icon' => 'fa-car'],
        'shabbat' => ['name' => 'Shabbat & Holidays', 'icon' => 'fa-star'],
        'community' => ['name' => 'Community Events', 'icon' => 'fa-users'],
        'urgent' => ['name' => 'Urgent Needs', 'icon' => 'fa-exclamation-triangle'],
        'family' => ['name' => 'Family Support', 'icon' => 'fa-home'],
        'health' => ['name' => 'Health & Wellness', 'icon' => 'fa-heartbeat'],
        'other' => ['name' => 'Other', 'icon' => 'fa-hands-helping']
    ];
    
    return $types;
}

/**
 * Format volunteer frequency for display
 */
function formatVolunteerFrequency($frequency) {
    $formats = [
        'one_time' => 'One Time',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'flexible' => 'Flexible'
    ];
    
    return $formats[$frequency] ?? $frequency;
}

/**
 * Get volunteer opportunity status badge
 */
function getVolunteerStatusBadge($status, $urgent = false) {
    $badges = [
        'active' => 'success',
        'filled' => 'secondary',
        'expired' => 'warning',
        'pending' => 'info'
    ];
    
    $badge_class = $badges[$status] ?? 'secondary';
    $text = ucfirst($status);
    
    if ($urgent && $status === 'active') {
        $badge_class = 'danger';
        $text = 'Urgent';
    }
    
    return "<span class=\"badge badge-{$badge_class}\">{$text}</span>";
}

/**
 * Get volunteer opportunity card HTML
 */
function renderVolunteerCard($opportunity) {
    $tags = json_decode($opportunity['tags'], true) ?? [];
    $tag_html = '';
    
    foreach (array_slice($tags, 0, 3) as $tag) {
        $tag_html .= "<span class=\"badge badge-light mr-1\">#{$tag}</span>";
    }
    
    $status_badge = getVolunteerStatusBadge($opportunity['status'], $opportunity['urgent']);
    $frequency = formatVolunteerFrequency($opportunity['frequency']);
    
    return "
    <div class=\"volunteer-card card mb-3\">
        <div class=\"card-body\">
            <div class=\"d-flex justify-content-between align-items-start mb-2\">
                <h5 class=\"card-title mb-0\">" . h($opportunity['title']) . "</h5>
                {$status_badge}
            </div>
            <p class=\"card-text text-muted\">" . h($opportunity['summary']) . "</p>
            <div class=\"volunteer-meta mb-3\">
                <small class=\"text-muted\">
                    <i class=\"fa fa-map-marker-alt\"></i> " . h($opportunity['location']) . " |
                    <i class=\"fa fa-clock\"></i> {$frequency} |
                    <i class=\"fa fa-users\"></i> {$opportunity['interests_count']} interested
                </small>
            </div>
            <div class=\"volunteer-tags mb-3\">
                {$tag_html}
            </div>
            <a href=\"/volunteer_detail.php?slug=" . h($opportunity['slug']) . "\" class=\"btn btn-primary btn-sm\">
                View Details
            </a>
        </div>
    </div>";
}

/**
 * Get volunteer opportunities for admin panel
 */
function getAdminVolunteerOpportunities($status_filter = 'all', $location_filter = '', $search_filter = '') {
    global $pdo;
    
    if (!$pdo) return [];
    
    $where_conditions = [];
    $params = [];
    
    // Status filter
    if ($status_filter !== 'all') {
        $where_conditions[] = 'vo.status = :status';
        $params[':status'] = $status_filter;
    }
    
    // Location filter
    if (!empty($location_filter)) {
        $where_conditions[] = 'vo.location LIKE :location';
        $params[':location'] = '%' . $location_filter . '%';
    }
    
    // Search filter
    if (!empty($search_filter)) {
        $where_conditions[] = '(vo.title LIKE :search OR vo.description LIKE :search OR vo.tags LIKE :search)';
        $params[':search'] = '%' . $search_filter . '%';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT vo.*, u.name as posted_by_name, u.email as posted_by_email
            FROM volunteer_opportunities vo
            LEFT JOIN users u ON vo.posted_by = u.id
            {$where_clause}
            ORDER BY vo.created_at DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error fetching admin volunteer opportunities: " . $e->getMessage());
        }
        return [];
    }
}

/**
 * Get volunteer statistics
 */
function getVolunteerStatistics() {
    global $pdo;
    
    if (!$pdo) return [];
    
    $sql = "SELECT 
                COUNT(*) as total_opportunities,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_approval,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_opportunities,
                COUNT(CASE WHEN status = 'filled' THEN 1 END) as filled_opportunities,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_opportunities,
                SUM(views_count) as total_views,
                SUM(interests_count) as total_interests
            FROM volunteer_opportunities";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error fetching volunteer statistics: " . $e->getMessage());
        }
        return [];
    }
}

/**
 * Approve a volunteer opportunity
 */
function approveVolunteerOpportunity($opportunity_id, $admin_id) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "UPDATE volunteer_opportunities 
            SET status = 'active', approved_by = :admin_id, approved_at = NOW() 
            WHERE id = :opportunity_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':opportunity_id', $opportunity_id);
        $stmt->bindParam(':admin_id', $admin_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error approving volunteer opportunity: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Reject a volunteer opportunity
 */
function rejectVolunteerOpportunity($opportunity_id, $admin_id) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "UPDATE volunteer_opportunities 
            SET status = 'expired', approved_by = :admin_id, approved_at = NOW() 
            WHERE id = :opportunity_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':opportunity_id', $opportunity_id);
        $stmt->bindParam(':admin_id', $admin_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error rejecting volunteer opportunity: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Mark opportunity as filled
 */
function markOpportunityFilled($opportunity_id) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "UPDATE volunteer_opportunities SET status = 'filled' WHERE id = :opportunity_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':opportunity_id', $opportunity_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error marking opportunity as filled: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Mark opportunity as expired
 */
function markOpportunityExpired($opportunity_id) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "UPDATE volunteer_opportunities SET status = 'expired' WHERE id = :opportunity_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':opportunity_id', $opportunity_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error marking opportunity as expired: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Delete a volunteer opportunity
 */
function deleteVolunteerOpportunity($opportunity_id) {
    global $pdo;
    
    if (!$pdo) return false;
    
    $sql = "DELETE FROM volunteer_opportunities WHERE id = :opportunity_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':opportunity_id', $opportunity_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Error deleting volunteer opportunity: " . $e->getMessage());
        }
        return false;
    }
} 