-- ============================================
-- Timecard - Employee Time In/Out System
-- Import this file in phpMyAdmin (SQL tab)
-- ============================================

CREATE DATABASE IF NOT EXISTS timeclock_db;
USE timeclock_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- plain text password stored here (see README)
    role ENUM('employee', 'admin') NOT NULL DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS time_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    time_in DATETIME NOT NULL,
    time_out DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- NOTE: No user rows are seeded here. Add accounts manually:
-- phpMyAdmin -> timeclock_db -> users -> Insert tab.
-- Fill username, email, password_hash (type the plain password directly),
-- and role ('employee' or 'admin').

-- Example (uncomment and edit to use):
-- INSERT INTO users (username, email, password_hash, role) VALUES
-- ('admin', 'admin@example.com', 'admin123', 'admin'),
-- ('jdoe', 'jdoe@example.com', 'password123', 'employee');
