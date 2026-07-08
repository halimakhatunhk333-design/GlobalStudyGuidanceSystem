-- =========================================================================
-- GSGS (Global Study Guidance System) - Relational Database Schema
-- Target Module Coverage: All 10 Core Application Features
-- =========================================================================

-- 1 & 2. REGISTRATION & LOGIN (User Management)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, -- Securely hashed passwords
    phone_number VARCHAR(20),
    preferred_country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. PUBLIC DASHBOARD (System Metrics / Dynamic Announcements)
CREATE TABLE IF NOT EXISTS dashboard_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. UNIVERSITY SEARCH
CREATE TABLE IF NOT EXISTS universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100),
    global_ranking INT,
    website_url VARCHAR(255),
    INDEX idx_country (country) -- Indexing for faster search performance
);

-- 5. COURSE FINDER (Linked to Universities via Foreign Key)
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    degree_level ENUM('Bachelors', 'Masters', 'PhD', 'Diploma') NOT NULL,
    duration_years DECIMAL(3,1) NOT NULL,
    tuition_fee_usd INT NOT NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    INDEX idx_degree (degree_level)
);

-- 6. SOP-BUILDER (Linked to Users via Foreign Key)
CREATE TABLE IF NOT EXISTS sop_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_title VARCHAR(150) DEFAULT 'My Statement of Purpose',
    academic_background TEXT,
    career_goals TEXT,
    motivation_text TEXT,
    completed_sop TEXT, -- Final generated text
    last_saved TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. SCHOLARSHIP HUB
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scholarship_name VARCHAR(255) NOT NULL,
    offering_country VARCHAR(100) NOT NULL,
    coverage_type ENUM('Full Funding', 'Partial Tuition', 'Stipend Only') NOT NULL,
    amount_usd INT DEFAULT 0,
    eligibility_criteria TEXT,
    application_deadline DATE
);

-- 8. FINANCIAL GUIDANCE (Cost of Living estimates per country)
CREATE TABLE IF NOT EXISTS financial_guidance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(100) UNIQUE NOT NULL,
    avg_accommodation_monthly INT NOT NULL,
    avg_food_monthly INT NOT NULL,
    avg_transport_monthly INT NOT NULL,
    currency_code VARCHAR(10) DEFAULT 'USD'
);

-- 9. VISA GUIDE
CREATE TABLE IF NOT EXISTS visa_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_country VARCHAR(100) UNIQUE NOT NULL,
    visa_type VARCHAR(100) DEFAULT 'Student Visa',
    required_documents TEXT NOT NULL,
    application_fee_usd INT,
    processing_time_weeks INT,
    financial_proof_required TEXT
);

-- 10. CAREER SUPPORT (Job sectors and part-time rules per country)
CREATE TABLE IF NOT EXISTS career_support (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(100) NOT NULL,
    part_time_hours_per_week INT DEFAULT 20,
    post_study_work_visa_months INT DEFAULT 0,
    top_in_demand_industries VARCHAR(255),
    FOREIGN KEY (country_name) REFERENCES financial_guidance(country_name) ON DELETE CASCADE
);
