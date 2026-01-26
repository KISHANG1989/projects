<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
if (!hasRole('registrar') && !hasRole('admin')) {
    redirect('/');
}

require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();

// Fetch Filters
$years = $pdo->query("SELECT DISTINCT strftime('%Y', created_at) as year FROM student_profiles WHERE created_at IS NOT NULL ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
$programs = $pdo->query("SELECT DISTINCT course_applied FROM student_profiles WHERE course_applied IS NOT NULL ORDER BY course_applied")->fetchAll(PDO::FETCH_COLUMN);

// Build Query
$query = "SELECT sp.*, u.username FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE 1=1";
$params = [];

// Filter by Status (Default to Pending if not searching, or keep logic simple: View All or just Pending?)
// The original requirement was "Verification Queue", usually implying Pending.
// However, search might be used to find processed students too.
// Let's stick to the previous behavior: Only Pending, but maybe the user wants to search *anyone*?
// The prompt says "To search and view the documents", implying they might want to see anyone.
// But the page title is "Verification Queue".
// Let's keep "enrollment_status = 'Pending'" for now as per the page intent, unless the user removes that constraint?
// Actually, usually a search overrides the "Pending" filter if you want to find a specific student.
// But let's act safely. The user said "Registrar staff view... To search and view the documents".
// If I can't find a student because they are already Approved, that's annoying.
// But this page is `verification_list.php`.
// Let's keep the Pending filter for now, but allow the user to clear it or maybe add a Status filter?
// Re-reading code: The original query had `WHERE sp.enrollment_status = 'Pending'`.
// I will keep that constraint for the "Queue" concept.

$query .= " AND sp.enrollment_status = 'Pending'";

if (!empty($_GET['year'])) {
    $query .= " AND strftime('%Y', sp.created_at) = ?";
    $params[] = $_GET['year'];
}
if (!empty($_GET['program'])) {
    $query .= " AND sp.course_applied = ?";
    $params[] = $_GET['program'];
}
if (!empty($_GET['search'])) {
    $term = '%' . $_GET['search'] . '%';
    $query .= " AND (u.username LIKE ? OR sp.roll_number LIKE ?)";
    $params[] = $term;
    $params[] = $term;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Verification Queue</h2>
        <p class="text-muted">Review and approve pending student applications.</p>
    </div>
    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo count($students); ?> Pending</span>
</div>

<form method="GET" class="row g-2 mb-4 align-items-center">
    <div class="col-auto">
        <label class="visually-hidden">Year</label>
        <select name="year" class="form-select" onchange="this.form.submit()">
            <option value="">All Years</option>
            <?php foreach($years as $y): ?>
                <option value="<?php echo htmlspecialchars($y); ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : ''; ?>><?php echo htmlspecialchars($y); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
         <label class="visually-hidden">Program</label>
         <select name="program" class="form-select" onchange="this.form.submit()">
            <option value="">All Programs</option>
            <?php foreach($programs as $p): ?>
                <option value="<?php echo htmlspecialchars($p); ?>" <?php echo (isset($_GET['program']) && $_GET['program'] == $p) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search UID / Roll No" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </div>
    </div>
     <div class="col-auto">
        <a href="verification_list.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Name</th>
                        <th>Nationality</th>
                        <th>Category</th>
                        <th>Program</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?php echo htmlspecialchars($student['id']); ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td>
                            <?php if($student['nationality'] === 'International'): ?>
                                <span class="badge bg-info text-dark">International</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Indian</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($student['category']); ?></td>
                        <td><?php echo htmlspecialchars($student['course_applied'] ?? '-'); ?></td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm shadow-sm">
                                <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary" title="View Profile">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                                <a href="view_documents.php?id=<?php echo $student['id']; ?>" class="btn btn-primary" title="Verify Documents">
                                    <i class="fas fa-file-check"></i> Verify Docs
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x mb-3 d-block text-success"></i>All caught up! No pending applications.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
