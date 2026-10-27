<?php
/**
 * Administrator Dashboard
 * Manage, search, filter, edit, delete students, and export records
 */
require_once __DIR__ . '/../config/db.php';
require_admin_login('login.php');

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=registered_students_' . date('Y-m-d_His') . '.csv');

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // CSV Header row
    fputcsv($output, ['ID', 'Full Name', 'Date of Birth', 'Email', 'Phone', 'Address', 'Course', 'Semester', 'Registration Date']);

    $stmt = $pdo->query("SELECT id, full_name, dob, email, phone, address, course, semester, created_at FROM students ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['full_name'],
            $row['dob'],
            $row['email'],
            $row['phone'],
            $row['address'],
            $row['course'],
            $row['semester'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Summary Statistics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$totalCourses  = $pdo->query("SELECT COUNT(DISTINCT course) FROM students")->fetchColumn() ?: 0;
$todayRegistrations = $pdo->query("SELECT COUNT(*) FROM students WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

// Fetch all distinct courses for filter dropdown
$courseList = $pdo->query("SELECT DISTINCT course FROM students ORDER BY course ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch all students
$search = sanitize($_GET['q'] ?? '');
$courseFilter = sanitize($_GET['course'] ?? '');

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($courseFilter)) {
    $sql .= " AND course = ?";
    $params[] = $courseFilter;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Management Console</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <!-- Top Navigation -->
    <nav class="navbar">
        <div class="container-fluid nav-wrapper">
            <a href="dashboard.php" class="nav-brand">
                <div class="brand-icon">🛡️</div>
                <span>EduEnroll Admin Portal</span>
            </a>
            <ul class="nav-links">
                <li><span class="badge badge-secondary" style="font-size: 0.85rem;">Admin: <?= htmlspecialchars($_SESSION['admin_username']) ?></span></li>
                <li><a href="../register.php" target="_blank" class="btn btn-sm btn-outline-primary">+ New Registration</a></li>
                <li><a href="dashboard.php?export=csv" class="btn btn-sm btn-secondary">📥 Export CSV</a></li>
                <li><a href="../logout.php" class="btn btn-sm btn-outline-danger">Log Out</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                    <button class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Metric Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$totalStudents ?></div>
                        <div class="stat-label">Total Registered Students</div>
                    </div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-icon">📚</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$totalCourses ?></div>
                        <div class="stat-label">Academic Courses</div>
                    </div>
                </div>

                <div class="stat-card stat-purple">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$todayRegistrations ?></div>
                        <div class="stat-label">Registrations Today</div>
                    </div>
                </div>

                <div class="stat-card stat-orange">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= count($students) ?></div>
                        <div class="stat-label">Showing Results</div>
                    </div>
                </div>
            </div>

            <!-- Student Management Card -->
            <div class="card">
                <div class="card-header" style="flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h2 class="card-title">Enrolled Students Database</h2>
                        <p class="card-subtitle">Search, inspect, edit, or remove registered students.</p>
                    </div>

                    <!-- Search & Filter Controls -->
                    <form method="GET" action="dashboard.php" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                        <input type="text" name="q" id="adminSearchInput" class="form-control" 
                               style="width: 240px; padding: 8px 12px; font-size: 0.9rem;" 
                               placeholder="Live search by name, email, phone..." 
                               value="<?= htmlspecialchars($search) ?>">

                        <select name="course" id="adminCourseFilter" class="form-control" style="width: 200px; padding: 8px 12px; font-size: 0.9rem;">
                            <option value="">All Courses</option>
                            <?php foreach ($courseList as $cName): ?>
                                <option value="<?= htmlspecialchars($cName) ?>" <?= $courseFilter === $cName ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                        <?php if (!empty($search) || !empty($courseFilter)): ?>
                            <a href="dashboard.php" class="btn btn-outline-danger btn-sm">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>DOB</th>
                                    <th>Contact Details</th>
                                    <th>Course & Semester</th>
                                    <th>Registered Date</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                            <div style="font-size: 2rem; margin-bottom: 8px;">📂</div>
                                            <strong>No student records found.</strong>
                                            <p style="font-size: 0.85rem; margin-top: 4px;">Try modifying your search filter or add a new student.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s): ?>
                                        <?php 
                                            $photo = glob(__DIR__ . "/../uploads/student_{$s['id']}.*");
                                            $photoUrl = !empty($photo) ? '../uploads/' . basename($photo[0]) : null;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= str_pad($s['id'], 4, '0', STR_PAD_LEFT) ?></strong>
                                            </td>
                                            <td>
                                                <div class="user-info-cell">
                                                    <div class="user-avatar-sm">
                                                        <?php if ($photoUrl): ?>
                                                            <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Avatar">
                                                        <?php else: ?>
                                                            <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                                                        <div style="font-size: 0.78rem; color: var(--text-muted); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($s['address']) ?>">
                                                            <?= htmlspecialchars($s['address']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?= format_date($s['dob']) ?>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($s['email']) ?></div>
                                                <small style="color: var(--text-muted);"><?= htmlspecialchars($s['phone']) ?></small>
                                            </td>
                                            <td class="col-course">
                                                <div><span class="badge badge-primary"><?= htmlspecialchars($s['course']) ?></span></div>
                                                <div style="margin-top: 4px;"><span class="badge badge-secondary"><?= htmlspecialchars($s['semester']) ?></span></div>
                                            </td>
                                            <td>
                                                <?= date('d M Y', strtotime($s['created_at'])) ?>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('h:i A', strtotime($s['created_at'])) ?></div>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a href="../success.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" title="View & Print Slip" target="_blank">
                                                    🖨️ Slip
                                                </a>
                                                <a href="../edit_student.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Student">
                                                    ✏️ Edit
                                                </a>
                                                <a href="../delete_student.php?id=<?= $s['id'] ?>&csrf=<?= csrf_token() ?>" 
                                                   class="btn btn-outline-danger btn-sm" 
                                                   onclick="return confirmDelete('<?= htmlspecialchars(addslashes($s['full_name'])) ?>');" 
                                                   title="Delete Student">
                                                    🗑️
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer" style="justify-content: space-between;">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        Total records: <strong><?= count($students) ?></strong>
                    </span>
                    <div style="display: flex; gap: 8px;">
                        <a href="dashboard.php?export=csv" class="btn btn-secondary btn-sm">📥 Export All to CSV</a>
                        <a href="../register.php" target="_blank" class="btn btn-primary btn-sm">+ Enroll Student</a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <p>&copy; <?= date('Y') ?> Student Registration System &bull; Admin Console</p>
        </div>
    </footer>

    <script src="../js/validation.js"></script>
</body>
</html>
