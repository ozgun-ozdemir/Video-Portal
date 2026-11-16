CREATE DATABASE IF NOT EXISTS video_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE video_portal;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL  -- long enough for password_hash()
);

CREATE TABLE IF NOT EXISTS video (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    INDEX idx_is_deleted (is_deleted)
);
