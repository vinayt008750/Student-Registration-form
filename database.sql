-- Database Design for Student Registration System
-- Database: student_registration

CREATE DATABASE IF NOT EXISTS `student_registration` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `student_registration`;

-- --------------------------------------------------------
-- Table 1: students
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `dob` DATE NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `course` VARCHAR(100) NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 2: admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Initial Admin User
-- Default credentials:
-- Username: admin
-- Password: admin123
-- (PHP password_verify() compatible bcrypt hash)
-- --------------------------------------------------------
INSERT INTO `admins` (`id`, `username`, `password_hash`) 
VALUES (1, 'admin', '$2y$10$w8T9h.26yRz7C0Z8L5hXweqP5qYgE7tZ1a6e7tY0uQeW4j6s6k4v6')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- --------------------------------------------------------
-- Sample Students Data for Testing
-- --------------------------------------------------------
INSERT INTO `students` (`id`, `full_name`, `dob`, `email`, `phone`, `address`, `course`, `semester`, `created_at`) VALUES
(1, 'Aarav Sharma', '2003-05-14', 'aarav.sharma@example.com', '9876543210', '42 MG Road, Bengaluru, Karnataka - 560001', 'B.Tech Computer Science', 'Semester 5', NOW()),
(2, 'Ananya Patel', '2004-11-20', 'ananya.patel@example.com', '9812345678', '15 Nehru Nagar, Ahmedabad, Gujarat - 380015', 'B.Sc Data Science & AI', 'Semester 3', NOW()),
(3, 'Rohan Verma', '2002-08-09', 'rohan.verma@example.com', '9723456789', '78 Park Street, Kolkata, West Bengal - 700016', 'BCA (Bachelor of Applications)', 'Semester 6', NOW())
ON DUPLICATE KEY UPDATE `email` = `email`;
