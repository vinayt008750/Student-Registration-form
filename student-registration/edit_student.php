<?php
/**
 * Edit Student Details
 * Accessible by: Logged-in Admin (any student) OR Logged-in Student (own profile only)
 */
require_once __DIR__ . '/config/db.php';

$isAdmin = is_admin_logged_in();
$isStudent = is_student_logged_in();

if (!$isAdmin && !$isStudent) {
    set_flash('error', 'Please log in to edit student details.');
    header('Location: login.php');
    exit;
}

// Determine student ID to edit
$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Security Check: If not admin, student can only edit their own profile
if (!$isAdmin) {
    if (!$studentId || $studentId != $_SESSION['student_id']) {
        set_flash('error', 'Unauthorized access. You may only edit your own profile.');
        header('Location: dashboard.php');
        exit;
    }
}

if (!$studentId) {
    set_flash('error', 'Invalid student identifier specified.');
    header($isAdmin ? 'Location: admin/dashboard.php' : 'Location: dashboard.php');
    exit;
}

// Fetch current record
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student record not found.');
    header($isAdmin ? 'Location: admin/dashboard.php' : 'Location: dashboard.php');
    exit;
}

$courses = [
    'B.Tech Computer Science & Engineering',
    'B.Tech Information Technology',
    'BCA (Bachelor of Computer Applications)',
    'MCA (Master of Computer Applications)',
    'B.Sc Data Science & AI',
    'B.Sc Cyber Security',
    'B.Com Information Systems',
    'BBA (Bachelor of Business Administration)',
    'M.Tech Computer Science'
];

$semesters = [
    'Semester 1',
    'Semester 2',
    'Semester 3',
    'Semester 4',
    'Semester 5',
    'Semester 6',
    'Semester 7',
    'Semester 8'
];

$errors = [];
$formData = [
    'full_name' => $student['full_name'],
    'dob'       => $student['dob'],
    'email'     => $student['email'],
    'phone'     => $student['phone'],
    'address'   => $student['address'],
    'course'    => $student['course'],
    'semester'  => $student['semester']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Security verification failed. Please try again.';
    } else {
        $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
        $formData['dob']       = sanitize($_POST['dob'] ?? '');
        $formData['email']     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $formData['phone']     = sanitize($_POST['phone'] ?? '');
        $formData['address']   = sanitize($_POST['address'] ?? '');
        $formData['course']    = sanitize($_POST['course'] ?? '');
        $formData['semester']  = sanitize($_POST['semester'] ?? '');

        // Validations
        if (empty($formData['full_name']) || strlen($formData['full_name']) < 3) {
            $errors['full_name'] = 'Full Name must be at least 3 characters.';
        }

        if (empty($formData['dob'])) {
            $errors['dob'] = 'Date of Birth is required.';
        }

        if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        } else {
            // Check email uniqueness excluding this student's ID
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
            $stmt->execute([$formData['email'], $studentId]);
            if ($stmt->fetch()) {
                $errors['email'] = 'This email address is already in use by another student.';
            }
        }

        $cleanPhone = preg_replace('/[\s-]/', '', $formData['phone']);
        if (empty($formData['phone']) || !preg_match('/^[+]?[0-9]{10,15}$/', $cleanPhone)) {
            $errors['phone'] = 'Please enter a valid 10-15 digit phone number.';
        }

        if (empty($formData['address']) || strlen($formData['address']) < 5) {
            $errors['address'] = 'Residential address must be at least 5 characters.';
        }

        if (empty($formData['course']) || !in_array($formData['course'], $courses)) {
            $errors['course'] = 'Please select a valid course.';
        }

        if (empty($formData['semester']) || !in_array($formData['semester'], $semesters)) {
            $errors['semester'] = 'Please select a valid semester.';
        }

        // Handle Photo upload if provided
        $hasPhoto = !empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK;
        if ($hasPhoto) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $fileMime = mime_content_type($_FILES['photo']['tmp_name']);
            $fileSize = $_FILES['photo']['size'];

            if (!in_array($fileMime, $allowedTypes)) {
                $errors['photo'] = 'Only JPG, PNG, and WEBP image formats are accepted.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $errors['photo'] = 'Image size must be 2MB or less.';
            }
        }

        if (empty($errors)) {
            try {
                $updateSql = "UPDATE students SET 
                              full_name = ?, dob = ?, email = ?, phone = ?, address = ?, course = ?, semester = ? 
                              WHERE id = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([
                    $formData['full_name'],
                    $formData['dob'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['address'],
                    $formData['course'],
                    $formData['semester'],
                    $studentId
                ]);

                // Update photo if uploaded
                if ($hasPhoto) {
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $ext = strtolower($ext) === 'jpeg' ? 'jpg' : strtolower($ext);
                    $destFileName = "student_" . $studentId . "." . $ext;
                    $destPath = __DIR__ . '/uploads/' . $destFileName;
                    
                    array_map('unlink', glob(__DIR__ . "/uploads/student_{$studentId}.*"));
                    move_uploaded_file($_FILES['photo']['tmp_name'], $destPath);
                }

                // If student updated own name, refresh session name
                if ($isStudent && $studentId == $_SESSION['student_id']) {
                    $_SESSION['student_name'] = $formData['full_name'];
                }

                set_flash('success', 'Student record updated successfully!');

                if ($isAdmin) {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;

            } catch (PDOException $e) {
                $errors['general'] = 'Failed to update record: ' . $e->getMessage();
            }
        }
    }
}

