<?php
/**
 * Student Login Portal
 * Authenticates student using registered Email & Date of Birth
 */
require_once __DIR__ . '/config/db.php';

// If student is already logged in, redirect to dashboard
if (is_student_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';
$dob = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $dob   = trim($_POST['dob'] ?? '');

        if (empty($email) || empty($dob)) {
            $error = 'Please enter both your registered Email and Date of Birth.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $student = $stmt->fetch();

            if ($student && $student['dob'] === $dob) {
                // Authentication successful
                $_SESSION['student_id']   = $student['id'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['role']         = 'student';

                set_flash('success', 'Welcome back, ' . htmlspecialchars($student['full_name']) . '!');
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid Email address or Date of Birth. Please check your credentials.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login</title>
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
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="register.php" class="nav-link">Register</a></li>
                <li><a href="login.php" class="nav-link active">Student Login</a></li>
                <li><a href="admin/login.php" class="btn btn-sm btn-outline-primary">Admin Portal</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container" style="max-width: 480px;">
            
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span><?= htmlspecialchars($error) ?></span>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header" style="text-align: center; display: block;">
                    <div style="font-size: 2.2rem; margin-bottom: 8px;">🔑</div>
                    <h1 class="card-title" style="font-size: 1.4rem;">Student Portal Login</h1>
                    <p class="card-subtitle">Sign in using your registered Email & Date of Birth</p>
                </div>

                <div class="card-body">
                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="form-group">
                            <label for="email" class="form-label">Registered Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                   placeholder="student@example.com" 
                                   value="<?= htmlspecialchars($email) ?>" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" name="dob" id="dob" class="form-control" 
                                   value="<?= htmlspecialchars($dob) ?>" required>
                            <small class="form-help">Enter your exact date of birth as submitted during registration.</small>
                        </div>

                        <div style="margin-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                Access Student Dashboard →
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer" style="flex-direction: column; gap: 8px; text-align: center;">
                    <p style="font-size: 0.88rem; color: var(--text-secondary);">
                        Not registered yet? <a href="register.php" style="color: var(--primary); font-weight: 600;">Enroll Here</a>
                    </p>
                    <p style="font-size: 0.82rem; color: var(--text-muted);">
                        Administrator? <a href="admin/login.php" style="color: var(--secondary); font-weight: 600;">Admin Sign In</a>
                    </p>
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
