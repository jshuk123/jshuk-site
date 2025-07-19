<?php
/**
 * Employer Dashboard Setup Script
 * 
 * This script sets up the database tables and initial data for the Employer's Dashboard
 * features including company profiles and job applications.
 */

require_once '../config/config.php';

echo "🚀 Setting up Employer's Dashboard...\n\n";

try {
    // Read and execute the SQL file
    $sql_file = '../sql/create_employer_dashboard.sql';
    
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
        'company_profiles',
        'job_applications', 
        'application_status_history'
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
    
    // Check if recruitment table has company_profile_id column
    echo "🔍 Checking recruitment table structure...\n";
    
    $stmt = $pdo->query("DESCRIBE recruitment");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('company_profile_id', $columns)) {
        echo "✅ recruitment table has company_profile_id column\n";
    } else {
        echo "❌ recruitment table missing company_profile_id column\n";
    }
    
    // Create sample data for testing (optional)
    echo "\n🎯 Creating sample data for testing...\n";
    
    // Check if we have any users to create sample company profiles for
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM users LIMIT 3");
    $sample_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sample_users)) {
        foreach ($sample_users as $user) {
            // Check if user already has a company profile
            $stmt = $pdo->prepare("SELECT id FROM company_profiles WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            if (!$stmt->fetch()) {
                // Create sample company profile
                $company_name = $user['first_name'] . "'s Company";
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $company_name)));
                
                $stmt = $pdo->prepare("
                    INSERT INTO company_profiles (
                        user_id, company_name, slug, description, about_us, industry,
                        website, company_size, founded_year, location, contact_email
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $user['id'],
                    $company_name,
                    $slug,
                    "A sample company created for testing purposes.",
                    "This is a sample company profile created during the setup process. You can edit this information in your company profile dashboard.",
                    "Technology",
                    "https://example.com",
                    "1-10",
                    2024,
                    "London, UK",
                    $user['first_name'] . "@example.com"
                ]);
                
                echo "✅ Created sample company profile for " . $user['first_name'] . "\n";
            }
        }
    }
    
    // Create sample job applications if we have jobs and users
    echo "\n🎯 Creating sample job applications...\n";
    
    $stmt = $pdo->query("SELECT id FROM recruitment LIMIT 5");
    $sample_jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->query("SELECT id FROM users LIMIT 5");
    $sample_applicants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($sample_jobs) && !empty($sample_applicants)) {
        $application_count = 0;
        
        foreach ($sample_jobs as $job_id) {
            // Create 1-3 applications per job
            $num_applications = rand(1, 3);
            
            for ($i = 0; $i < $num_applications; $i++) {
                $applicant_id = $sample_applicants[array_rand($sample_applicants)];
                $statuses = ['pending', 'reviewed', 'shortlisted'];
                $status = $statuses[array_rand($statuses)];
                
                // Check if application already exists
                $stmt = $pdo->prepare("SELECT id FROM job_applications WHERE job_id = ? AND applicant_id = ?");
                $stmt->execute([$job_id, $applicant_id]);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO job_applications (job_id, applicant_id, cover_letter, status)
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    $cover_letter = "Dear Hiring Manager,\n\nI am writing to express my interest in this position. I believe my skills and experience make me a strong candidate for this role.\n\nThank you for considering my application.\n\nBest regards,\nSample Applicant";
                    
                    $stmt->execute([$job_id, $applicant_id, $cover_letter, $status]);
                    $application_count++;
                }
            }
        }
        
        echo "✅ Created $application_count sample job applications\n";
    }
    
    echo "\n🎉 Employer's Dashboard setup completed successfully!\n\n";
    
    echo "📋 Next Steps:\n";
    echo "   1. Visit /users/company_profile.php to set up your company profile\n";
    echo "   2. Visit /users/manage_jobs.php to manage your job postings\n";
    echo "   3. Visit /users/applications.php to view job applications\n";
    echo "   4. Test the public company profile at /company-profile.php?slug=your-company-slug\n\n";
    
    echo "🔗 Useful URLs:\n";
    echo "   • Company Profile Management: /users/company_profile.php\n";
    echo "   • Job Posting Management: /users/manage_jobs.php\n";
    echo "   • Application Management: /users/applications.php\n";
    echo "   • Public Company Profile: /company-profile.php?slug=[your-slug]\n\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 