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

<div class="row">
    <div class="col-12">
        <h2>Verification Queue</h2>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Nationality</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['nationality']); ?></td>
                    <td><?php echo htmlspecialchars($student['category']); ?></td>
                    <td>
                        <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">View & Verify</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                <tr><td colspan="5" class="text-center">No pending applications.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
