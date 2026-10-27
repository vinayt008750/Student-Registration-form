<?php
/**
 * Student Registration System - Home / Landing Page
 */
require_once __DIR__ . '/config/db.php';

// Quick aggregate stats if database is accessible
$totalStudents = 0;
$totalCourses = 0;
try {
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
    $totalCourses = $pdo->query("SELECT COUNT(DISTINCT course) FROM students")->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Graceful fallback if fresh install
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Top Navigation -->
    <nav class="navbar">
        <div class="container nav-wrapper">
            <a href="index.php" class="nav-brand">
                <div class="brand-icon">🎓</div>
                <span>EduEnroll Portal</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link active">Home</a></li>
                <li><a href="register.php" class="nav-link">Register</a></li>
                <li><a href="login.php" class="nav-link">Student Login</a></li>
                <li><a href="admin/login.php" class="btn btn-sm btn-outline-primary">Admin Portal</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Hero Section -->
            <section class="hero">
                <span class="hero-badge">Academic Session 2026 – 2027</span>
                <h1>Next-Gen <span>Student Registration</span> System</h1>
                <p>Welcome to the official online student admission and enrollment portal. Register for your undergraduate or postgraduate course with instant digital slips and student portal access.</p>
                <div class="hero-cta">
                    <a href="register.php" class="btn btn-primary btn-lg">📝 Register as New Student</a>
                    <a href="login.php" class="btn btn-secondary btn-lg">🔑 Student Login (Email & DOB)</a>
                    <a href="admin/login.php" class="btn btn-outline-primary btn-lg">🛡️ Administrator Access</a>
                </div>
            </section>

            <!-- Quick Live Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$totalStudents ?></div>
                        <div class="stat-label">Students Registered</div>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">📚</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$totalCourses ?></div>
                        <div class="stat-label">Active Courses</div>
                    </div>
                </div>
                <div class="stat-card stat-purple">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-info">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Digital & Paperless</div>
                    </div>
                </div>
                <div class="stat-card stat-orange">
                    <div class="stat-icon">🔒</div>
                    <div class="stat-info">
                        <div class="stat-number">Secure</div>
                        <div class="stat-label">Admin Managed</div>
                    </div>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📋</div>
                    <h3>Fast Online Registration</h3>
                    <p>Fill out your personal and academic details in our streamlined registration form with instant client and server validations.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🖨️</div>
                    <h3>Instant Digital Slip</h3>
                    <p>Download or print your official registration confirmation receipt immediately upon submission with a dedicated print layout.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🎓</div>
                    <h3>Student Dashboard</h3>
                    <p>Log in using your registered Email and Date of Birth to review your profile, verify semester status, and update your details.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛠️</div>
                    <h3>Admin Management</h3>
                    <p>Comprehensive admin dashboard with live search, course filters, student edit/delete privileges, and CSV data export.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Student Registration System. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/validation.js"></script>
</body>
</html>
