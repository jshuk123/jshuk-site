USE home_business_db;

CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    author_title VARCHAR(255),
    content TEXT NOT NULL,
    image_path VARCHAR(255),
    rating INT NOT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
); 