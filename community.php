<?php
/**
 * Community Hub Page
 * Central feed-style page showing highlights from every community category
 */

require_once 'config/config.php';
require_once 'includes/community_corner_functions.php';

$pageTitle = "Community | JShuk - Jewish Community Hub";
$page_css = "pages/community.css";
$metaDescription = "Connect with your Jewish community through Gemachim, Lost & Found, Ask the Rabbi, Divrei Torah, Simchas, Charity Alerts, and Volunteer Opportunities.";
$metaKeywords = "jewish community, gemachim, lost and found, ask the rabbi, divrei torah, simchas, charity, volunteer, community hub";

include 'includes/header_main.php';

// Get featured items for each community section
$communitySections = [
    'gemach' => [
        'title' => 'Gemachim',
        'emoji' => '🍼',
        'description' => 'Community lending and sharing',
        'url' => '/gemachim.php',
        'items' => getCommunityCornerItemsByType('gemach', 2)
    ],
    'lost_found' => [
        'title' => 'Lost & Found',
        'emoji' => '🎒',
        'description' => 'Help reunite people with lost items',
        'url' => '/lost_and_found.php',
        'items' => getCommunityCornerItemsByType('lost_found', 2)
    ],
    'ask_rabbi' => [
        'title' => 'Ask the Rabbi',
        'emoji' => '📜',
        'description' => 'Halachic questions and answers',
        'url' => '/ask-the-rabbi.php',
        'items' => getCommunityCornerItemsByType('ask_rabbi', 2)
    ],
    'divrei_torah' => [
        'title' => 'Divrei Torah',
        'emoji' => '🕯️',
        'description' => 'Weekly Torah insights',
        'url' => '/divrei-torah.php',
        'items' => getCommunityCornerItemsByType('divrei_torah', 2)
    ],
    'simcha' => [
        'title' => 'Simcha Notices',
        'emoji' => '🎉',
        'description' => 'Celebrate lifecycle events',
        'url' => '/simchas.php',
        'items' => getCommunityCornerItemsByType('simcha', 2)
    ],
    'charity_alert' => [
        'title' => 'Charity Alerts',
        'emoji' => '❤️',
        'description' => 'Urgent community needs',
        'url' => '/charity_alerts.php',
        'items' => getCommunityCornerItemsByType('charity_alert', 2)
    ],
    'volunteer' => [
        'title' => 'Volunteer Opportunities',
        'emoji' => '🤝',
        'description' => 'Mitzvah projects and help needed',
        'url' => '/volunteer.php',
        'items' => getCommunityCornerItemsByType('volunteer', 2)
    ]
];
?>

<div class="community-hub">
    <!-- Hero Section -->
    <section class="community-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">Community Hub</h1>
                <p class="hero-subtitle">Connect, share, and support your Jewish community</p>
                <div class="hero-emoji">🫶</div>
            </div>
        </div>
    </section>

    <!-- Community Sections -->
    <div class="container">
        <?php foreach ($communitySections as $sectionKey => $section): ?>
            <section class="community-block" id="<?= $sectionKey ?>">
                <div class="section-header">
                    <div class="section-icon"><?= $section['emoji'] ?></div>
                    <div class="section-info">
                        <h2 class="section-title"><?= $section['title'] ?></h2>
                        <p class="section-description"><?= $section['description'] ?></p>
                    </div>
                </div>

                <div class="section-content">
                    <?php if (!empty($section['items'])): ?>
                        <div class="items-grid">
                            <?php foreach ($section['items'] as $item): ?>
                                <div class="item-card">
                                    <div class="item-emoji"><?= htmlspecialchars($item['emoji']) ?></div>
                                    <div class="item-content">
                                        <h3 class="item-title"><?= htmlspecialchars($item['title']) ?></h3>
                                        <p class="item-text"><?= htmlspecialchars($item['body_text']) ?></p>
                                        <div class="item-meta">
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
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon"><?= $section['emoji'] ?></div>
                            <p>No <?= strtolower($section['title']) ?> items yet.</p>
                        </div>
                    <?php endif; ?>

                    <div class="section-actions">
                        <a href="<?= $section['url'] ?>" class="btn-see-more">
                            See more <?= $section['emoji'] ?> →
                        </a>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <!-- Community Guidelines -->
        <section class="community-guidelines">
            <div class="guidelines-card">
                <div class="guidelines-header">
                    <h3>Community Guidelines</h3>
                    <p>Help keep our community safe and supportive</p>
                </div>
                <div class="guidelines-content">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Be Respectful</h4>
                            <ul>
                                <li>Treat everyone with kindness and respect</li>
                                <li>Use appropriate language and tone</li>
                                <li>Respect privacy and confidentiality</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4>Be Helpful</h4>
                            <ul>
                                <li>Share accurate and helpful information</li>
                                <li>Respond to requests when you can help</li>
                                <li>Update posts when items are found or resolved</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include 'includes/footer_main.php'; ?> 