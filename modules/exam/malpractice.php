<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Allow Admin, Faculty (Invigilators)
if (!hasRole('admin') && !hasRole('faculty') && !hasRole('controller')) {
    die("Access Denied");
}

$pdo = getDBConnection();
$message = "";

// Handle New Report
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_ufm'])) {
    $student_id = $_POST['student_id'];
    $exam_id = $_POST['exam_id'];
    $subject_id = $_POST['subject_id'];
    $description = $_POST['description'];

    try {
        $stmt = $pdo->prepare("INSERT INTO exam_malpractice (student_id, exam_id, subject_id, description, reported_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $exam_id, $subject_id, $description, $_SESSION['user_id']]);
        $message = "Malpractice reported successfully. Case ID: " . $pdo->lastInsertId();
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Update Status
if (isset($_POST['update_status'])) {
    $id = $_POST['case_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE exam_malpractice SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    $message = "Case updated.";
}

// Fetch Lists
$exams = $pdo->query("SELECT * FROM exams WHERE status IN ('Ongoing', 'Completed')")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Cases
$cases = $pdo->query("
    SELECT em.*, u.username as student_name, e.name as exam_name, s.subject_name
    FROM exam_malpractice em
    JOIN users u ON em.student_id = u.id
    JOIN exams e ON em.exam_id = e.id
    JOIN subjects s ON em.subject_id = s.id
    ORDER BY em.reported_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Malpractice Management (UFM)</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Report Form -->
<div class="card mb-4">
    <div class="card-header bg-danger text-white">Report New Case</div>
    <div class="card-body">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Student ID/Roll No</label>
                    <input type="number" name="student_id" class="form-control" placeholder="Student User ID" required>
                    <!-- ideally a search dropdown, but using ID for MVP -->
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Event</label>
                    <select name="exam_id" class="form-select" required>
                        <option value="">-- Select Exam --</option>
                        <?php foreach ($exams as $ex): ?>
                            <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description of Incident</label>
                <textarea name="description" class="form-control" rows="3" required placeholder="Describe the malpractice incident..."></textarea>
            </div>
            <button type="submit" name="report_ufm" class="btn btn-danger">Report Malpractice</button>
        </form>
    </div>
</div>

<!-- Cases List -->
<div class="card">
    <div class="card-header">Reported Cases</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Subject</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $c): ?>
                        <tr>
                            <td><?= date('d-M-Y', strtotime($c['reported_at'])) ?></td>
                            <td><?= htmlspecialchars($c['student_name']) ?> (ID: <?= $c['student_id'] ?>)</td>
                            <td><?= htmlspecialchars($c['exam_name']) ?></td>
                            <td><?= htmlspecialchars($c['subject_name']) ?></td>
                            <td><?= htmlspecialchars($c['description']) ?></td>
                            <td>
                                <span class="badge bg-<?= ($c['status'] == 'Punished') ? 'danger' : (($c['status'] == 'Exonerated') ? 'success' : 'warning') ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="case_id" value="<?= $c['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                        <option value="Reported" <?= ($c['status'] == 'Reported') ? 'selected' : '' ?>>Reported</option>
                                        <option value="Under Inquiry" <?= ($c['status'] == 'Under Inquiry') ? 'selected' : '' ?>>Inquiry</option>
                                        <option value="Punished" <?= ($c['status'] == 'Punished') ? 'selected' : '' ?>>Punished</option>
                                        <option value="Exonerated" <?= ($c['status'] == 'Exonerated') ? 'selected' : '' ?>>Exonerated</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
