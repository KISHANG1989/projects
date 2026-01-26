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

// Filters
$enroll_status = $_GET['enroll_status'] ?? 'Pending';
$stud_status = $_GET['stud_status'] ?? '';

if (!empty($enroll_status) && $enroll_status !== 'All') {
    $query .= " AND sp.enrollment_status = ?";
    $params[] = $enroll_status;
}

if (!empty($stud_status)) {
    $query .= " AND sp.student_status = ?";
    $params[] = $stud_status;
}

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
    $query .= " AND (u.username LIKE ? OR sp.roll_number LIKE ? OR sp.full_name LIKE ?)";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Student Management</h2>
        <p class="text-muted">Manage applications, admissions, and student statuses.</p>
    </div>
    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo count($students); ?> Records</span>
</div>

<form method="GET" class="row g-2 mb-4 align-items-center">
    <div class="col-auto">
        <select name="enroll_status" class="form-select" onchange="this.form.submit()">
            <option value="All" <?php echo ($enroll_status == 'All') ? 'selected' : ''; ?>>All Applications</option>
            <option value="Pending" <?php echo ($enroll_status == 'Pending') ? 'selected' : ''; ?>>Pending Approval</option>
            <option value="Approved" <?php echo ($enroll_status == 'Approved') ? 'selected' : ''; ?>>Approved</option>
            <option value="Rejected" <?php echo ($enroll_status == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="stud_status" class="form-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Provisional" <?php echo ($stud_status == 'Provisional') ? 'selected' : ''; ?>>Provisional</option>
            <option value="Admitted" <?php echo ($stud_status == 'Admitted') ? 'selected' : ''; ?>>Admitted</option>
            <option value="Active" <?php echo ($stud_status == 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="Deactive" <?php echo ($stud_status == 'Deactive') ? 'selected' : ''; ?>>Deactive</option>
            <option value="Suspended" <?php echo ($stud_status == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
            <option value="Alumni" <?php echo ($stud_status == 'Alumni') ? 'selected' : ''; ?>>Alumni</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="year" class="form-select" onchange="this.form.submit()">
            <option value="">All Years</option>
            <?php foreach($years as $y): ?>
                <option value="<?php echo htmlspecialchars($y); ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : ''; ?>><?php echo htmlspecialchars($y); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
         <select name="program" class="form-select" onchange="this.form.submit()">
            <option value="">All Programs</option>
            <?php foreach($programs as $p): ?>
                <option value="<?php echo htmlspecialchars($p); ?>" <?php echo (isset($_GET['program']) && $_GET['program'] == $p) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Name / UID / Roll No" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
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
                        <th>Name / UID</th>
                        <th>Nationality</th>
                        <th>Program</th>
                        <th>Admission</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?php echo htmlspecialchars($student['id']); ?></td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($student['username']); ?></small>
                        </td>
                        <td>
                            <?php if($student['nationality'] === 'International'): ?>
                                <span class="badge bg-info text-dark">International</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Indian</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($student['course_applied'] ?? '-'); ?>
                            <?php if(($student['admission_mode'] ?? 'Regular') === 'Lateral Entry'): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">Lateral</span>
                            <?php endif; ?>
                        </td>
                        <td>
                             <?php
                                $admBadge = match($student['enrollment_status']) {
                                    'Approved' => 'bg-success',
                                    'Rejected' => 'bg-danger',
                                    default => 'bg-warning text-dark'
                                };
                            ?>
                            <span class="badge <?php echo $admBadge; ?>"><?php echo htmlspecialchars($student['enrollment_status']); ?></span>
                        </td>
                        <td>
                            <?php
                                 $currStatus = $student['student_status'] ?? 'Provisional';
                                 $stBadge = match($currStatus) {
                                     'Active', 'Admitted' => 'bg-success',
                                     'Deactive', 'Suspended' => 'bg-danger',
                                     'Alumni' => 'bg-info',
                                     default => 'bg-light text-dark border'
                                 };
                             ?>
                             <span class="badge <?php echo $stBadge; ?>"><?php echo htmlspecialchars($currStatus); ?></span>
                        </td>
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
