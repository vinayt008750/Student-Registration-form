<?php
/**
 * Logout Handler
 * Clears session for both students and admins
 */
require_once __DIR__ . '/config/db.php';

$wasAdmin = is_admin_logged_in();

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Restart session just to set flash message
session_start();
set_flash('info', 'You have been logged out successfully.');

if ($wasAdmin) {
    header('Location: admin/login.php');
} else {
    header('Location: login.php');
}
exit;
