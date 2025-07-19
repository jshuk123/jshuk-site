<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['id'] ?? null;

if (!$application_id || !is_numeric($application_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

try {
    // Fetch application details with security check
    $stmt = $pdo->prepare("
        SELECT ja.*, r.job_title, r.job_location, r.job_type, r.job_description,
               u.first_name, u.last_name, u.email as applicant_email, u.phone as applicant_phone,
               s.name as sector_name
        FROM job_applications ja
        JOIN recruitment r ON ja.job_id = r.id
        JOIN users u ON ja.applicant_id = u.id
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        WHERE ja.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit;
    }

    // Get application status history
    $stmt = $pdo->prepare("
        SELECT ash.*, u.first_name, u.last_name
        FROM application_status_history ash
        JOIN users u ON ash.changed_by = u.id
        WHERE ash.application_id = ?
        ORDER BY ash.changed_at DESC
    ");
    $stmt->execute([$application_id]);
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML for the modal
    $html = '
    <div class="application-details">
        <div class="row">
            <div class="col-md-8">
                <!-- Candidate Information -->
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-user me-2"></i>Candidate Information
                    </h6>
                    <div class="candidate-details">
                        <div class="candidate-header">
                            <img src="https://ui-avatars.com/api/?name=' . urlencode($application['first_name'] . ' ' . $application['last_name']) . '&background=0d6efd&color=fff&size=80&rounded=true" 
                                 alt="Candidate Avatar" class="candidate-avatar">
                            <div class="candidate-info">
                                <h5>' . htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) . '</h5>
                                <p class="candidate-email">
                                    <i class="fas fa-envelope me-2"></i>
                                    ' . htmlspecialchars($application['applicant_email']) . '
                                </p>';
    
    if ($application['applicant_phone']) {
        $html .= '
                                <p class="candidate-phone">
                                    <i class="fas fa-phone me-2"></i>
                                    ' . htmlspecialchars($application['applicant_phone']) . '
                                </p>';
    }
    
    $html .= '
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Information -->
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-briefcase me-2"></i>Job Position
                    </h6>
                    <div class="job-details">
                        <h5>' . htmlspecialchars($application['job_title']) . '</h5>
                        <div class="job-meta">
                            <span class="job-location">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                ' . htmlspecialchars($application['job_location'] ?? 'Location TBD') . '
                            </span>
                            <span class="job-type">
                                <i class="fas fa-clock me-1"></i>
                                ' . ucfirst(str_replace('-', ' ', $application['job_type'] ?? 'Full Time')) . '
                            </span>';
    
    if ($application['sector_name']) {
        $html .= '
                            <span class="job-sector">
                                <i class="fas fa-industry me-1"></i>
                                ' . htmlspecialchars($application['sector_name']) . '
                            </span>';
    }
    
    $html .= '
                        </div>
                    </div>
                </div>

                <!-- Cover Letter -->
                ' . ($application['cover_letter'] ? '
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-file-alt me-2"></i>Cover Letter
                    </h6>
                    <div class="cover-letter">
                        ' . nl2br(htmlspecialchars($application['cover_letter'])) . '
                    </div>
                </div>' : '') . '

                <!-- Application Notes -->
                ' . ($application['notes'] ? '
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-sticky-note me-2"></i>Notes
                    </h6>
                    <div class="application-notes">
                        ' . nl2br(htmlspecialchars($application['notes'])) . '
                    </div>
                </div>' : '') . '
            </div>

            <div class="col-md-4">
                <!-- Application Status -->
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>Application Status
                    </h6>
                    <div class="status-info">
                        <span class="status-badge status-' . $application['status'] . '">
                            ' . ucfirst($application['status']) . '
                        </span>
                        <p class="applied-date">
                            <i class="fas fa-calendar me-1"></i>
                            Applied on ' . date('M j, Y', strtotime($application['applied_at'])) . '
                        </p>
                        <p class="applied-time">
                            <i class="fas fa-clock me-1"></i>
                            ' . date('g:i A', strtotime($application['applied_at'])) . '
                        </p>
                    </div>
                </div>

                <!-- Resume Download -->
                ' . ($application['resume_path'] ? '
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-file-pdf me-2"></i>Resume
                    </h6>
                    <div class="resume-download">
                        <a href="' . htmlspecialchars($application['resume_path']) . '" 
                           class="btn btn-primary btn-sm w-100" target="_blank">
                            <i class="fas fa-download me-2"></i>Download Resume
                        </a>
                    </div>
                </div>' : '
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-file-pdf me-2"></i>Resume
                    </h6>
                    <div class="resume-download">
                        <p class="text-muted">No resume uploaded</p>
                    </div>
                </div>') . '

                <!-- Status History -->
                ' . (!empty($status_history) ? '
                <div class="section">
                    <h6 class="section-title">
                        <i class="fas fa-history me-2"></i>Status History
                    </h6>
                    <div class="status-history">
                        ' . implode('', array_map(function($history) {
                            return '
                            <div class="history-item">
                                <div class="history-status">
                                    <span class="status-badge status-' . $history['status'] . '">
                                        ' . ucfirst($history['status']) . '
                                    </span>
                                </div>
                                <div class="history-details">
                                    <p class="history-date">
                                        ' . date('M j, Y g:i A', strtotime($history['changed_at'])) . '
                                    </p>
                                    <p class="history-user">
                                        by ' . htmlspecialchars($history['first_name'] . ' ' . $history['last_name']) . '
                                    </p>
                                    ' . ($history['notes'] ? '<p class="history-notes">' . htmlspecialchars($history['notes']) . '</p>' : '') . '
                                </div>
                            </div>';
                        }, $status_history)) . '
                    </div>
                </div>' : '') . '
            </div>
        </div>
    </div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    error_log("Error fetching application: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?> 