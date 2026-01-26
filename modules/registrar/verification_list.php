<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
if (!hasRole('registrar') && !hasRole('admin')) {
    redirect('/');
}

require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$stmt = $pdo->query("SELECT sp.*, u.username FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.enrollment_status = 'Pending'");
$students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold">Verification Queue</h2>
        <p class="text-muted">Review and approve pending student applications.</p>
    </div>
    <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo count($students); ?> Pending</span>
</div>

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
                            <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary shadow-sm">
                                <i class="fas fa-eye me-1"></i> View & Verify
                            </a>
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
