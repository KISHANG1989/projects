<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || (!hasRole('admin') && !hasRole('registrar'))) {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['exam_id'])) {
    header("Location: manage_exams.php");
    exit();
}

$pdo = getDBConnection();
$exam_id = $_GET['exam_id'];
$message = '';

// Fetch Exam Details
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) die("Exam not found");

// Handle Timetable Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_timetable'])) {
    try {
        $pdo->beginTransaction();

        // Clear existing entries for this exam (simplest approach for update)
        // Alternatively, use UPSERT or check individually.
        // For now, we iterate through posted data.

        if (isset($_POST['schedule'])) {
            foreach ($_POST['schedule'] as $subject_id => $data) {
                if (!empty($data['date']) && !empty($data['start']) && !empty($data['end'])) {
                    // Check if entry exists
                    $check = $pdo->prepare("SELECT id FROM exam_timetable WHERE exam_id = ? AND subject_id = ?");
                    $check->execute([$exam_id, $subject_id]);
                    if ($check->fetch()) {
                        $upd = $pdo->prepare("UPDATE exam_timetable SET exam_date = ?, start_time = ?, end_time = ? WHERE exam_id = ? AND subject_id = ?");
                        $upd->execute([$data['date'], $data['start'], $data['end'], $exam_id, $subject_id]);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO exam_timetable (exam_id, subject_id, exam_date, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
                        $ins->execute([$exam_id, $subject_id, $data['date'], $data['start'], $data['end']]);
                    }
                }
            }
        }

        $pdo->commit();
        $message = "Timetable updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch Subjects for this Exam (Program + Semester)
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE program_name = ? AND semester = ?");
$stmt->execute([$exam['program_name'], $exam['semester']]);
$subjects = $stmt->fetchAll();

// Fetch Existing Timetable
$stmt = $pdo->prepare("SELECT * FROM exam_timetable WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$timetable_entries = [];
foreach ($stmt->fetchAll() as $row) {
    $timetable_entries[$row['subject_id']] = $row;
}

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="manage_exams.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left"></i> Back to Exams</a>
            <h2 class="mb-0">Manage Timetable</h2>
            <p class="text-muted"><?= htmlspecialchars($exam['name']) ?> (<?= htmlspecialchars($exam['program_name']) ?> - Sem <?= htmlspecialchars($exam['semester']) ?>)</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Exam Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $sub):
                            $existing = $timetable_entries[$sub['id']] ?? null;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($sub['subject_code']) ?></td>
                            <td><?= htmlspecialchars($sub['subject_name']) ?></td>
                            <td>
                                <input type="date" name="schedule[<?= $sub['id'] ?>][date]" class="form-control"
                                    value="<?= $existing ? $existing['exam_date'] : '' ?>">
                            </td>
                            <td>
                                <input type="time" name="schedule[<?= $sub['id'] ?>][start]" class="form-control"
                                    value="<?= $existing ? $existing['start_time'] : '' ?>">
                            </td>
                            <td>
                                <input type="time" name="schedule[<?= $sub['id'] ?>][end]" class="form-control"
                                    value="<?= $existing ? $existing['end_time'] : '' ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subjects)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No subjects found for this program/semester. Please add subjects first.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($subjects)): ?>
                <div class="mt-3 text-end">
                    <button type="submit" name="save_timetable" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Timetable
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
