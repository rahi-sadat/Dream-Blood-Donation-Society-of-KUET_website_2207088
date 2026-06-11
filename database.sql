CREATE DATABASE IF NOT EXISTS dream_blood_donation
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE dream_blood_donation;

-- To make the first admin, register a normal account, then run:
-- UPDATE users SET role = 'admin' WHERE email = 'your_email@example.com';

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    district VARCHAR(80) NOT NULL,
    is_donor TINYINT(1) NOT NULL DEFAULT 1,
    available_to_donate TINYINT(1) NOT NULL DEFAULT 1,
    last_donation_date DATE NULL,
    is_member TINYINT(1) NOT NULL DEFAULT 0,
    kuet_batch VARCHAR(20) NULL,
    department VARCHAR(120) NULL,
    roll_no VARCHAR(30) NULL,
    member_joined_at DATETIME NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blood_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    blood_bag INT NOT NULL,
    needed_date DATE NOT NULL,
    district VARCHAR(80) NOT NULL,
    hospital VARCHAR(160) NOT NULL,
    address TEXT NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    relation VARCHAR(80) NOT NULL,
    urgency VARCHAR(40) NOT NULL,
    details TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS donation_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    donor_id INT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Interested',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request_donor (request_id, donor_id),
    FOREIGN KEY (request_id) REFERENCES blood_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS gallery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_key VARCHAR(40) NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blood_summaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_label VARCHAR(80) NOT NULL,
    summary_year VARCHAR(10) NOT NULL,
    total_bags VARCHAR(40) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    status_label VARCHAR(40) NOT NULL DEFAULT 'Completed',
    event_date VARCHAR(80) NULL,
    location VARCHAR(160) NULL,
    category VARCHAR(120) NULL,
    badge_text VARCHAR(60) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS campaign_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
