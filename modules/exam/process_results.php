<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../config/db.php';
require_once 'grading_system.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'controller') {
    die("Access Denied");
}

$pdo = getDBConnection();
$message = "";

// Handle Calculation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_results'])) {
    $exam_id = $_POST['exam_id'];

    try {
        $pdo->beginTransaction();

        // Fetch all students who have marks for this exam
        $stmt = $pdo->prepare("SELECT DISTINCT student_id FROM student_marks WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $count = 0;
        foreach ($students as $student_id) {
            $calc = calculateSGPA($student_id, $exam_id, $pdo);
            if ($calc) {
                list($sgpa, $total_credits, $status) = $calc;

                // Update Result
                $check = $pdo->prepare("SELECT id FROM student_exam_results WHERE student_id=? AND exam_id=?");
                $check->execute([$student_id, $exam_id]);
                if ($check->fetch()) {
                    $upd = $pdo->prepare("UPDATE student_exam_results SET sgpa=?, total_credits=?, result_status=?, processed_at=CURRENT_TIMESTAMP WHERE student_id=? AND exam_id=?");
                    $upd->execute([$sgpa, $total_credits, $status, $student_id, $exam_id]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO student_exam_results (student_id, exam_id, sgpa, total_credits, result_status) VALUES (?, ?, ?, ?, ?)");
                    $ins->execute([$student_id, $exam_id, $sgpa, $total_credits, $status]);
                }
                $count++;
            }
        }

        // Update Exam Status
        $upd_exam = $pdo->prepare("UPDATE exams SET status='Results Declared' WHERE id=?");
        $upd_exam->execute([$exam_id]);

        $pdo->commit();
        $message = "Results Processed for $count students.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch Exams
$exams = $pdo->query("SELECT * FROM exams WHERE status != 'Results Declared'")->fetchAll(PDO::FETCH_ASSOC);
$declared_exams = $pdo->query("SELECT * FROM exams WHERE status = 'Results Declared'")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Process Results (SGPA Calculation)</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-dark">Pending Results</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Select Exam to Process</label>
                        <select name="exam_id" class="form-select" required>
                            <option value="">-- Select Exam --</option>
                            <?php foreach ($exams as $ex): ?>
                                <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="process_results" class="btn btn-primary w-100" onclick="return confirm('Are you sure? This will calculate SGPA for all students.')">
                        Calculate & Publish Results
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-success text-white">Declared Results</div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($declared_exams as $dex): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($dex['name']) ?>
                            <span class="badge bg-light text-dark">Published</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
