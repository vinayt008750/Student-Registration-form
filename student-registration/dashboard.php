<?php
/**
 * Student Dashboard
 */
require_once __DIR__ . '/config/db.php';
require_student_login();

$studentId = $_SESSION['student_id'];

// Retrieve current student profile
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    // Session exists but record was deleted
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check for photo
$photoPath = null;
$photos = glob(__DIR__ . "/uploads/student_{$studentId}.*");
if (!empty($photos)) {
    $photoPath = 'uploads/' . basename($photos[0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?= htmlspecialchars($student['full_name']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Top Navigation -->
    <nav class="navbar">
        <div class="container nav-wrapper">
            <a href="dashboard.php" class="nav-brand">
                <div class="brand-icon">🎓</div>
                <span>EduEnroll Portal</span>
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                <li><a href="edit_student.php?id=<?= $student['id'] ?>" class="nav-link">Edit Profile</a></li>
                <li><a href="success.php?id=<?= $student['id'] ?>" class="nav-link">Print Slip</a></li>
                <li><a href="logout.php" class="btn btn-sm btn-outline-danger">Log Out</a></li>
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

            <!-- Header Welcome Card -->
            <div class="card" style="background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #ffffff; border: none; padding: 28px 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 72px; height: 72px; border-radius: 50%; overflow: hidden; background: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700; color: #1e3a8a; border: 3px solid rgba(255,255,255,0.4); flex-shrink: 0;">
                        <?php if ($photoPath): ?>
                            <img src="<?= htmlspecialchars($photoPath) ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 style="font-size: 1.7rem; font-weight: 800;"><?= htmlspecialchars($student['full_name']) ?></h1>
                        <p style="opacity: 0.9; font-size: 0.95rem;">Student ID: <strong>#STU-<?= str_pad($student['id'], 5, '0', STR_PAD_LEFT) ?></strong> &bull; <span class="badge badge-success" style="background: rgba(255,255,255,0.25); color: #fff;">Status: Active</span></p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="edit_student.php?id=<?= $student['id'] ?>" class="btn btn-secondary btn-sm" style="background: #ffffff; color: var(--text-primary);">✏️ Edit Profile</a>
                    <a href="success.php?id=<?= $student['id'] ?>" class="btn btn-secondary btn-sm" style="background: #ffffff; color: var(--text-primary);">🖨️ View & Print Slip</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Enrolled Academic & Personal Details</h2>
                    <span class="badge badge-primary"><?= htmlspecialchars($student['course']) ?></span>
                </div>

                <div class="card-body">
                    <table class="detail-table">
                        <tr>
                            <td class="detail-label">Full Name</td>
                            <td class="detail-value"><strong><?= htmlspecialchars($student['full_name']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Date of Birth</td>
                            <td class="detail-value"><?= format_date($student['dob']) ?></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Email Address</td>
                            <td class="detail-value"><?= htmlspecialchars($student['email']) ?></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Contact Number</td>
                            <td class="detail-value"><?= htmlspecialchars($student['phone']) ?></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Selected Course</td>
                            <td class="detail-value"><span class="badge badge-primary"><?= htmlspecialchars($student['course']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Current Semester</td>
                            <td class="detail-value"><span class="badge badge-success"><?= htmlspecialchars($student['semester']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Residential Address</td>
                            <td class="detail-value"><?= nl2br(htmlspecialchars($student['address'])) ?></td>
                        </tr>
                        <tr>
                            <td class="detail-label">Registered Date</td>
                            <td class="detail-value"><?= date('d M Y, h:i A', strtotime($student['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="card-footer" style="justify-content: space-between;">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        To update registered details, click "Edit Profile".
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Log Out</a>
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
