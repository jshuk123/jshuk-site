-- Category Metadata and Featured Stories Tables
-- This script creates the necessary tables for enhanced category functionality

-- Create category_meta table for SEO and content management
CREATE TABLE IF NOT EXISTS category_meta (
  category_id INT PRIMARY KEY,
  short_description TEXT,
  banner_image VARCHAR(255),
  seo_title VARCHAR(255),
  seo_description TEXT,
  faq_content TEXT,
  featured_story_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES business_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create featured_stories table for category-specific content
CREATE TABLE IF NOT EXISTS featured_stories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT,
  content TEXT,
  image_path VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES business_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
ALTER TABLE category_meta ADD INDEX IF NOT EXISTS idx_category_id (category_id);
ALTER TABLE featured_stories ADD INDEX IF NOT EXISTS idx_category_active (category_id, is_active);
ALTER TABLE featured_stories ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Insert some sample featured stories for existing categories
INSERT IGNORE INTO featured_stories (category_id, title, excerpt, content, is_active) VALUES
(1, 'The Rise of Kosher Dining in Manchester', 'Discover how local Jewish restaurants are thriving and serving authentic kosher cuisine to our growing community.', 'Our community has seen an incredible growth in kosher dining options over the past few years. From traditional delis to modern fusion restaurants, Manchester\'s Jewish food scene is flourishing. Local chefs are combining traditional recipes with contemporary techniques, creating unique dining experiences that honor our heritage while embracing innovation.', 1),
(2, 'Supporting Local Jewish Retail', 'Meet the entrepreneurs behind our community\'s favorite shops and discover their stories of success.', 'Local Jewish retailers are the backbone of our community, providing everything from traditional clothing to modern electronics. These business owners understand our values and needs, offering personalized service that you won\'t find in large chain stores.', 1),
(3, 'Professional Services You Can Trust', 'Building trust through community connections and exceptional service delivery.', 'When it comes to professional services, our community values trust, reliability, and shared cultural understanding. Local Jewish professionals offer these qualities in abundance, serving our community with dedication and expertise.', 1);

-- Update existing categories with basic metadata
INSERT IGNORE INTO category_meta (category_id, short_description, seo_title, seo_description) VALUES
(1, 'Find trusted kosher restaurants, cafes, bakeries, and food-related businesses in your area.', 'Kosher Food & Beverage Businesses in Manchester | JShuk', 'Discover the best kosher restaurants, cafes, and food businesses in Manchester. Browse trusted local establishments and read real reviews on JShuk.'),
(2, 'Explore local Jewish retail shops and boutiques offering quality products and personalized service.', 'Jewish Retail & Shopping in Manchester | JShuk', 'Find local Jewish retail businesses in Manchester. From clothing to electronics, discover trusted shops with community values.'),
(3, 'Connect with trusted Jewish professionals for all your service needs.', 'Jewish Professional Services in Manchester | JShuk', 'Find reliable Jewish professionals in Manchester. From legal to financial services, connect with trusted community experts.'),
(4, 'Discover unique handcrafted items and artisanal products from local Jewish artisans.', 'Jewish Crafts & Handmade in Manchester | JShuk', 'Explore beautiful handcrafted items from local Jewish artisans in Manchester. Unique gifts and artisanal products.'),
(5, 'Find beauty salons, spa services, and wellness businesses that understand your lifestyle.', 'Jewish Health & Beauty in Manchester | JShuk', 'Discover health and beauty services in Manchester that cater to Jewish lifestyle needs. Trusted professionals and quality care.'),
(6, 'Connect with tutors, coaches, and educational services for all ages and subjects.', 'Jewish Education & Training in Manchester | JShuk', 'Find Jewish tutors and educational services in Manchester. From GCSE prep to bar/bat mitzvah training, expert guidance available.'),
(7, 'Get tech services and digital solutions from trusted local professionals.', 'Jewish Technology Services in Manchester | JShuk', 'Find reliable Jewish tech professionals in Manchester. Web development, IT support, and digital services you can trust.'),
(8, 'Find trusted home services including cleaning, maintenance, and improvements.', 'Jewish Home Services in Manchester | JShuk', 'Discover reliable Jewish home service professionals in Manchester. From cleaning to renovations, quality work guaranteed.');

-- Add FAQ content for each category
UPDATE category_meta SET faq_content = 'Q: How do I know these restaurants are truly kosher?\nA: All listed businesses are verified by our community standards. We work with local rabbinical authorities to ensure proper certification.\n\nQ: Do these restaurants offer delivery?\nA: Many do! Check individual business profiles for delivery options and contact them directly.\n\nQ: Are there vegetarian/vegan kosher options?\nA: Yes, many kosher restaurants offer excellent vegetarian and vegan dishes that maintain strict kosher standards.' WHERE category_id = 1;

UPDATE category_meta SET faq_content = 'Q: Do these shops offer online ordering?\nA: Many local retailers now offer online shopping and delivery. Check individual business profiles for details.\n\nQ: Can I find traditional Jewish clothing?\nA: Yes, several local shops specialize in traditional Jewish clothing and accessories.\n\nQ: Do these businesses ship internationally?\nA: Some do offer international shipping. Contact individual businesses for specific shipping policies.' WHERE category_id = 2;

UPDATE category_meta SET faq_content = 'Q: How do I know these professionals are trustworthy?\nA: All listed professionals are community-verified and come with recommendations from satisfied clients.\n\nQ: Do they offer free consultations?\nA: Many professionals offer initial consultations. Contact them directly to discuss their consultation policies.\n\nQ: Are they experienced with Jewish community needs?\nA: Yes, these professionals understand Jewish cultural and religious requirements.' WHERE category_id = 3; 