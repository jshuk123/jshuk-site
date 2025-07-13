<?php
/**
 * Enhanced Carousel Migration Script
 * JShuk Advanced Carousel Management System
 * Phase 6: Database Migration
 */

require_once '../config/config.php';

echo "🚀 Starting Enhanced Carousel Migration...\n\n";

try {
    // Check if we're in a safe environment
    if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
        echo "✅ Safe environment detected (localhost)\n";
    } else {
        echo "⚠️  WARNING: Running on production server. Are you sure? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 'y') {
            echo "❌ Migration cancelled by user\n";
            exit(1);
        }
    }
    
    echo "\n📊 Checking current database state...\n";
    
    // Check if old carousel_ads table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_ads'");
    $oldTableExists = $stmt->rowCount() > 0;
    
    if ($oldTableExists) {
        echo "✅ Found existing carousel_ads table\n";
        
        // Count existing records
        $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads");
        $oldRecordCount = $stmt->fetchColumn();
        echo "📈 Found {$oldRecordCount} existing carousel records\n";
    } else {
        echo "ℹ️  No existing carousel_ads table found\n";
    }
    
    // Check if new tables already exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_slides'");
    $newTableExists = $stmt->rowCount() > 0;
    
    if ($newTableExists) {
        echo "⚠️  Enhanced carousel tables already exist\n";
        echo "Do you want to drop and recreate them? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) === 'y') {
            echo "🗑️  Dropping existing enhanced carousel tables...\n";
            $pdo->exec("DROP TABLE IF EXISTS carousel_analytics_summary");
            $pdo->exec("DROP TABLE IF EXISTS carousel_analytics");
            $pdo->exec("DROP TABLE IF EXISTS carousel_slides");
            $pdo->exec("DROP TABLE IF EXISTS location_mappings");
            echo "✅ Existing tables dropped\n";
        } else {
            echo "❌ Migration cancelled - tables already exist\n";
            exit(1);
        }
    }
    
    echo "\n🏗️  Creating enhanced carousel database schema...\n";
    
    // Create enhanced carousel_slides table
    $pdo->exec("
        CREATE TABLE carousel_slides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            subtitle TEXT,
            image_url VARCHAR(255) NOT NULL,
            cta_text VARCHAR(100),
            cta_link VARCHAR(255),
            priority INT DEFAULT 0,
            location VARCHAR(100) DEFAULT 'all',
            sponsored TINYINT(1) DEFAULT 0,
            start_date DATE,
            end_date DATE,
            active TINYINT(1) DEFAULT 1,
            zone VARCHAR(100) DEFAULT 'homepage',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_active_priority (active, priority),
            INDEX idx_location (location),
            INDEX idx_zone (zone),
            INDEX idx_dates (start_date, end_date),
            INDEX idx_sponsored (sponsored),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created carousel_slides table\n";
    
    // Create analytics table
    $pdo->exec("
        CREATE TABLE carousel_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slide_id INT NOT NULL,
            event_type ENUM('impression', 'click', 'hover') NOT NULL,
            user_location VARCHAR(100),
            user_agent TEXT,
            ip_address VARCHAR(45),
            session_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (slide_id) REFERENCES carousel_slides(id) ON DELETE CASCADE,
            
            INDEX idx_slide_event (slide_id, event_type),
            INDEX idx_created_at (created_at),
            INDEX idx_location (user_location)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created carousel_analytics table\n";
    
    // Create location mappings table
    $pdo->exec("
        CREATE TABLE location_mappings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            location_name VARCHAR(100) NOT NULL,
            latitude_min DECIMAL(10, 8),
            latitude_max DECIMAL(10, 8),
            longitude_min DECIMAL(10, 8),
            longitude_max DECIMAL(10, 8),
            display_name VARCHAR(100) NOT NULL,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_location (location_name),
            INDEX idx_coordinates (latitude_min, latitude_max, longitude_min, longitude_max)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created location_mappings table\n";
    
    // Create analytics summary table
    $pdo->exec("
        CREATE TABLE carousel_analytics_summary (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slide_id INT NOT NULL,
            event_type ENUM('impression', 'click', 'hover') NOT NULL,
            count INT DEFAULT 1,
            date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_summary (slide_id, event_type, date),
            FOREIGN KEY (slide_id) REFERENCES carousel_slides(id) ON DELETE CASCADE,
            INDEX idx_analytics_summary_date (date),
            INDEX idx_analytics_summary_slide (slide_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Created carousel_analytics_summary table\n";
    
    // Insert default location mappings
    $pdo->exec("
        INSERT INTO location_mappings (location_name, latitude_min, latitude_max, longitude_min, longitude_max, display_name) VALUES
        ('london', 51.3, 51.7, -0.5, 0.3, 'London'),
        ('manchester', 53.4, 53.5, -2.3, -2.1, 'Manchester'),
        ('gateshead', 54.9, 55.0, -1.7, -1.5, 'Gateshead'),
        ('all', NULL, NULL, NULL, NULL, 'All Locations')
    ");
    echo "✅ Inserted default location mappings\n";
    
    // Migrate existing data if available
    if ($oldTableExists && $oldRecordCount > 0) {
        echo "\n🔄 Migrating existing carousel data...\n";
        
        $stmt = $pdo->query("SELECT * FROM carousel_ads ORDER BY position ASC");
        $oldSlides = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $migratedCount = 0;
        foreach ($oldSlides as $oldSlide) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO carousel_slides (
                        title, subtitle, image_url, cta_text, cta_link, 
                        priority, location, sponsored, active, zone, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $oldSlide['title'] ?? 'Migrated Slide',
                    $oldSlide['subtitle'] ?? '',
                    $oldSlide['image_path'] ?? '',
                    $oldSlide['cta_text'] ?? '',
                    $oldSlide['cta_url'] ?? '',
                    $oldSlide['position'] ?? 0,
                    'all', // Default to all locations
                    $oldSlide['is_auto_generated'] ?? 0,
                    $oldSlide['active'] ?? 1,
                    'homepage', // Default to homepage zone
                    $oldSlide['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $migratedCount++;
                echo "✅ Migrated slide: {$oldSlide['title']}\n";
                
            } catch (Exception $e) {
                echo "❌ Failed to migrate slide {$oldSlide['title']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "✅ Successfully migrated {$migratedCount} out of {$oldRecordCount} slides\n";
        
        // Create backup of old table
        echo "\n💾 Creating backup of old carousel_ads table...\n";
        $pdo->exec("CREATE TABLE carousel_ads_backup_" . date('Y_m_d_H_i_s') . " AS SELECT * FROM carousel_ads");
        echo "✅ Backup created: carousel_ads_backup_" . date('Y_m_d_H_i_s') . "\n";
        
        // Ask if user wants to drop old table
        echo "\nDo you want to drop the old carousel_ads table? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) === 'y') {
            $pdo->exec("DROP TABLE carousel_ads");
            echo "🗑️  Old carousel_ads table dropped\n";
        } else {
            echo "ℹ️  Old carousel_ads table preserved\n";
        }
        
    } else {
        // Insert sample data if no existing data
        echo "\n📝 Inserting sample carousel data...\n";
        
        $sampleSlides = [
            [
                'title' => 'Welcome to JShuk',
                'subtitle' => 'Your Jewish Community Hub - Discover Local Businesses',
                'image_url' => 'uploads/carousel/sample_ad1.jpg',
                'cta_text' => 'Explore Now',
                'cta_link' => 'businesses.php',
                'priority' => 10,
                'location' => 'all',
                'sponsored' => 0
            ],
            [
                'title' => 'Kosher Restaurants in London',
                'subtitle' => 'Find the best kosher dining in London',
                'image_url' => 'uploads/carousel/sample_ad2.jpg',
                'cta_text' => 'Find Restaurants',
                'cta_link' => 'businesses.php?category=restaurants&location=london',
                'priority' => 8,
                'location' => 'london',
                'sponsored' => 0
            ],
            [
                'title' => 'Community Events',
                'subtitle' => 'Stay connected with your local Jewish community',
                'image_url' => 'uploads/carousel/sample_ad3.jpg',
                'cta_text' => 'View Events',
                'cta_link' => 'events.php',
                'priority' => 5,
                'location' => 'all',
                'sponsored' => 0
            ],
            [
                'title' => 'Sponsored: Premium Business',
                'subtitle' => 'Featured business promotion',
                'image_url' => 'uploads/carousel/sponsored_ad.jpg',
                'cta_text' => 'Learn More',
                'cta_link' => 'businesses.php?id=123',
                'priority' => 15,
                'location' => 'all',
                'sponsored' => 1,
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+30 days'))
            ]
        ];
        
        foreach ($sampleSlides as $slide) {
            $stmt = $pdo->prepare("
                INSERT INTO carousel_slides (
                    title, subtitle, image_url, cta_text, cta_link, 
                    priority, location, sponsored, start_date, end_date, active, zone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'homepage')
            ");
            
            $stmt->execute([
                $slide['title'],
                $slide['subtitle'],
                $slide['image_url'],
                $slide['cta_text'],
                $slide['cta_link'],
                $slide['priority'],
                $slide['location'],
                $slide['sponsored'],
                $slide['start_date'] ?? null,
                $slide['end_date'] ?? null
            ]);
        }
        
        echo "✅ Inserted " . count($sampleSlides) . " sample slides\n";
    }
    
    // Create views
    echo "\n👁️  Creating database views...\n";
    
    $pdo->exec("
        CREATE OR REPLACE VIEW active_carousel_slides AS
        SELECT * FROM carousel_slides 
        WHERE active = 1 
          AND (start_date IS NULL OR start_date <= CURDATE())
          AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY priority DESC, sponsored DESC, created_at DESC
    ");
    echo "✅ Created active_carousel_slides view\n";
    
    $pdo->exec("
        CREATE OR REPLACE VIEW carousel_performance AS
        SELECT 
            cs.id,
            cs.title,
            cs.location,
            cs.sponsored,
            COUNT(CASE WHEN ca.event_type = 'impression' THEN 1 END) as impressions,
            COUNT(CASE WHEN ca.event_type = 'click' THEN 1 END) as clicks,
            ROUND(
                (COUNT(CASE WHEN ca.event_type = 'click' THEN 1 END) / 
                 NULLIF(COUNT(CASE WHEN ca.event_type = 'impression' THEN 1 END), 0)) * 100, 2
            ) as ctr_percentage
        FROM carousel_slides cs
        LEFT JOIN carousel_analytics ca ON cs.id = ca.slide_id
        GROUP BY cs.id, cs.title, cs.location, cs.sponsored
    ");
    echo "✅ Created carousel_performance view\n";
    
    // Create stored procedure
    echo "\n⚙️  Creating stored procedures...\n";
    
    $pdo->exec("
        DROP PROCEDURE IF EXISTS GetCarouselSlides
    ");
    
    $pdo->exec("
        CREATE PROCEDURE GetCarouselSlides(
            IN p_location VARCHAR(100),
            IN p_zone VARCHAR(100),
            IN p_limit INT
        )
        BEGIN
            SELECT * FROM carousel_slides 
            WHERE active = 1 
              AND (location = p_location OR location = 'all')
              AND zone = p_zone
              AND (start_date IS NULL OR start_date <= CURDATE())
              AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY priority DESC, sponsored DESC, created_at DESC
            LIMIT p_limit;
        END
    ");
    echo "✅ Created GetCarouselSlides stored procedure\n";
    
    // Final verification
    echo "\n🔍 Verifying migration...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides");
    $slideCount = $stmt->fetchColumn();
    echo "✅ Total slides: {$slideCount}\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides WHERE active = 1");
    $activeCount = $stmt->fetchColumn();
    echo "✅ Active slides: {$activeCount}\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM location_mappings");
    $locationCount = $stmt->fetchColumn();
    echo "✅ Location mappings: {$locationCount}\n";
    
    echo "\n🎉 Enhanced Carousel Migration Completed Successfully!\n\n";
    echo "📋 Next Steps:\n";
    echo "1. Update your homepage to use sections/enhanced_carousel.php\n";
    echo "2. Access the admin panel at admin/enhanced_carousel_manager.php\n";
    echo "3. Test the carousel functionality\n";
    echo "4. Configure location-based targeting if needed\n";
    echo "5. Set up analytics tracking\n\n";
    
    echo "🔗 Useful URLs:\n";
    echo "- Admin Panel: /admin/enhanced_carousel_manager.php\n";
    echo "- API Endpoint: /api/carousel-analytics.php\n";
    echo "- Enhanced Carousel: /sections/enhanced_carousel.php\n\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?> 