// Current photo check
$existingPhoto = null;
$photos = glob(__DIR__ . "/uploads/student_{$studentId}.*");
if (!empty($photos)) {
    $existingPhoto = 'uploads/' . basename($photos[0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - <?= htmlspecialchars($student['full_name']) ?></title>
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
                <?php if ($isAdmin): ?>
                    <li><a href="admin/dashboard.php" class="nav-link">Admin Dashboard</a></li>
                    <li><a href="logout.php" class="btn btn-sm btn-outline-danger">Admin Logout</a></li>
                <?php else: ?>
                    <li><a href="dashboard.php" class="nav-link">My Dashboard</a></li>
                    <li><a href="logout.php" class="btn btn-sm btn-outline-danger">Student Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container container-sm">
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger">
                    <span><?= htmlspecialchars($errors['general']) ?></span>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h1 class="card-title">Edit Student Information</h1>
                        <p class="card-subtitle">Updating Record for Student ID: <strong>#STU-<?= str_pad($student['id'], 5, '0', STR_PAD_LEFT) ?></strong></p>
                    </div>
                    <div>
                        <a href="<?= $isAdmin ? 'admin/dashboard.php' : 'dashboard.php' ?>" class="btn btn-secondary btn-sm">Cancel</a>
                    </div>
                </div>

                <div class="card-body">
                    <form id="studentForm" action="edit_student.php?id=<?= $studentId ?>" method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <!-- Student Photo -->
                        <div class="form-group col-full">
                            <label class="form-label">Student Photo</label>
                            <div class="avatar-upload-wrap">
                                <div class="avatar-preview" id="photoPreview">
                                    <?php if ($existingPhoto): ?>
                                        <img src="<?= htmlspecialchars($existingPhoto) ?>" alt="Current Photo" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span style="font-size: 2rem; color: #94a3b8;">👤</span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <input type="file" name="photo" id="photoInput" class="form-control" accept="image/png, image/jpeg, image/webp">
                                    <small class="form-help">Upload a new photo to replace the existing one (JPG, PNG, WEBP, max 2MB).</small>
                                    <?php if (!empty($errors['photo'])): ?>
                                        <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['photo']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <!-- Full Name -->
                            <div class="form-group col-full">
                                <label for="full_name" class="form-label">Full Name <span class="req">*</span></label>
                                <input type="text" name="full_name" id="full_name" 
                                       class="form-control <?= !empty($errors['full_name']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                                <?php if (!empty($errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['full_name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- DOB -->
                            <div class="form-group">
                                <label for="dob" class="form-label">Date of Birth <span class="req">*</span></label>
                                <input type="date" name="dob" id="dob" 
                                       class="form-control <?= !empty($errors['dob']) ? 'is-invalid' : '' ?>" 
                                       max="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($formData['dob']) ?>" required>
                                <?php if (!empty($errors['dob'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['dob']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Phone -->
                            <div class="form-group">
                                <label for="phone" class="form-label">Contact Phone <span class="req">*</span></label>
                                <input type="tel" name="phone" id="phone" 
                                       class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($formData['phone']) ?>" required>
                                <?php if (!empty($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="form-group col-full">
                                <label for="email" class="form-label">Email Address <span class="req">*</span></label>
                                <input type="email" name="email" id="email" 
                                       class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($formData['email']) ?>" required>
                                <?php if (!empty($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Course -->
                            <div class="form-group">
                                <label for="course" class="form-label">Selected Course <span class="req">*</span></label>
                                <select name="course" id="course" class="form-control <?= !empty($errors['course']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">-- Choose Course --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= htmlspecialchars($c) ?>" <?= $formData['course'] === $c ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errors['course'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['course']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Semester -->
                            <div class="form-group">
                                <label for="semester" class="form-label">Current Semester <span class="req">*</span></label>
                                <select name="semester" id="semester" class="form-control <?= !empty($errors['semester']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">-- Choose Semester --</option>
                                    <?php foreach ($semesters as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>" <?= $formData['semester'] === $s ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errors['semester'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['semester']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Address -->
                            <div class="form-group col-full">
                                <label for="address" class="form-label">Residential Address <span class="req">*</span></label>
                                <textarea name="address" id="address" class="form-control <?= !empty($errors['address']) ? 'is-invalid' : '' ?>" 
                                          rows="3" required><?= htmlspecialchars($formData['address']) ?></textarea>
                                <?php if (!empty($errors['address'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['address']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="margin-top: 24px; display: flex; gap: 12px;">
                            <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                                Save Changes
                            </button>
                            <a href="<?= $isAdmin ? 'admin/dashboard.php' : 'dashboard.php' ?>" class="btn btn-secondary btn-lg">
                                Cancel
                            </a>
                        </div>
                    </form>
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
