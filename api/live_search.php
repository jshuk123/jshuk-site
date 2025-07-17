<?php
/**
 * Live Search API Endpoint
 * Returns business suggestions for autocomplete dropdown
 */

require_once '../config/config.php';
require_once '../includes/helpers.php';

// Set JSON content type
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get search query
$query = trim($_GET['q'] ?? '');
$limit = min(10, max(1, (int)($_GET['limit'] ?? 8))); // Default 8, max 10

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    // Search businesses with subscription tier priority
    $sql = "
        SELECT 
            b.id,
            b.business_name,
            b.description,
            c.name as category_name,
            u.subscription_tier,
            COALESCE(bi.file_path, '/assets/images/placeholder-business.png') as main_image
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        WHERE b.status = 'active' 
        AND (
            b.business_name LIKE :search 
            OR b.description LIKE :search 
            OR c.name LIKE :search
        )
        ORDER BY 
            CASE u.subscription_tier 
                WHEN 'premium_plus' THEN 1 
                WHEN 'premium' THEN 2 
                ELSE 3 
            END,
            b.business_name ASC 
        LIMIT :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$query%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results for frontend
    $formatted_results = [];
    foreach ($results as $result) {
        $formatted_results[] = [
            'id' => $result['id'],
            'name' => $result['business_name'],
            'category' => $result['category_name'],
            'description' => substr($result['description'], 0, 100) . (strlen($result['description']) > 100 ? '...' : ''),
            'image' => $result['main_image'] ?: '/images/jshuk-logo.png',
            'tier' => $result['subscription_tier'],
            'url' => "/business.php?id=" . $result['id']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'results' => $formatted_results,
        'query' => $query
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'results' => []
    ]);
}
?> 