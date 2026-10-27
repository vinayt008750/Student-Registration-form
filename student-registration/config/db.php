<?php
/**
 * Database Connection & Global Configuration
 * Database: student_registration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_registration');
define('DB_CHARSET', 'utf8mb4');

try {
    // 1. First attempt to connect directly to the database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If database does not exist, connect to server and create it automatically
        $serverDsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, $options);
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Re-connect to the created database
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    // Ensure required tables exist (Auto-Schema Migration)
    $createStudentsTable = "CREATE TABLE IF NOT EXISTS `students` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `full_name` VARCHAR(100) NOT NULL,
        `dob` DATE NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `phone` VARCHAR(20) NOT NULL,
        `address` TEXT NOT NULL,
        `course` VARCHAR(100) NOT NULL,
        `semester` VARCHAR(20) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($createStudentsTable);

    $createAdminsTable = "CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($createAdminsTable);

    // Auto-seed default admin if table is empty
    $adminCheck = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ($adminCheck == 0) {
        $defaultAdminUser = 'admin';
        $defaultAdminPass = 'admin123';
        $hashed = password_hash($defaultAdminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password_hash`) VALUES (?, ?)");
        $stmt->execute([$defaultAdminUser, $hashed]);
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()) . 
        "<br><small>Please verify MySQL is running in XAMPP/WAMP and your credentials in <code>config/db.php</code> are correct.</small>");
}

// ==========================================
// Utility & Security Helper Functions
// ==========================================

/**
 * Sanitize user input string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * Set flash message (success, error, warning, info)
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format date for friendly display (e.g. 14 May 2003)
 */
function format_date($dateStr) {
    if (!$dateStr) return 'N/A';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date('d M Y', $timestamp) : htmlspecialchars($dateStr);
}

/**
 * Check if a student is logged in
 */
function is_student_logged_in() {
    return !empty($_SESSION['student_id']);
}

/**
 * Check if an admin is logged in
 */
function is_admin_logged_in() {
    return !empty($_SESSION['admin_id']);
}

/**
 * Require student login or redirect
 */
function require_student_login() {
    if (!is_student_logged_in()) {
        set_flash('error', 'Please log in with your student credentials to access this page.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin login or redirect
 */
function require_admin_login($redirectUrl = 'login.php') {
    if (!is_admin_logged_in()) {
        set_flash('error', 'Access restricted. Please log in as an administrator.');
        header("Location: $redirectUrl");
        exit;
    }
}
