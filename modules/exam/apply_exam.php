<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!hasRole('student')) {
    header("Location: ../../index.php");
    exit();
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Handle Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_exam'])) {
    $exam_id = $_POST['exam_id'];

    // Check if already applied
    $check = $pdo->prepare("SELECT id FROM exam_applications WHERE student_id = ? AND exam_id = ?");
    $check->execute([$user_id, $exam_id]);

    if ($check->fetch()) {
        $message = "You have already applied for this exam.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO exam_applications (student_id, exam_id, status) VALUES (?, ?, 'Pending')");
            $stmt->execute([$user_id, $exam_id]);
            $message = "Application submitted successfully! Please wait for approval.";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Profile
$profile = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$profile->execute([$user_id]);
$student = $profile->fetch();

// Fetch Available Exams
$available_exams = [];
if ($student) {
    // Only upcoming exams for student's course/sem
    $stmt = $pdo->prepare("
        SELECT e.*
        FROM exams e
        WHERE e.program_name = ?
        AND e.semester = ?
        AND e.status = 'Upcoming'
        AND e.id NOT IN (SELECT exam_id FROM exam_applications WHERE student_id = ?)
    ");
    $stmt->execute([$student['course_applied'], $student['current_semester'], $user_id]);
    $available_exams = $stmt->fetchAll();
}

// Fetch Applied Exams
$applied_exams = $pdo->prepare("
    SELECT ea.*, e.name, e.session, e.type
    FROM exam_applications ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.student_id = ?
    ORDER BY ea.applied_at DESC
");
$applied_exams->execute([$user_id]);
$applications = $applied_exams->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2>Examination Application</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Apply New -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">Apply for New Exam</div>
                <div class="card-body">
                    <?php if (empty($available_exams)): ?>
                        <p class="text-muted">No exams available for application at this time.</p>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Select Exam</label>
                                <select name="exam_id" class="form-select" required>
                                    <?php foreach ($available_exams as $exam): ?>
                                        <option value="<?= $exam['id'] ?>">
                                            <?= htmlspecialchars($exam['name']) ?> (<?= htmlspecialchars($exam['type']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" required id="agree">
                                <label class="form-check-label small" for="agree">
                                    I hereby declare that I fulfill the eligibility criteria for the selected exam.
                                </label>
                            </div>
                            <button type="submit" name="apply_exam" class="btn btn-success">Submit Application</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">Application History</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?= htmlspecialchars($app['name']) ?></td>
                                <td><?= htmlspecialchars($app['type']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $app['status'] == 'Approved' ? 'success' : ($app['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= htmlspecialchars($app['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($applications)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No applications found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
