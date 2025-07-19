<?php
require_once '../config/config.php';

// Set JSON content type
header('Content-Type: application/json');

// Get search parameters
$sector = $_GET['sector'] ?? '';
$location = $_GET['location'] ?? '';
$experience = $_GET['experience'] ?? '';

try {
    // Build query with filters
    $where_conditions = ["is_active = 1"];
    $params = [];

    if (!empty($sector)) {
        $where_conditions[] = "sector = ?";
        $params[] = $sector;
    }

    if (!empty($location)) {
        $where_conditions[] = "location = ?";
        $params[] = $location;
    }

    if (!empty($experience)) {
        $where_conditions[] = "experience_level = ?";
        $params[] = $experience;
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Fetch salary data
    $stmt = $pdo->prepare("
        SELECT sector, job_title, location, salary_low, salary_average, salary_high, 
               experience_level, currency, last_updated
        FROM salary_data 
        WHERE $where_clause
        ORDER BY salary_average DESC
    ");
    $stmt->execute($params);
    $salary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $salary_data,
        'count' => count($salary_data)
    ]);

} catch (PDOException $e) {
    error_log("Salary Data Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching salary data'
    ]);
}
?> 