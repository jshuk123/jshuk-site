<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

// Get filter parameters
$category = $_GET['category'] ?? '';
$tag = $_GET['tag'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

try {
    // Build query with filters
    $where_conditions = ["caa.status = 'published'"];
    $params = [];
    $joins = [];

    if (!empty($category)) {
        $joins[] = "LEFT JOIN article_category_relations acr ON caa.id = acr.article_id";
        $joins[] = "LEFT JOIN article_categories ac ON acr.category_id = ac.id";
        $where_conditions[] = "ac.slug = ?";
        $params[] = $category;
    }

    if (!empty($tag)) {
        $joins[] = "LEFT JOIN article_tag_relations atr ON caa.id = atr.article_id";
        $joins[] = "LEFT JOIN article_tags at ON atr.tag_id = at.id";
        $where_conditions[] = "at.slug = ?";
        $params[] = $tag;
    }

    if (!empty($search)) {
        $where_conditions[] = "(caa.title LIKE ? OR caa.excerpt LIKE ? OR caa.content LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    $where_clause = implode(" AND ", $where_conditions);
    $join_clause = implode(" ", $joins);

    // Get total count for pagination
    $count_stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT caa.id) as total
        FROM career_advice_articles caa
        $join_clause
        WHERE $where_clause
    ");
    $count_stmt->execute($params);
    $total_articles = $count_stmt->fetchColumn();

    // Get articles
    $stmt = $pdo->prepare("
        SELECT DISTINCT caa.*, u.first_name, u.last_name
        FROM career_advice_articles caa
        LEFT JOIN users u ON caa.author_id = u.id
        $join_clause
        WHERE $where_clause
        ORDER BY caa.published_at DESC, caa.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get categories for filter
    $stmt = $pdo->prepare("
        SELECT ac.name, ac.slug, COUNT(acr.article_id) as article_count
        FROM article_categories ac
        LEFT JOIN article_category_relations acr ON ac.id = acr.category_id
        LEFT JOIN career_advice_articles caa ON acr.article_id = caa.id AND caa.status = 'published'
        WHERE ac.is_active = 1
        GROUP BY ac.id
        ORDER BY ac.sort_order, ac.name
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get popular tags
    $stmt = $pdo->prepare("
        SELECT at.name, at.slug, COUNT(atr.article_id) as article_count
        FROM article_tags at
        LEFT JOIN article_tag_relations atr ON at.id = atr.tag_id
        LEFT JOIN career_advice_articles caa ON atr.article_id = caa.id AND caa.status = 'published'
        GROUP BY at.id
        HAVING article_count > 0
        ORDER BY article_count DESC, at.name
        LIMIT 20
    ");
    $stmt->execute();
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_pages = ceil($total_articles / $per_page);

} catch (PDOException $e) {
    error_log("Career Advice Error: " . $e->getMessage());
    $articles = [];
    $categories = [];
    $tags = [];
    $total_pages = 0;
    $total_articles = 0;
}

$pageTitle = "Career Advice - Professional Development Resources";
$page_css = "career_advice.css";
$metaDescription = "Discover expert career advice, interview tips, resume writing guidance, and professional development resources to advance your career.";
include 'includes/header_main.php';
?>

<div class="career-advice-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Career Advice</h1>
                <p class="hero-subtitle">
                    Expert guidance to help you advance your career, ace interviews, 
                    write compelling resumes, and develop professional skills.
                </p>
                
                <!-- Search Form -->
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <input type="text" name="search" placeholder="Search career advice..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <div class="sidebar">
                        <!-- Categories Filter -->
                        <div class="sidebar-widget">
                            <h4 class="widget-title">
                                <i class="fas fa-folder me-2"></i>Categories
                            </h4>
                            <div class="category-list">
                                <a href="?<?= http_build_query(array_merge($_GET, ['category' => '', 'page' => 1])) ?>" 
                                   class="category-item <?= empty($category) ? 'active' : '' ?>">
                                    <span class="category-name">All Articles</span>
                                    <span class="category-count"><?= $total_articles ?></span>
                                </a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['category' => $cat['slug'], 'page' => 1])) ?>" 
                                       class="category-item <?= $category === $cat['slug'] ? 'active' : '' ?>">
                                        <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                        <span class="category-count"><?= $cat['article_count'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Popular Tags -->
                        <?php if (!empty($tags)): ?>
                        <div class="sidebar-widget">
                            <h4 class="widget-title">
                                <i class="fas fa-tags me-2"></i>Popular Tags
                            </h4>
                            <div class="tag-cloud">
                                <?php foreach ($tags as $tag_item): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['tag' => $tag_item['slug'], 'page' => 1])) ?>" 
                                       class="tag-item <?= $tag === $tag_item['slug'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($tag_item['name']) ?>
                                        <span class="tag-count"><?= $tag_item['article_count'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Featured Articles -->
                        <div class="sidebar-widget">
                            <h4 class="widget-title">
                                <i class="fas fa-star me-2"></i>Featured Articles
                            </h4>
                            <div class="featured-articles">
                                <?php
                                $featured_stmt = $pdo->prepare("
                                    SELECT caa.title, caa.slug, caa.excerpt, caa.views_count
                                    FROM career_advice_articles caa
                                    WHERE caa.status = 'published' AND caa.is_featured = 1
                                    ORDER BY caa.views_count DESC, caa.published_at DESC
                                    LIMIT 3
                                ");
                                $featured_stmt->execute();
                                $featured_articles = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <?php foreach ($featured_articles as $featured): ?>
                                    <div class="featured-article">
                                        <h5 class="featured-title">
                                            <a href="/career-advice/<?= htmlspecialchars($featured['slug']) ?>">
                                                <?= htmlspecialchars($featured['title']) ?>
                                            </a>
                                        </h5>
                                        <p class="featured-excerpt">
                                            <?= htmlspecialchars(mb_strimwidth($featured['excerpt'], 0, 80, '...')) ?>
                                        </p>
                                        <div class="featured-meta">
                                            <span class="views">
                                                <i class="fas fa-eye"></i>
                                                <?= number_format($featured['views_count']) ?> views
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Articles Grid -->
                <div class="col-lg-9">
                    <!-- Results Header -->
                    <div class="results-header">
                        <div class="results-info">
                            <h2 class="results-title">
                                <?php if (!empty($search)): ?>
                                    Search Results for "<?= htmlspecialchars($search) ?>"
                                <?php elseif (!empty($category)): ?>
                                    <?= htmlspecialchars(array_filter($categories, fn($c) => $c['slug'] === $category)[0]['name'] ?? 'Category') ?>
                                <?php elseif (!empty($tag)): ?>
                                    Tag: <?= htmlspecialchars(array_filter($tags, fn($t) => $t['slug'] === $tag)[0]['name'] ?? 'Tag') ?>
                                <?php else: ?>
                                    All Career Advice Articles
                                <?php endif; ?>
                            </h2>
                            <p class="results-count">
                                <?= $total_articles ?> article<?= $total_articles != 1 ? 's' : '' ?>
                                <?php if ($total_pages > 1): ?>
                                    • Page <?= $page ?> of <?= $total_pages ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- Clear Filters -->
                        <?php if (!empty($category) || !empty($tag) || !empty($search)): ?>
                            <a href="?" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($articles)): ?>
                        <div class="no-results">
                            <div class="no-results-icon">
                                <i class="fas fa-search fa-3x text-muted"></i>
                            </div>
                            <h3>No Articles Found</h3>
                            <p>
                                <?php if (!empty($search)): ?>
                                    No articles found matching "<?= htmlspecialchars($search) ?>". 
                                    Try different keywords or browse our categories.
                                <?php else: ?>
                                    No articles available in this category. 
                                    Check back soon for new content!
                                <?php endif; ?>
                            </p>
                            <a href="?" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>View All Articles
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Articles Grid -->
                        <div class="articles-grid">
                            <?php foreach ($articles as $article): ?>
                                <div class="article-card">
                                    <div class="article-image">
                                        <?php if ($article['featured_image']): ?>
                                            <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                                                 alt="<?= htmlspecialchars($article['title']) ?>">
                                        <?php else: ?>
                                            <div class="article-placeholder">
                                                <i class="fas fa-newspaper"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($article['is_featured']): ?>
                                            <span class="featured-badge">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="article-content">
                                        <h3 class="article-title">
                                            <a href="/career-advice/<?= htmlspecialchars($article['slug']) ?>">
                                                <?= htmlspecialchars($article['title']) ?>
                                            </a>
                                        </h3>
                                        
                                        <p class="article-excerpt">
                                            <?= htmlspecialchars(mb_strimwidth($article['excerpt'] ?: $article['content'], 0, 120, '...')) ?>
                                        </p>
                                        
                                        <div class="article-meta">
                                            <div class="meta-left">
                                                <?php if ($article['first_name']): ?>
                                                    <span class="author">
                                                        <i class="fas fa-user"></i>
                                                        <?= htmlspecialchars($article['first_name'] . ' ' . $article['last_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <span class="date">
                                                    <i class="fas fa-calendar"></i>
                                                    <?= date('M j, Y', strtotime($article['published_at'])) ?>
                                                </span>
                                            </div>
                                            
                                            <div class="meta-right">
                                                <span class="views">
                                                    <i class="fas fa-eye"></i>
                                                    <?= number_format($article['views_count']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav class="pagination-wrapper">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Career Advice Styles */
.career-advice-container {
    background: #f8f9fa;
    min-height: 100vh;
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 2rem;
}

.search-form {
    max-width: 500px;
    margin: 0 auto;
}

.search-input-group {
    display: flex;
    background: white;
    border-radius: 50px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.search-input {
    flex: 1;
    border: none;
    padding: 1rem 1.5rem;
    font-size: 1rem;
    outline: none;
}

.search-button {
    background: #ffd700;
    border: none;
    padding: 1rem 1.5rem;
    color: #1a3353;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-button:hover {
    background: #ffcc00;
}

.main-content {
    padding: 3rem 0;
}

.sidebar {
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

.category-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    text-decoration: none;
    color: #495057;
    transition: all 0.2s ease;
}

.category-item:hover {
    background: #f8f9fa;
    color: #1a3353;
}

.category-item.active {
    background: #ffd700;
    color: #1a3353;
    font-weight: 600;
}

.category-count {
    background: rgba(0,0,0,0.1);
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
}

.tag-cloud {
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
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tag-item:hover {
    background: #ffd700;
    color: #1a3353;
    border-color: #ffd700;
}

.tag-item.active {
    background: #ffd700;
    color: #1a3353;
    border-color: #ffd700;
}

.tag-count {
    background: rgba(0,0,0,0.1);
    padding: 0.1rem 0.4rem;
    border-radius: 10px;
    font-size: 0.7rem;
}

.featured-articles {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.featured-article {
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.featured-article:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.featured-title {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.featured-title a {
    color: #1a3353;
    text-decoration: none;
}

.featured-title a:hover {
    color: #ffd700;
}

.featured-excerpt {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.featured-meta {
    font-size: 0.7rem;
    color: #6c757d;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.results-title {
    color: #1a3353;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.results-count {
    color: #6c757d;
    margin: 0;
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.article-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.article-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border-color: #ffd700;
}

.article-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.article-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.article-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #ffd700 0%, #ffcc00 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a3353;
    font-size: 3rem;
}

.featured-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #ffd700;
    color: #1a3353;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.article-content {
    padding: 1.5rem;
}

.article-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.article-title a {
    color: #1a3353;
    text-decoration: none;
}

.article-title a:hover {
    color: #ffd700;
}

.article-excerpt {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.article-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #6c757d;
}

.meta-left {
    display: flex;
    gap: 1rem;
}

.meta-left span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
}

.no-results-icon {
    margin-bottom: 1rem;
}

.no-results h3 {
    color: #1a3353;
    margin-bottom: 1rem;
}

.no-results p {
    color: #6c757d;
    margin-bottom: 2rem;
}

.pagination-wrapper {
    margin-top: 3rem;
}

.pagination .page-link {
    color: #1a3353;
    border-color: #e9ecef;
}

.pagination .page-item.active .page-link {
    background: #ffd700;
    border-color: #ffd700;
    color: #1a3353;
}

.pagination .page-link:hover {
    background: #f8f9fa;
    color: #1a3353;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .results-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .articles-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .sidebar {
        position: static;
        margin-bottom: 2rem;
    }
}
</style>

<?php include 'includes/footer_main.php'; ?> 