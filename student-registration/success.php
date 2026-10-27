<?php
/**
 * Student Registration Success & Printable Slip
 */
require_once __DIR__ . '/config/db.php';

$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$studentId && !empty($_SESSION['new_student_id'])) {
    $studentId = $_SESSION['new_student_id'];
}

if (!$studentId) {
    header('Location: register.php');
    exit;
}

// Fetch student record
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student record not found.');
    header('Location: register.php');
    exit;
}

// Check if student photo exists in uploads
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
    <title>Registration Confirmation - <?= htmlspecialchars($student['full_name']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Top Navigation (hidden on print) -->
    <nav class="navbar no-print">
        <div class="container nav-wrapper">
            <a href="index.php" class="nav-brand">
                <div class="brand-icon">🎓</div>
                <span>EduEnroll Portal</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="register.php" class="nav-link">Register Another</a></li>
                <li><a href="login.php" class="nav-link">Student Login</a></li>
                <li><a href="admin/login.php" class="btn btn-sm btn-outline-primary">Admin Portal</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container container-sm">
            
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> no-print">
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Action Toolbar (hidden on print) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;" class="no-print">
                <a href="index.php" class="btn btn-secondary btn-sm">← Back to Home</a>
                <div style="display: flex; gap: 10px;">
                    <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Print Slip</button>
                    <a href="login.php" class="btn btn-outline-primary btn-sm">🔑 Access Dashboard</a>
                </div>
            </div>

            <!-- Official Registration Card / Slip -->
            <div class="reg-card">
                <div class="reg-card-header">
                    <h2>NATIONAL INSTITUTE OF TECHNOLOGY & SCIENCE</h2>
                    <p>Official Student Admission & Course Registration Slip</p>
                    <div class="reg-badge">Registration ID: #STU-<?= str_pad($student['id'], 5, '0', STR_PAD_LEFT) ?></div>
                </div>

                <div class="reg-details-grid">
                    <div class="reg-photo-wrap">
                        <?php if ($photoPath): ?>
                            <img src="<?= htmlspecialchars($photoPath) ?>" alt="Student Photo">
                        <?php else: ?>
                            <div class="reg-photo-fallback">
                                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
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
                                <td class="detail-label">Enrolled Course</td>
                                <td class="detail-value"><span class="badge badge-primary"><?= htmlspecialchars($student['course']) ?></span></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Current Semester</td>
                                <td class="detail-value"><span class="badge badge-success"><?= htmlspecialchars($student['semester']) ?></span></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Email Address</td>
                                <td class="detail-value"><?= htmlspecialchars($student['email']) ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Contact Phone</td>
                                <td class="detail-value"><?= htmlspecialchars($student['phone']) ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Residential Address</td>
                                <td class="detail-value"><?= nl2br(htmlspecialchars($student['address'])) ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">Registered On</td>
                                <td class="detail-value"><?= date('d M Y, h:i A', strtotime($student['created_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div style="padding: 20px 30px; background: #fafafa; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-end;">
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        <p>Status: <strong style="color: var(--success);">Verified & Registered</strong></p>
                        <p>Issued by Registrar Office</p>
                    </div>
                    <div style="text-align: center; border-top: 1px dashed #64748b; padding-top: 4px; min-width: 140px;">
                        <span style="font-size: 0.78rem; color: var(--text-secondary);">Authorized Signatory</span>
                    </div>
                </div>
            </div>

            <!-- Quick Guidance Notes (hidden on print) -->
            <div style="margin-top: 24px; padding: 18px; background: #ffffff; border-radius: var(--radius-md); border: 1px solid var(--border-color);" class="no-print">
                <h4 style="margin-bottom: 8px; color: var(--text-primary); font-size: 0.95rem;">📌 Important Instructions:</h4>
                <ul style="font-size: 0.88rem; color: var(--text-secondary); margin-left: 20px; line-height: 1.6;">
                    <li>Keep this registration slip for your records and identity verification during class orientation.</li>
                    <li>You can log in anytime using your registered email: <strong><?= htmlspecialchars($student['email']) ?></strong> and your Date of Birth.</li>
                    <li>If you need to make any corrections, log in to your student dashboard or contact the administrative office.</li>
                </ul>
            </div>
        </div>
    </main>

    <!-- Footer (hidden on print) -->
    <footer class="footer no-print">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Student Registration System. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/validation.js"></script>
</body>
</html>
