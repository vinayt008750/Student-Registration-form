<?php
/**
 * Student Registration Form & Processing
 */
require_once __DIR__ . '/config/db.php';

$errors = [];
$formData = [
    'full_name' => '',
    'dob' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'course' => '',
    'semester' => ''
];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Security verification failed. Please refresh and try again.';
    }

    // 2. Extract and Sanitize Form Inputs
    $formData['full_name'] = sanitize($_POST['full_name'] ?? '');
    $formData['dob']       = sanitize($_POST['dob'] ?? '');
    $formData['email']     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $formData['phone']     = sanitize($_POST['phone'] ?? '');
    $formData['address']   = sanitize($_POST['address'] ?? '');
    $formData['course']    = sanitize($_POST['course'] ?? '');
    $formData['semester']  = sanitize($_POST['semester'] ?? '');

    // 3. Validation Logic
    if (empty($formData['full_name']) || strlen($formData['full_name']) < 3) {
        $errors['full_name'] = 'Full Name must be at least 3 characters long.';
    } elseif (!preg_match("/^[a-zA-Z\s\.\'-]+$/", $formData['full_name'])) {
        $errors['full_name'] = 'Full Name should contain only letters, dots, and spaces.';
    }

    if (empty($formData['dob'])) {
        $errors['dob'] = 'Date of Birth is required.';
    } else {
        $dobDate = DateTime::createFromFormat('Y-m-d', $formData['dob']);
        $today = new DateTime();
        if (!$dobDate || $dobDate > $today) {
            $errors['dob'] = 'Date of birth cannot be in the future.';
        } else {
            $age = $today->diff($dobDate)->y;
            if ($age < 14 || $age > 100) {
                $errors['dob'] = 'Student must be at least 14 years old.';
            }
        }
    }

    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email address is required.';
    } else {
        // Check uniqueness of email
        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'This email address is already registered. Please log in or use another email.';
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
        $errors['course'] = 'Please select a valid course from the list.';
    }

    if (empty($formData['semester']) || !in_array($formData['semester'], $semesters)) {
        $errors['semester'] = 'Please select a valid semester.';
    }

    // Photo Validation (Optional)
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

    // 4. Save to Database if no errors
    if (empty($errors)) {
        try {
            $insertSql = "INSERT INTO students (full_name, dob, email, phone, address, course, semester) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                $formData['full_name'],
                $formData['dob'],
                $formData['email'],
                $formData['phone'],
                $formData['address'],
                $formData['course'],
                $formData['semester']
            ]);

            $newStudentId = $pdo->lastInsertId();

            // Handle Photo upload if provided
            if ($hasPhoto) {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $ext = strtolower($ext) === 'jpeg' ? 'jpg' : strtolower($ext);
                $destFileName = "student_" . $newStudentId . "." . $ext;
                $destPath = __DIR__ . '/uploads/' . $destFileName;
                
                // Remove existing photo if any
                array_map('unlink', glob(__DIR__ . "/uploads/student_{$newStudentId}.*"));
                move_uploaded_file($_FILES['photo']['tmp_name'], $destPath);
            }

            // Set session indicator and redirect to success page
            $_SESSION['new_student_id'] = $newStudentId;
            set_flash('success', 'Student registration completed successfully!');
            header("Location: success.php?id=" . $newStudentId);
            exit;

        } catch (PDOException $e) {
            $errors['general'] = 'Registration failed due to a database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Student Registration</title>
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
                <li><a href="register.php" class="nav-link active">Register</a></li>
                <li><a href="login.php" class="nav-link">Student Login</a></li>
                <li><a href="admin/login.php" class="btn btn-sm btn-outline-primary">Admin Portal</a></li>
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
                        <h1 class="card-title">Student Registration Form</h1>
                        <p class="card-subtitle">Please enter accurate details to complete your course enrollment.</p>
                    </div>
                </div>

                <div class="card-body">
                    <form id="studentForm" action="register.php" method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <!-- Student Photo (Optional) -->
                        <div class="form-group col-full">
                            <label class="form-label">Passport Photo (Optional)</label>
                            <div class="avatar-upload-wrap">
                                <div class="avatar-preview" id="photoPreview">
                                    <span style="font-size: 2rem; color: #94a3b8;">👤</span>
                                </div>
                                <div style="flex: 1;">
                                    <input type="file" name="photo" id="photoInput" class="form-control" accept="image/png, image/jpeg, image/webp">
                                    <small class="form-help">Accepted formats: JPG, PNG, WEBP. Max size: 2MB.</small>
                                    <?php if (!empty($errors['photo'])): ?>
                                        <div class="invalid-feedback" style="display:block;"><?= htmlspecialchars($errors['photo']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <!-- Full Name -->
                            <div class="form-group col-full">
                                <label for="full_name" class="form-label">
                                    Full Name <span class="req">*</span>
                                </label>
                                <input type="text" name="full_name" id="full_name" 
                                       class="form-control <?= !empty($errors['full_name']) ? 'is-invalid' : '' ?>" 
                                       placeholder="e.g. Vinay Kumar Sharma"
                                       value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                                <?php if (!empty($errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['full_name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Date of Birth -->
                            <div class="form-group">
                                <label for="dob" class="form-label">
                                    Date of Birth <span class="req">*</span>
                                </label>
                                <input type="date" name="dob" id="dob" 
                                       class="form-control <?= !empty($errors['dob']) ? 'is-invalid' : '' ?>" 
                                       max="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($formData['dob']) ?>" required>
                                <?php if (!empty($errors['dob'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['dob']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Contact Phone -->
                            <div class="form-group">
                                <label for="phone" class="form-label">
                                    Contact Phone <span class="req">*</span>
                                </label>
                                <input type="tel" name="phone" id="phone" 
                                       class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       placeholder="e.g. 9876543210"
                                       value="<?= htmlspecialchars($formData['phone']) ?>" required>
                                <?php if (!empty($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Email Address -->
                            <div class="form-group col-full">
                                <label for="email" class="form-label">
                                    Email Address <span class="req">*</span>
                                </label>
                                <input type="email" name="email" id="email" 
                                       class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                                       placeholder="e.g. student@example.com"
                                       value="<?= htmlspecialchars($formData['email']) ?>" required>
                                <small class="form-help">This will be your username to log in to the Student Portal.</small>
                                <?php if (!empty($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Course Selection -->
                            <div class="form-group">
                                <label for="course" class="form-label">
                                    Selected Course <span class="req">*</span>
                                </label>
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

                            <!-- Semester Selection -->
                            <div class="form-group">
                                <label for="semester" class="form-label">
                                    Current Semester <span class="req">*</span>
                                </label>
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

                            <!-- Residential Address -->
                            <div class="form-group col-full">
                                <label for="address" class="form-label">
                                    Residential Address <span class="req">*</span>
                                </label>
                                <textarea name="address" id="address" class="form-control <?= !empty($errors['address']) ? 'is-invalid' : '' ?>" 
                                          placeholder="Enter permanent or correspondence address with city and pin code" rows="3" required><?= htmlspecialchars($formData['address']) ?></textarea>
                                <?php if (!empty($errors['address'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['address']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="margin-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                Submit Registration 🚀
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer" style="justify-content: center;">
                    <p style="font-size: 0.9rem; color: var(--text-secondary);">
                        Already registered? <a href="login.php" style="color: var(--primary); font-weight: 600;">Student Login Here</a>
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
