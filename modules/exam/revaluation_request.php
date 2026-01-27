<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!hasRole('student')) {
    echo "<div class='alert alert-danger'>Access Denied. Student Only.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$pdo = getDBConnection();
$message = "";

// Handle Request
if (isset($_POST['request_reval'])) {
    $exam_id = $_POST['exam_id'];
    $subject_id = $_POST['subject_id'];
    $original_marks = $_POST['original_marks'];

    // Check if already requested
    $check = $pdo->prepare("SELECT id FROM exam_revaluations WHERE student_id = ? AND exam_id = ? AND subject_id = ?");
    $check->execute([$_SESSION['user_id'], $exam_id, $subject_id]);

    if ($check->fetch()) {
        $message = "You have already applied for revaluation for this subject.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO exam_revaluations (student_id, exam_id, subject_id, original_marks) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $exam_id, $subject_id, $original_marks]);
        $message = "Revaluation requested successfully. Please pay the fee.";
    }
}

// Fetch Results eligible for revaluation (Results Declared)
// Join student_marks with exams where status is declared
$sql = "
    SELECT sm.*, s.subject_name, e.name as exam_name
    FROM student_marks sm
    JOIN exams e ON sm.exam_id = e.id
    JOIN subjects s ON sm.subject_id = s.id
    WHERE sm.student_id = ? AND e.status = 'Results Declared'
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch My Requests
$req_sql = "
    SELECT er.*, s.subject_name, e.name as exam_name
    FROM exam_revaluations er
    JOIN exams e ON er.exam_id = e.id
    JOIN subjects s ON er.subject_id = s.id
    WHERE er.student_id = ?
";
$req_stmt = $pdo->prepare($req_sql);
$req_stmt->execute([$_SESSION['user_id']]);
$requests = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Revaluation Request</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Eligible Subjects -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">Eligible Subjects for Revaluation</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Subject</th>
                        <th>Marks Obtained</th>
                        <th>Grade</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['exam_name']) ?></td>
                            <td><?= htmlspecialchars($r['subject_name']) ?></td>
                            <td><?= htmlspecialchars($r['total_marks']) ?></td>
                            <td><?= htmlspecialchars($r['grade']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="exam_id" value="<?= $r['exam_id'] ?>">
                                    <input type="hidden" name="subject_id" value="<?= $r['subject_id'] ?>">
                                    <input type="hidden" name="original_marks" value="<?= $r['total_marks'] ?>">
                                    <button type="submit" name="request_reval" class="btn btn-sm btn-warning" onclick="return confirm('Request revaluation for <?= htmlspecialchars($r['subject_name']) ?>? Fee: 500 INR')">
                                        Apply Reval
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- My Requests -->
<div class="card">
    <div class="card-header">My Requests Status</div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Original Marks</th>
                    <th>New Marks</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $rq): ?>
                    <tr>
                        <td><?= date('d-M-Y', strtotime($rq['requested_at'])) ?></td>
                        <td><?= htmlspecialchars($rq['subject_name']) ?></td>
                        <td><?= htmlspecialchars($rq['original_marks']) ?></td>
                        <td><?= htmlspecialchars($rq['new_marks'] ?? '-') ?></td>
                        <td>
                            <span class="badge bg-<?= ($rq['status'] == 'Completed') ? 'success' : 'secondary' ?>">
                                <?= htmlspecialchars($rq['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
