<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!hasRole('admin') && !hasRole('registrar')) {
    header("Location: ../../index.php");
    exit();
}

$pdo = getDBConnection();
$message = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = $_POST['app_id'];
    $status = $_POST['update_status'];

    $stmt = $pdo->prepare("UPDATE exam_applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $app_id]);
    $message = "Application status updated.";
}

// Fetch Pending Applications
$stmt = $pdo->prepare("
    SELECT ea.*, u.username, sp.full_name, sp.roll_number, e.name as exam_name
    FROM exam_applications ea
    JOIN users u ON ea.student_id = u.id
    JOIN student_profiles sp ON u.id = sp.user_id
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.status = 'Pending'
    ORDER BY ea.applied_at ASC
");
$stmt->execute();
$applications = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2>Manage Exam Applications</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <p class="text-muted text-center py-4">No pending applications.</p>
            <?php else: ?>
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Roll No</th>
                            <th>Exam Applied</th>
                            <th>Applied Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['full_name']) ?></td>
                            <td><?= htmlspecialchars($app['roll_number']) ?></td>
                            <td><?= htmlspecialchars($app['exam_name']) ?></td>
                            <td><?= date('d M Y H:i', strtotime($app['applied_at'])) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                    <button type="submit" name="update_status" value="Approved" class="btn btn-sm btn-success">Approve</button>
                                    <button type="submit" name="update_status" value="Rejected" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
