<?php
/**
 * Ask the Rabbi Page
 * Community Q&A for halachic questions
 */

require_once 'config/config.php';
require_once 'includes/community_corner_functions.php';

$pageTitle = "Ask the Rabbi | JShuk Community";
$page_css = "pages/ask_the_rabbi.css";
$metaDescription = "Get answers to your halachic questions from our community rabbis. Submit questions and browse previous Q&A on Jewish law and practice.";
$metaKeywords = "ask the rabbi, halachic questions, jewish law, rabbi q&a, community rabbi";

include 'includes/header_main.php';

// Get ask the rabbi items
$qaItems = getCommunityCornerItemsByType('ask_rabbi', 20);
?>

<div class="ask-rabbi-page">
    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">Ask the Rabbi</h1>
                <p class="hero-subtitle">Get answers to your halachic questions from our community rabbis</p>
                <div class="hero-emoji">📜</div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="action-card">
                    <div class="card-body text-center">
                        <div class="action-icon">❓</div>
                        <h5>Have a Question?</h5>
                        <p>Submit your halachic question to our community rabbis for guidance.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#askQuestionModal">
                            Ask a Question
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="action-card">
                    <div class="card-body text-center">
                        <div class="action-icon">📚</div>
                        <h5>Browse Q&A</h5>
                        <p>Search through previous questions and answers on Jewish law and practice.</p>
                        <div class="search-box">
                            <input type="text" class="form-control" placeholder="Search questions..." id="qaSearch">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Q&A Section -->
        <div class="qa-section">
            <div class="section-header">
                <h2>Recent Questions & Answers</h2>
                <p>Latest halachic guidance from our community</p>
            </div>

            <?php if (!empty($qaItems)): ?>
                <div class="qa-grid">
                    <?php foreach ($qaItems as $item): ?>
                        <div class="qa-card">
                            <div class="qa-header">
                                <div class="qa-emoji"><?= htmlspecialchars($item['emoji']) ?></div>
                                <div class="qa-meta">
                                    <h3 class="qa-title"><?= htmlspecialchars($item['title']) ?></h3>
                                    <div class="qa-date">
                                        <small class="text-muted">
                                            <?= date('M j, Y', strtotime($item['date_added'])) ?>
                                        </small>
                                        <?php if ($item['views_count'] > 0): ?>
                                            <small class="text-muted ms-2">
                                                <?= $item['views_count'] ?> views
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="qa-content">
                                <p class="qa-question"><?= htmlspecialchars($item['body_text']) ?></p>
                            </div>
                            <?php if ($item['link_url']): ?>
                                <div class="qa-actions">
                                    <a href="<?= htmlspecialchars($item['link_url']) ?>" class="btn btn-outline-primary btn-sm">
                                        <?= htmlspecialchars($item['link_text']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="empty-state">
                        <div class="empty-icon">📜</div>
                        <h3>No Questions Yet</h3>
                        <p>Be the first to ask a halachic question to our community rabbis.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#askQuestionModal">
                            Ask the First Question
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Guidelines -->
        <div class="guidelines-section mt-5">
            <div class="card">
                <div class="card-header">
                    <h4>Guidelines for Questions</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>What to Include</h5>
                            <ul>
                                <li>Clear, specific question about Jewish law or practice</li>
                                <li>Relevant context and background information</li>
                                <li>Your name (optional, for personal responses)</li>
                                <li>Contact information if you need a private response</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Response Time</h5>
                            <ul>
                                <li>General questions: 1-3 business days</li>
                                <li>Urgent matters: Please contact your local rabbi directly</li>
                                <li>Complex halachic issues may require additional research</li>
                                <li>Personal matters will be handled privately</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ask Question Modal -->
<div class="modal fade" id="askQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ask the Rabbi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="askQuestionForm">
                    <div class="mb-3">
                        <label for="questionTitle" class="form-label">Question Title</label>
                        <input type="text" class="form-control" id="questionTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="questionText" class="form-label">Your Question</label>
                        <textarea class="form-control" id="questionText" rows="5" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="questionerName" class="form-label">Your Name (Optional)</label>
                                <input type="text" class="form-control" id="questionerName">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="questionerEmail" class="form-label">Email (Optional)</label>
                                <input type="email" class="form-control" id="questionerEmail">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="privateResponse">
                            <label class="form-check-label" for="privateResponse">
                                Keep this question private (not published publicly)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitQuestion()">Submit Question</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitQuestion() {
    const formData = {
        title: document.getElementById('questionTitle').value,
        question: document.getElementById('questionText').value,
        name: document.getElementById('questionerName').value,
        email: document.getElementById('questionerEmail').value,
        private: document.getElementById('privateResponse').checked
    };
    
    // Here you would typically send to your backend
    alert('Thank you for your question! A rabbi will respond within 1-3 business days.');
    bootstrap.Modal.getInstance(document.getElementById('askQuestionModal')).hide();
}

// Search functionality
document.getElementById('qaSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const qaCards = document.querySelectorAll('.qa-card');
    
    qaCards.forEach(card => {
        const title = card.querySelector('.qa-title').textContent.toLowerCase();
        const content = card.querySelector('.qa-question').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || content.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<?php include 'includes/footer_main.php'; ?> 