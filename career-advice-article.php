<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

// Get article slug from URL
$article_slug = $_GET['slug'] ?? '';

if (empty($article_slug)) {
    header('Location: /career-advice.php');
    exit;
}

try {
    // Get article details
    $stmt = $pdo->prepare("
        SELECT caa.*, u.first_name, u.last_name
        FROM career_advice_articles caa
        LEFT JOIN users u ON caa.author_id = u.id
        WHERE caa.slug = ? AND caa.status = 'published'
    ");
    $stmt->execute([$article_slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        header('Location: /career-advice.php');
        exit;
    }

    // Increment view count
    $stmt = $pdo->prepare("UPDATE career_advice_articles SET views_count = views_count + 1 WHERE id = ?");
    $stmt->execute([$article['id']]);

    // Get article categories
    $stmt = $pdo->prepare("
        SELECT ac.name, ac.slug
        FROM article_categories ac
        JOIN article_category_relations acr ON ac.id = acr.category_id
        WHERE acr.article_id = ? AND ac.is_active = 1
        ORDER BY ac.sort_order
    ");
    $stmt->execute([$article['id']]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get article tags
    $stmt = $pdo->prepare("
        SELECT at.name, at.slug
        FROM article_tags at
        JOIN article_tag_relations atr ON at.id = atr.tag_id
        WHERE atr.article_id = ?
        ORDER BY at.name
    ");
    $stmt->execute([$article['id']]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get related articles
    $stmt = $pdo->prepare("
        SELECT caa.title, caa.slug, caa.excerpt, caa.featured_image, caa.views_count, caa.published_at
        FROM career_advice_articles caa
        JOIN article_category_relations acr1 ON caa.id = acr1.article_id
        JOIN article_category_relations acr2 ON acr1.category_id = acr2.category_id
        WHERE acr2.article_id = ? AND caa.id != ? AND caa.status = 'published'
        GROUP BY caa.id
        ORDER BY caa.views_count DESC, caa.published_at DESC
        LIMIT 3
    ");
    $stmt->execute([$article['id'], $article['id']]);
    $related_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Career Advice Article Error: " . $e->getMessage());
    header('Location: /career-advice.php');
    exit;
}

$pageTitle = htmlspecialchars($article['meta_title'] ?: $article['title']);
$page_css = "career_advice_article.css";
$metaDescription = htmlspecialchars($article['meta_description'] ?: $article['excerpt']);
include 'includes/header_main.php';
?>

<div class="article-container">
    <!-- Article Header -->
    <div class="article-header">
        <div class="container">
            <div class="article-meta">
                <?php if (!empty($categories)): ?>
                    <div class="article-categories">
                        <?php foreach ($categories as $category): ?>
                            <a href="/career-advice.php?category=<?= htmlspecialchars($category['slug']) ?>" 
                               class="category-badge">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                
                <div class="article-info">
                    <div class="info-left">
                        <?php if ($article['first_name']): ?>
                            <span class="author">
                                <i class="fas fa-user"></i>
                                By <?= htmlspecialchars($article['first_name'] . ' ' . $article['last_name']) ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="date">
                            <i class="fas fa-calendar"></i>
                            <?= date('F j, Y', strtotime($article['published_at'])) ?>
                        </span>
                        
                        <span class="read-time">
                            <i class="fas fa-clock"></i>
                            <?= ceil(str_word_count(strip_tags($article['content'])) / 200) ?> min read
                        </span>
                    </div>
                    
                    <div class="info-right">
                        <span class="views">
                            <i class="fas fa-eye"></i>
                            <?= number_format($article['views_count']) ?> views
                        </span>
                        
                        <?php if ($article['is_featured']): ?>
                            <span class="featured-badge">
                                <i class="fas fa-star"></i> Featured
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Image -->
    <?php if ($article['featured_image']): ?>
        <div class="article-featured-image">
            <div class="container">
                <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                     alt="<?= htmlspecialchars($article['title']) ?>">
            </div>
        </div>
    <?php endif; ?>

    <!-- Article Content -->
    <div class="article-content">
        <div class="container">
            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="content-wrapper">
                        <?php if ($article['excerpt']): ?>
                            <div class="article-excerpt">
                                <p class="lead"><?= htmlspecialchars($article['excerpt']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-body">
                            <?= $article['content'] ?>
                        </div>
                        
                        <!-- Tags -->
                        <?php if (!empty($tags)): ?>
                            <div class="article-tags">
                                <h5>Tags:</h5>
                                <div class="tag-list">
                                    <?php foreach ($tags as $tag): ?>
                                        <a href="/career-advice.php?tag=<?= htmlspecialchars($tag['slug']) ?>" 
                                           class="tag-item">
                                            #<?= htmlspecialchars($tag['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Share Buttons -->
                        <div class="share-section">
                            <h5>Share this article:</h5>
                            <div class="share-buttons">
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($article['title']) ?>" 
                                   target="_blank" class="share-button twitter">
                                    <i class="fab fa-twitter"></i>
                                    Twitter
                                </a>
                                
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   target="_blank" class="share-button linkedin">
                                    <i class="fab fa-linkedin"></i>
                                    LinkedIn
                                </a>
                                
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   target="_blank" class="share-button facebook">
                                    <i class="fab fa-facebook"></i>
                                    Facebook
                                </a>
                                
                                <button onclick="navigator.clipboard.writeText(window.location.href)" 
                                        class="share-button copy">
                                    <i class="fas fa-link"></i>
                                    Copy Link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="article-sidebar">
                        <!-- Author Info -->
                        <?php if ($article['first_name']): ?>
                            <div class="sidebar-widget">
                                <h4 class="widget-title">About the Author</h4>
                                <div class="author-info">
                                    <div class="author-avatar">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($article['first_name'] . ' ' . $article['last_name']) ?>&background=0d6efd&color=fff&size=80&rounded=true" 
                                             alt="Author Avatar">
                                    </div>
                                    <h5 class="author-name">
                                        <?= htmlspecialchars($article['first_name'] . ' ' . $article['last_name']) ?>
                                    </h5>
                                    <p class="author-bio">
                                        Career development expert with years of experience in helping professionals 
                                        achieve their career goals.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Related Articles -->
                        <?php if (!empty($related_articles)): ?>
                            <div class="sidebar-widget">
                                <h4 class="widget-title">Related Articles</h4>
                                <div class="related-articles">
                                    <?php foreach ($related_articles as $related): ?>
                                        <div class="related-article">
                                            <?php if ($related['featured_image']): ?>
                                                <div class="related-image">
                                                    <img src="<?= htmlspecialchars($related['featured_image']) ?>" 
                                                         alt="<?= htmlspecialchars($related['title']) ?>">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="related-content">
                                                <h5 class="related-title">
                                                    <a href="/career-advice/<?= htmlspecialchars($related['slug']) ?>">
                                                        <?= htmlspecialchars($related['title']) ?>
                                                    </a>
                                                </h5>
                                                <p class="related-excerpt">
                                                    <?= htmlspecialchars(mb_strimwidth($related['excerpt'], 0, 80, '...')) ?>
                                                </p>
                                                <div class="related-meta">
                                                    <span class="date">
                                                        <?= date('M j, Y', strtotime($related['published_at'])) ?>
                                                    </span>
                                                    <span class="views">
                                                        <?= number_format($related['views_count']) ?> views
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Newsletter Signup -->
                        <div class="sidebar-widget">
                            <h4 class="widget-title">Stay Updated</h4>
                            <div class="newsletter-signup">
                                <p>Get the latest career advice and job search tips delivered to your inbox.</p>
                                <form class="newsletter-form">
                                    <input type="email" placeholder="Enter your email" class="form-control mb-2" required>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-envelope me-2"></i>Subscribe
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Copy link functionality
document.querySelector('.share-button.copy').addEventListener('click', function() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        const button = document.querySelector('.share-button.copy');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.style.background = '#28a745';
        
        setTimeout(function() {
            button.innerHTML = originalText;
            button.style.background = '';
        }, 2000);
    });
});
</script>

<style>
/* Career Advice Article Styles */
.article-container {
    background: white;
    min-height: 100vh;
}

.article-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
}

.article-categories {
    margin-bottom: 1rem;
}

.category-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.9rem;
    margin-right: 0.5rem;
    transition: all 0.2s ease;
}

.category-badge:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}

.article-title {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 1.5rem;
}

.article-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.info-left,
.info-right {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.info-left span,
.info-right span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.featured-badge {
    background: #ffd700;
    color: #1a3353;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.article-featured-image {
    padding: 2rem 0;
    background: #f8f9fa;
}

.article-featured-image img {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 12px;
}

.article-content {
    padding: 3rem 0;
}

.content-wrapper {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.article-excerpt {
    background: #f8f9fa;
    border-left: 4px solid #ffd700;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-radius: 0 8px 8px 0;
}

.article-excerpt .lead {
    font-size: 1.1rem;
    color: #495057;
    margin: 0;
    line-height: 1.6;
}

.article-body {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
}

.article-body h2 {
    color: #1a3353;
    font-size: 1.8rem;
    font-weight: 600;
    margin: 2rem 0 1rem;
    border-bottom: 2px solid #ffd700;
    padding-bottom: 0.5rem;
}

.article-body h3 {
    color: #1a3353;
    font-size: 1.4rem;
    font-weight: 600;
    margin: 1.5rem 0 1rem;
}

.article-body p {
    margin-bottom: 1.5rem;
}

.article-body ul,
.article-body ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.article-body li {
    margin-bottom: 0.5rem;
}

.article-body blockquote {
    background: #f8f9fa;
    border-left: 4px solid #ffd700;
    padding: 1.5rem;
    margin: 2rem 0;
    font-style: italic;
    border-radius: 0 8px 8px 0;
}

.article-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.article-tags {
    margin: 2rem 0;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}

.article-tags h5 {
    color: #1a3353;
    margin-bottom: 1rem;
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    text-decoration: none;
    color: #495057;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.tag-item:hover {
    background: #ffd700;
    color: #1a3353;
    border-color: #ffd700;
}

.share-section {
    margin: 2rem 0;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}

.share-section h5 {
    color: #1a3353;
    margin-bottom: 1rem;
}

.share-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.share-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.share-button.twitter {
    background: #1da1f2;
    color: white;
}

.share-button.twitter:hover {
    background: #1a91da;
    color: white;
}

.share-button.linkedin {
    background: #0077b5;
    color: white;
}

.share-button.linkedin:hover {
    background: #006097;
    color: white;
}

.share-button.facebook {
    background: #1877f2;
    color: white;
}

.share-button.facebook:hover {
    background: #166fe5;
    color: white;
}

.share-button.copy {
    background: #6c757d;
    color: white;
}

.share-button.copy:hover {
    background: #5a6268;
    color: white;
}

.article-sidebar {
    position: sticky;
    top: 2rem;
}

.sidebar-widget {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.widget-title {
    color: #1a3353;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border-bottom: 2px solid #ffd700;
    padding-bottom: 0.5rem;
}

.author-info {
    text-align: center;
}

.author-avatar {
    margin-bottom: 1rem;
}

.author-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid #ffd700;
}

.author-name {
    color: #1a3353;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.author-bio {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0;
}

.related-articles {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.related-article {
    display: flex;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.related-article:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.related-image {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.related-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-content {
    flex: 1;
}

.related-title {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.related-title a {
    color: #1a3353;
    text-decoration: none;
}

.related-title a:hover {
    color: #ffd700;
}

.related-excerpt {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.related-meta {
    font-size: 0.7rem;
    color: #6c757d;
    display: flex;
    gap: 1rem;
}

.newsletter-signup {
    text-align: center;
}

.newsletter-signup p {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.newsletter-form .btn-primary {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    border: none;
    color: #1a3353;
    font-weight: 600;
}

.newsletter-form .btn-primary:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .article-title {
        font-size: 2rem;
    }
    
    .article-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .info-left,
    .info-right {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .content-wrapper {
        padding: 1.5rem;
    }
    
    .article-sidebar {
        position: static;
        margin-top: 2rem;
    }
    
    .share-buttons {
        justify-content: center;
    }
}
</style>

<?php include 'includes/footer_main.php'; ?> 