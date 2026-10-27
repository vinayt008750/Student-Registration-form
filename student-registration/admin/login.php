<?php
/**
 * Administrator Login Portal
 */
require_once __DIR__ . '/../config/db.php';

// If admin is already logged in, redirect directly to admin dashboard
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both administrator username and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            $verified = false;

            if ($admin) {
                // Verify standard PHP password_verify
                if (password_verify($password, $admin['password_hash'])) {
                    $verified = true;
                } 
                // Dev/Legacy fallback for direct imported plain or md5 hash
                elseif ($admin['password_hash'] === md5($password) || $admin['password_hash'] === $password) {
                    $verified = true;
                    // Automatically upgrade to secure bcrypt hash
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                    $up->execute([$newHash, $admin['id']]);
                }
            }

            if ($verified) {
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['role']           = 'admin';

                set_flash('success', 'Logged in as Administrator (' . htmlspecialchars($admin['username']) . ').');
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid admin username or password.';
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
    <title>Administrator Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background-color: #0f172a;">

    <!-- Top Navigation -->
    <nav class="navbar" style="background: #1e293b; border-color: #334155;">
        <div class="container nav-wrapper">
            <a href="../index.php" class="nav-brand" style="color: #f8fafc;">
                <div class="brand-icon" style="background: #3b82f6;">🛡️</div>
                <span>Admin Console</span>
            </a>
            <ul class="nav-links">
                <li><a href="../index.php" class="nav-link" style="color: #94a3b8;">Home</a></li>
                <li><a href="../register.php" class="nav-link" style="color: #94a3b8;">Registration</a></li>
                <li><a href="../login.php" class="nav-link" style="color: #94a3b8;">Student Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container" style="max-width: 440px;">
            
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

            <div class="card" style="box-shadow: var(--shadow-xl); border: 1px solid #334155; background: #ffffff;">
                <div class="card-header" style="text-align: center; display: block; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
                    <div style="font-size: 2.2rem; margin-bottom: 6px;">🔐</div>
                    <h1 class="card-title" style="font-size: 1.35rem;">Admin Authorization</h1>
                    <p class="card-subtitle">Secure access for admissions management</p>
                </div>

                <div class="card-body">
                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="form-group">
                            <label for="username" class="form-label">Admin Username</label>
                            <input type="text" name="username" id="username" class="form-control" 
                                   placeholder="e.g. admin" 
                                   value="<?= htmlspecialchars($username) ?>" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="••••••••" required>
                        </div>

                        <!-- Credentials Note -->
                        <div style="margin: 12px 0 20px; padding: 10px 14px; background: #f1f5f9; border-radius: var(--radius-sm); font-size: 0.8rem; color: #475569;">
                            💡 <strong>Default Credentials:</strong><br>
                            Username: <code>admin</code> &bull; Password: <code>admin123</code>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary btn-block btn-lg" style="background: #1e293b; border-color: #0f172a;">
                                Unlock Admin Console 🛡️
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer" style="justify-content: center; background: #f8fafc;">
                    <a href="../login.php" style="font-size: 0.85rem; color: var(--text-secondary); text-decoration: none;">
                        ← Switch to Student Login
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer" style="background: #1e293b; border-color: #334155; color: #94a3b8;">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Student Registration System &bull; Administrative Portal</p>
        </div>
    </footer>

    <script src="../js/validation.js"></script>
</body>
</html>
