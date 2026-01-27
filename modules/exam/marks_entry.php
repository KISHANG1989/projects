<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!hasRole('faculty') && !hasRole('admin')) {
    die("Access Denied. Only faculty can enter marks.");
}

$pdo = getDBConnection();
$message = '';

// Helper to calculate Grade
function calculateGrade($total) {
    if ($total >= 90) return ['O', 10];
    if ($total >= 80) return ['A+', 9];
    if ($total >= 70) return ['A', 8];
    if ($total >= 60) return ['B+', 7];
    if ($total >= 50) return ['B', 6];
    if ($total >= 40) return ['P', 5]; // Pass
    return ['F', 0];
}

// Handle Save Marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $exam_id = $_POST['exam_id'];
    $subject_id = $_POST['subject_id'];

    try {
        $pdo->beginTransaction();

        if (isset($_POST['marks'])) {
            foreach ($_POST['marks'] as $student_id => $data) {
                $internal = (float)($data['internal'] ?? 0);
                $external = (float)($data['external'] ?? 0);
                $total = $internal + $external;
                list($grade, $point) = calculateGrade($total);

                // Check exist
                $stmt = $pdo->prepare("SELECT id FROM student_marks WHERE student_id = ? AND exam_id = ? AND subject_id = ?");
                $stmt->execute([$student_id, $exam_id, $subject_id]);

                if ($stmt->fetch()) {
                    $upd = $pdo->prepare("UPDATE student_marks SET internal_marks=?, external_marks=?, total_marks=?, grade=?, grade_point=? WHERE student_id=? AND exam_id=? AND subject_id=?");
                    $upd->execute([$internal, $external, $total, $grade, $point, $student_id, $exam_id, $subject_id]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO student_marks (student_id, exam_id, subject_id, internal_marks, external_marks, total_marks, grade, grade_point) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$student_id, $exam_id, $subject_id, $internal, $external, $total, $grade, $point]);
                }
            }
        }

        $pdo->commit();
        $message = "Marks saved successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error saving marks: " . $e->getMessage();
    }
}

// Fetch Exams
$exams = $pdo->query("SELECT * FROM exams WHERE status IN ('Ongoing', 'Completed')")->fetchAll();

// Fetch Subjects
// Simplification: Fetch all subjects. In real app, filter by Department.
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();

// If Exam and Subject selected, fetch students
$selected_exam = null;
$selected_subject = null;
$students = [];

if (isset($_GET['exam_id']) && isset($_GET['subject_id'])) {
    $exam_id = $_GET['exam_id'];
    $subject_id = $_GET['subject_id'];

    // Get Exam Details for Filter
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $selected_exam = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $selected_subject = $stmt->fetch();

    if ($selected_exam && $selected_subject) {
        // Fetch Students matching Program & Semester
        // Also Left Join marks if already entered
        $sql = "
            SELECT sp.user_id, sp.full_name, sp.roll_number, sm.internal_marks, sm.external_marks
            FROM student_profiles sp
            JOIN users u ON sp.user_id = u.id
            LEFT JOIN student_marks sm ON sm.student_id = u.id AND sm.exam_id = ? AND sm.subject_id = ?
            WHERE sp.course_applied = ?
            -- AND sp.current_semester = ? (Optional: Allow backlogs if implemented)
            ORDER BY sp.roll_number
        ";
        // Note: For strictness we could filter by semester, but let's allow faculty to see all students in that program
        // who *might* take this subject. But usually subject is tied to sem.
        // Let's filter by Semester of the SUBJECT.

        $stmt = $pdo->prepare("
            SELECT sp.user_id, sp.full_name, sp.roll_number, sm.internal_marks, sm.external_marks
            FROM student_profiles sp
            JOIN users u ON sp.user_id = u.id
            LEFT JOIN student_marks sm ON sm.student_id = u.id AND sm.exam_id = ? AND sm.subject_id = ?
            WHERE sp.course_applied = ? AND sp.current_semester = ?
            ORDER BY sp.roll_number
        ");
        $stmt->execute([$exam_id, $subject_id, $selected_exam['program_name'], $selected_subject['semester']]);
        $students = $stmt->fetchAll();
    }
}

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2>Marks Entry Portal</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Exam</label>
                    <select name="exam_id" class="form-select" required>
                        <option value="">-- Select Exam --</option>
                        <?php foreach ($exams as $ex): ?>
                        <option value="<?= $ex['id'] ?>" <?= (isset($_GET['exam_id']) && $_GET['exam_id'] == $ex['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ex['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Select Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>" <?= (isset($_GET['subject_id']) && $_GET['subject_id'] == $sub['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subject_code']) ?> - <?= htmlspecialchars($sub['subject_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Load Student List</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_exam && $selected_subject): ?>
    <form method="POST">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        <input type="hidden" name="subject_id" value="<?= $subject_id ?>">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">Enter Marks for <?= htmlspecialchars($selected_subject['subject_name']) ?></h5>
                <small class="text-muted"><?= htmlspecialchars($selected_exam['name']) ?></small>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <p class="text-muted">No students found for this Program/Semester.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Roll Number</th>
                                    <th>Student Name</th>
                                    <th width="150">Internal Marks (40)</th>
                                    <th width="150">External Marks (60)</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $stu): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stu['roll_number']) ?></td>
                                    <td><?= htmlspecialchars($stu['full_name']) ?></td>
                                    <td>
                                        <input type="number" step="0.5" max="40" name="marks[<?= $stu['user_id'] ?>][internal]"
                                            class="form-control" value="<?= $stu['internal_marks'] ?? '' ?>">
                                    </td>
                                    <td>
                                        <input type="number" step="0.5" max="60" name="marks[<?= $stu['user_id'] ?>][external]"
                                            class="form-control" value="<?= $stu['external_marks'] ?? '' ?>">
                                    </td>
                                    <td>
                                        <?= ($stu['internal_marks'] ?? 0) + ($stu['external_marks'] ?? 0) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" name="save_marks" class="btn btn-success">
                            <i class="fas fa-save"></i> Save All Marks
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
