<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/config.php';

// Set JSON header for AJAX response
header('Content-Type: application/json');

try {
    // Get filter parameters from POST data
    $sector_filter = $_POST['sector'] ?? '';
    $location_filter = $_POST['location'] ?? '';
    $job_type_filter = $_POST['job_type'] ?? '';
    $search_query = $_POST['search'] ?? '';
    $current_sort_value = $_POST['sort'] ?? 'newest';

    // Input validation and sanitization
    $valid_job_types = ['full-time', 'part-time', 'contract', 'temporary', 'internship'];
    if (!in_array($job_type_filter, $valid_job_types)) {
        $job_type_filter = '';
    }

    $valid_sort_options = ['newest', 'featured', 'sector', 'location'];
    if (!in_array($current_sort_value, $valid_sort_options)) {
        $current_sort_value = 'newest';
    }

    if (!empty($sector_filter) && !ctype_digit($sector_filter)) {
        $sector_filter = '';
    }

    if (!empty($location_filter)) {
        $location_filter = filter_var($location_filter, FILTER_SANITIZE_STRING);
    }

    // Build the main query with proper joins
    $query = "
        SELECT r.*, s.name as sector_name, b.business_name,
               bi.file_path as business_logo, u.profile_image, u.first_name, u.last_name,
               CASE WHEN sj.id IS NOT NULL THEN 1 ELSE 0 END as is_saved
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN saved_jobs sj ON r.id = sj.job_id AND sj.user_id = ?
        WHERE r.is_active = 1
    ";
    
    // Build the query with filters
    $where_conditions = ["r.is_active = 1"];
    $params = [];
    
    // Add user_id as the first parameter for the saved_jobs join
    $user_id = $_SESSION['user_id'] ?? 0;
    $params[] = $user_id;
    
    if (!empty($sector_filter)) {
        $where_conditions[] = "r.sector_id = ?";
        $params[] = $sector_filter;
    }
    
    if (!empty($location_filter)) {
        $where_conditions[] = "r.job_location LIKE ?";
        $params[] = "%$location_filter%";
    }
    
    if (!empty($job_type_filter)) {
        $where_conditions[] = "r.job_type = ?";
        $params[] = $job_type_filter;
    }
    
    if (!empty($search_query)) {
        $where_conditions[] = "(r.job_title LIKE ? OR r.job_description LIKE ? OR b.business_name LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Build ORDER BY clause
    $order_clause = "ORDER BY ";
    switch ($current_sort_value) {
        case 'featured':
            $order_clause .= "r.is_featured DESC, r.created_at DESC";
            break;
        case 'sector':
            $order_clause .= "s.name ASC, r.created_at DESC";
            break;
        case 'location':
            $order_clause .= "r.job_location ASC, r.created_at DESC";
            break;
        default: // newest
            $order_clause .= "r.created_at DESC";
    }
    
    $sql = $query . " AND $where_clause $order_clause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sectors for filter dropdown
    $sectors = $pdo->query("SELECT * FROM job_sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique locations for filter dropdown
    $locations = $pdo->query("SELECT DISTINCT job_location FROM recruitment WHERE job_location IS NOT NULL AND job_location != '' ORDER BY job_location")->fetchAll(PDO::FETCH_COLUMN);

    // Calculate result numbers for display
    $total_jobs = count($jobs);
    $start_result_number = $total_jobs > 0 ? 1 : 0;
    $end_result_number = $total_jobs;

    // Generate results HTML
    ob_start();
    if (!empty($jobs)) {
        foreach ($jobs as $job): ?>
            <div class="job-card" data-job-id="<?= $job['id'] ?>">
                <?php if ($job['is_featured']): ?>
                    <div class="featured-badge">
                        <i class="fas fa-star"></i>
                        Featured
                    </div>
                <?php endif; ?>
                
                <div class="job-header">
                    <h3 class="job-title">
                        <a href="/job_view.php?id=<?= $job['id'] ?>">
                            <?= htmlspecialchars($job['job_title']) ?>
                        </a>
                    </h3>
                    
                    <?php if ($job['business_name']): ?>
                        <div class="job-company">
                            <a href="/company-profile.php?slug=<?= urlencode(strtolower(str_replace(' ', '-', $job['business_name']))) ?>">
                                <?= htmlspecialchars($job['business_name']) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="job-meta">
                    <span class="job-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($job['job_location']) ?>
                    </span>
                    <span class="job-type">
                        <i class="fas fa-clock"></i>
                        <?= ucfirst(str_replace('-', ' ', $job['job_type'])) ?>
                    </span>
                    <?php if ($job['sector_name']): ?>
                        <span class="job-sector">
                            <i class="fas fa-briefcase"></i>
                            <?= htmlspecialchars($job['sector_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <p class="job-excerpt">
                    <?= htmlspecialchars(mb_strimwidth($job['job_description'], 0, 150, '...')) ?>
                </p>
                
                <div class="job-actions">
                    <a href="/job_view.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i>
                        View Job
                    </a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn <?= $job['is_saved'] ? 'btn-success' : 'btn-outline-secondary' ?> btn-sm save-job-btn" 
                                data-job-id="<?= $job['id'] ?>" 
                                title="<?= $job['is_saved'] ? 'Remove from saved jobs' : 'Save this job' ?>">
                            <i class="fas fa-bookmark"></i>
                            <?= $job['is_saved'] ? 'Saved' : 'Save' ?>
                        </button>
                        
                        <button class="btn btn-outline-info btn-sm create-alert-btn" 
                                data-job-title="<?= htmlspecialchars($job['job_title']) ?>"
                                data-job-sector="<?= htmlspecialchars($job['sector_name'] ?? '') ?>"
                                data-job-location="<?= htmlspecialchars($job['job_location']) ?>"
                                title="Create job alert for similar positions">
                            <i class="fas fa-bell"></i>
                            Alert
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="job-footer">
                    <span class="job-date">
                        Posted <?= date('M j, Y', strtotime($job['created_at'])) ?>
                    </span>
                </div>
            </div>
        <?php endforeach;
    } else {
        // No jobs found
        if (!empty($sector_filter) || !empty($location_filter) || !empty($job_type_filter) || !empty($search_query)) {
            echo '<div class="no-results">';
            echo '<div class="no-results-icon"><i class="fas fa-search"></i></div>';
            echo '<h3>No jobs found</h3>';
            echo '<p>Try adjusting your search criteria or <a href="/submit_job.php">post a new job</a>.</p>';
            echo '</div>';
        } else {
            echo '<div class="no-results">';
            echo '<div class="no-results-icon"><i class="fas fa-briefcase"></i></div>';
            echo '<h3>No jobs available</h3>';
            echo '<p>Be the first to <a href="/submit_job.php">post a job</a>!</p>';
            echo '</div>';
        }
    }
    $results_html = ob_get_clean();

    // Return JSON response
    echo json_encode([
        'success' => true,
        'results_html' => $results_html,
        'total_jobs' => $total_jobs,
        'start_result_number' => $start_result_number,
        'end_result_number' => $end_result_number,
        'filters' => [
            'sector' => $sector_filter,
            'location' => $location_filter,
            'job_type' => $job_type_filter,
            'search' => $search_query,
            'sort' => $current_sort_value
        ]
    ]);

} catch (PDOException $e) {
    error_log("AJAX Jobs Filter Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred. Please try again later.'
    ]);
} catch (Exception $e) {
    error_log("AJAX Jobs Filter Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred. Please try again later.'
    ]);
}
?> 