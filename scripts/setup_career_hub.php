<?php
/**
 * Career Hub Setup Script
 * 
 * This script sets up the database tables and initial data for the Career Hub
 * features including salary guides and career advice blog.
 */

require_once '../config/config.php';

echo "🚀 Setting up Career Hub...\n\n";

try {
    // Read and execute the SQL file
    $sql_file = '../sql/create_career_hub.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $pdo->exec($statement);
            $success_count++;
            echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            $error_count++;
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "   Statement: " . substr($statement, 0, 100) . "...\n\n";
        }
    }
    
    echo "\n📊 Execution Summary:\n";
    echo "   ✅ Successful statements: $success_count\n";
    echo "   ❌ Failed statements: $error_count\n\n";
    
    // Verify table creation
    echo "🔍 Verifying table creation...\n";
    
    $required_tables = [
        'salary_data',
        'career_advice_articles',
        'article_categories',
        'article_category_relations',
        'article_tags',
        'article_tag_relations'
    ];
    
    $existing_tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    $missing_tables = array_diff($required_tables, $existing_tables);
    
    if (empty($missing_tables)) {
        echo "✅ All required tables created successfully!\n\n";
    } else {
        echo "❌ Missing tables: " . implode(', ', $missing_tables) . "\n\n";
    }
    
    // Create sample career advice articles
    echo "🎯 Creating sample career advice articles...\n";
    
    $sample_articles = [
        [
            'title' => '10 Essential Interview Tips to Land Your Dream Job',
            'slug' => '10-essential-interview-tips',
            'excerpt' => 'Master the art of interviewing with these proven strategies that will help you stand out from the competition and secure your dream job.',
            'content' => '
<h2>Introduction</h2>
<p>Interviews can be nerve-wracking, but with the right preparation and mindset, you can turn them into opportunities to showcase your skills and personality. In this comprehensive guide, we\'ll share 10 essential interview tips that will help you make a lasting impression.</p>

<h2>1. Research the Company Thoroughly</h2>
<p>Before your interview, take the time to research the company\'s history, mission, values, and recent news. This knowledge will help you ask informed questions and demonstrate genuine interest in the organization.</p>

<h2>2. Practice Common Interview Questions</h2>
<p>Prepare answers for common questions like "Tell me about yourself," "Why do you want this job?" and "What are your strengths and weaknesses?" Practice your responses until they feel natural.</p>

<h2>3. Prepare Your Own Questions</h2>
<p>Having thoughtful questions ready shows your engagement and helps you evaluate if the role is right for you. Ask about company culture, growth opportunities, and day-to-day responsibilities.</p>

<h2>4. Dress Appropriately</h2>
<p>Choose professional attire that matches the company culture. When in doubt, it\'s better to be slightly overdressed than underdressed.</p>

<h2>5. Arrive Early</h2>
<p>Plan to arrive 10-15 minutes before your scheduled interview time. This gives you a buffer for unexpected delays and helps you feel calm and collected.</p>

<h2>6. Make a Strong First Impression</h2>
<p>Greet everyone with a firm handshake, maintain eye contact, and offer a genuine smile. Your first impression can set the tone for the entire interview.</p>

<h2>7. Use the STAR Method</h2>
<p>When answering behavioral questions, use the STAR method: Situation, Task, Action, Result. This structured approach helps you provide comprehensive, relevant answers.</p>

<h2>8. Show Enthusiasm</h2>
<p>Demonstrate genuine excitement about the opportunity. Employers want to hire people who are passionate about their work and the company.</p>

<h2>9. Follow Up</h2>
<p>Send a thank-you email within 24 hours of your interview. Express your appreciation, reiterate your interest, and mention something specific from the conversation.</p>

<h2>10. Learn from Each Experience</h2>
<p>Every interview is a learning opportunity. Reflect on what went well and what you could improve for next time.</p>

<h2>Conclusion</h2>
<p>Remember, interviews are a two-way street. While you\'re being evaluated, you\'re also evaluating whether the company and role are right for you. Stay confident, be authentic, and trust in your preparation.</p>',
            'category' => 'Interview Tips'
        ],
        [
            'title' => 'How to Write a Standout Resume in 2024',
            'slug' => 'how-to-write-standout-resume-2024',
            'excerpt' => 'Learn the latest resume writing techniques and best practices to create a compelling CV that gets you noticed by recruiters and hiring managers.',
            'content' => '
<h2>The Modern Resume Landscape</h2>
<p>In today\'s competitive job market, your resume needs to do more than just list your experience—it needs to tell your story and demonstrate your value. Here\'s how to create a resume that stands out in 2024.</p>

<h2>1. Start with a Strong Summary</h2>
<p>Your professional summary should be a compelling 2-3 sentence overview that highlights your key achievements and career goals. Make it specific and results-oriented.</p>

<h2>2. Use Action Verbs</h2>
<p>Begin each bullet point with strong action verbs like "achieved," "developed," "implemented," or "led." This makes your accomplishments more impactful and engaging.</p>

<h2>3. Quantify Your Achievements</h2>
<p>Whenever possible, include specific numbers and metrics. Instead of "increased sales," say "increased sales by 25% over 6 months."</p>

<h2>4. Tailor for Each Application</h2>
<p>Customize your resume for each job by highlighting relevant experience and using keywords from the job description. This shows you\'ve done your research.</p>

<h2>5. Keep It Concise</h2>
<p>Aim for 1-2 pages maximum. Focus on the most relevant and recent experience that demonstrates your qualifications for the target role.</p>

<h2>6. Use Modern Formatting</h2>
<p>Choose a clean, professional template that\'s easy to read. Use consistent formatting, appropriate fonts, and plenty of white space.</p>

<h2>7. Include Relevant Skills</h2>
<p>Add a skills section that highlights both technical and soft skills relevant to your target position. Consider including proficiency levels.</p>

<h2>8. Proofread Thoroughly</h2>
<p>Eliminate all typos and grammatical errors. Consider having someone else review your resume for a fresh perspective.</p>

<h2>9. Optimize for ATS</h2>
<p>Many companies use Applicant Tracking Systems (ATS). Use standard section headings and include relevant keywords naturally throughout your resume.</p>

<h2>10. Keep It Updated</h2>
<p>Regularly update your resume with new achievements, skills, and experiences. This ensures you\'re always ready for new opportunities.</p>

<h2>Conclusion</h2>
<p>Your resume is often your first impression with potential employers. By following these guidelines, you\'ll create a document that effectively showcases your value and helps you stand out in the competitive job market.</p>',
            'category' => 'Resume Writing'
        ],
        [
            'title' => '5 Strategies for Successful Job Searching in a Digital Age',
            'slug' => '5-strategies-successful-job-searching-digital-age',
            'excerpt' => 'Navigate the modern job market with these proven strategies that leverage technology while maintaining authentic human connections.',
            'content' => '
<h2>The Digital Job Search Revolution</h2>
<p>The job search process has evolved dramatically with technology. While digital tools make job searching more efficient, they also require new strategies to stand out. Here are five key strategies for success.</p>

<h2>1. Build a Strong Online Presence</h2>
<p>Your online presence is often the first thing employers see. Create a professional LinkedIn profile, maintain an updated portfolio website, and ensure your social media accounts reflect your professional brand.</p>

<h2>2. Leverage Networking Platforms</h2>
<p>Use LinkedIn, professional associations, and industry-specific platforms to connect with professionals in your field. Engage with content, share insights, and participate in discussions.</p>

<h2>3. Master the Art of Virtual Networking</h2>
<p>With remote work becoming more common, virtual networking is essential. Attend online industry events, webinars, and virtual meetups to expand your professional network.</p>

<h2>4. Use Job Search Technology Wisely</h2>
<p>Utilize job boards, company career sites, and recruitment apps, but don\'t rely solely on them. Combine digital tools with traditional networking for the best results.</p>

<h2>5. Develop Your Personal Brand</h2>
<p>Define what makes you unique and communicate it consistently across all platforms. Your personal brand should reflect your values, expertise, and career goals.</p>

<h2>Conclusion</h2>
<p>Successful job searching in the digital age requires a balance of technology and human connection. By implementing these strategies, you\'ll be well-positioned to find and secure your next opportunity.</p>',
            'category' => 'Job Search'
        ]
    ];
    
    foreach ($sample_articles as $article_data) {
        // Check if article already exists
        $stmt = $pdo->prepare("SELECT id FROM career_advice_articles WHERE slug = ?");
        $stmt->execute([$article_data['slug']]);
        
        if (!$stmt->fetch()) {
            // Get category ID
            $stmt = $pdo->prepare("SELECT id FROM article_categories WHERE name = ?");
            $stmt->execute([$article_data['category']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Insert article
            $stmt = $pdo->prepare("
                INSERT INTO career_advice_articles (
                    title, slug, excerpt, content, status, published_at, 
                    meta_title, meta_description, is_featured
                ) VALUES (?, ?, ?, ?, 'published', NOW(), ?, ?, ?)
            ");
            
            $stmt->execute([
                $article_data['title'],
                $article_data['slug'],
                $article_data['excerpt'],
                $article_data['content'],
                $article_data['title'],
                $article_data['excerpt'],
                rand(0, 1) // Randomly feature some articles
            ]);
            
            $article_id = $pdo->lastInsertId();
            
            // Link to category
            if ($category) {
                $stmt = $pdo->prepare("
                    INSERT INTO article_category_relations (article_id, category_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$article_id, $category['id']]);
            }
            
            echo "✅ Created article: " . $article_data['title'] . "\n";
        }
    }
    
    // Create sample tags
    echo "\n🎯 Creating sample tags...\n";
    
    $sample_tags = [
        'interview', 'resume', 'career-development', 'job-search', 'networking',
        'leadership', 'communication', 'skills', 'professional-growth', 'success'
    ];
    
    foreach ($sample_tags as $tag_name) {
        $tag_slug = strtolower(str_replace(' ', '-', $tag_name));
        
        // Check if tag exists
        $stmt = $pdo->prepare("SELECT id FROM article_tags WHERE slug = ?");
        $stmt->execute([$tag_slug]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO article_tags (name, slug, description)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$tag_name, $tag_slug, "Articles tagged with $tag_name"]);
            echo "✅ Created tag: $tag_name\n";
        }
    }
    
    // Link some articles to tags
    echo "\n🎯 Linking articles to tags...\n";
    
    $tag_relations = [
        '10-essential-interview-tips' => ['interview', 'career-development', 'success'],
        'how-to-write-standout-resume-2024' => ['resume', 'job-search', 'skills'],
        '5-strategies-successful-job-searching-digital-age' => ['job-search', 'networking', 'professional-growth']
    ];
    
    foreach ($tag_relations as $article_slug => $tags) {
        $stmt = $pdo->prepare("SELECT id FROM career_advice_articles WHERE slug = ?");
        $stmt->execute([$article_slug]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($article) {
            foreach ($tags as $tag_name) {
                $stmt = $pdo->prepare("SELECT id FROM article_tags WHERE name = ?");
                $stmt->execute([$tag_name]);
                $tag = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tag) {
                    // Check if relation already exists
                    $stmt = $pdo->prepare("
                        SELECT id FROM article_tag_relations 
                        WHERE article_id = ? AND tag_id = ?
                    ");
                    $stmt->execute([$article['id'], $tag['id']]);
                    
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("
                            INSERT INTO article_tag_relations (article_id, tag_id)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$article['id'], $tag['id']]);
                    }
                }
            }
        }
    }
    
    echo "\n🎉 Career Hub setup completed successfully!\n\n";
    
    echo "📋 Next Steps:\n";
    echo "   1. Visit /salary-guide.php to test the salary guide\n";
    echo "   2. Visit /career-advice.php to browse career advice articles\n";
    echo "   3. Test individual article pages at /career-advice/[article-slug]\n";
    echo "   4. Add the career advice section to your homepage\n\n";
    
    echo "🔗 Useful URLs:\n";
    echo "   • Salary Guide: /salary-guide.php\n";
    echo "   • Career Advice Blog: /career-advice.php\n";
    echo "   • Sample Article: /career-advice/10-essential-interview-tips\n\n";
    
    echo "📊 Statistics:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM salary_data");
    $salary_count = $stmt->fetchColumn();
    echo "   • Salary data entries: $salary_count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM career_advice_articles WHERE status = 'published'");
    $article_count = $stmt->fetchColumn();
    echo "   • Published articles: $article_count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM article_categories");
    $category_count = $stmt->fetchColumn();
    echo "   • Article categories: $category_count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM article_tags");
    $tag_count = $stmt->fetchColumn();
    echo "   • Article tags: $tag_count\n\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 