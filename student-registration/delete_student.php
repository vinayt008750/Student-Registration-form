<?php
/**
 * Delete Student Record
 * Security: Administrator privilege required + CSRF check
 */
require_once __DIR__ . '/config/db.php';

// Strictly restrict to administrator
require_admin_login('admin/login.php');

$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$csrfToken = $_GET['csrf'] ?? ($_POST['csrf_token'] ?? '');

// Verify CSRF
if (!verify_csrf($csrfToken)) {
    set_flash('error', 'Security token mismatch. Delete action blocked.');
    header('Location: admin/dashboard.php');
    exit;
}

if (!$studentId) {
    set_flash('error', 'Invalid student ID specified.');
    header('Location: admin/dashboard.php');
    exit;
}

try {
    // 1. Fetch student name for the flash message
    $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if ($student) {
        $name = $student['full_name'];

        // 2. Delete database record
        $delStmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $delStmt->execute([$studentId]);

        // 3. Remove uploaded photo if exists
        $photos = glob(__DIR__ . "/uploads/student_{$studentId}.*");
        foreach ($photos as $photoFile) {
            if (is_file($photoFile)) {
                @unlink($photoFile);
            }
        }

        set_flash('success', "Student record for '{$name}' has been deleted successfully.");
    } else {
        set_flash('warning', 'Student record was not found or already deleted.');
    }

} catch (PDOException $e) {
    set_flash('error', 'Database error while deleting student: ' . $e->getMessage());
}

header('Location: admin/dashboard.php');
exit;
