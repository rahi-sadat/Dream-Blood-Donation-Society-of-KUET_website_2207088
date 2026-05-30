CREATE DATABASE IF NOT EXISTS dream_blood_donation
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE dream_blood_donation;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    district VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
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
