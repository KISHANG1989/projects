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
$generated_plan = [];

// Handle Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_seating'])) {
    $exam_id = $_POST['exam_id'];
    $subject_id = $_POST['subject_id'];

    // Clear existing for this exam/subject
    $pdo->prepare("DELETE FROM seating_allocations WHERE exam_id = ? AND subject_id = ?")->execute([$exam_id, $subject_id]);

    // Fetch Eligible Students (Approved Applications + Course Match)
    // Actually, usually it's just Course Match for mandatory exams, or Applications for optional.
    // Let's use Applications if exists, else all students in course.
    // For now, assuming Applications are mandatory.

    $stmt = $pdo->prepare("
        SELECT ea.student_id
        FROM exam_applications ea
        WHERE ea.exam_id = ? AND ea.status = 'Approved'
    ");
    $stmt->execute([$exam_id]);
    $student_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // If no applications, fallback to all students in course?
    // Let's stick to Approved Applications as the filter for seating.

    if (empty($student_ids)) {
        $message = "No approved students found for this exam.";
    } else {
        // Fetch Available Rooms
        $rooms = $pdo->query("SELECT * FROM classrooms ORDER BY capacity DESC")->fetchAll();

        $current_student_idx = 0;
        $allocations = [];

        foreach ($rooms as $room) {
            if ($current_student_idx >= count($student_ids)) break;

            $capacity = $room['capacity'];
            // Fill half capacity to prevent cheating? Or full? Let's do full for simplicity, or user choice.
            // Let's just fill sequentially.

            for ($i = 1; $i <= $capacity; $i++) {
                if ($current_student_idx >= count($student_ids)) break;

                $s_id = $student_ids[$current_student_idx];
                $seat_no = "R" . $room['room_no'] . "-S" . str_pad($i, 2, '0', STR_PAD_LEFT);

                $allocations[] = [
                    'exam_id' => $exam_id,
                    'subject_id' => $subject_id,
                    'room_id' => $room['id'],
                    'student_id' => $s_id,
                    'seat_no' => $seat_no
                ];

                $current_student_idx++;
            }
        }

        if ($current_student_idx < count($student_ids)) {
            $message = "Warning: Not enough room capacity! " . (count($student_ids) - $current_student_idx) . " students unseated.";
        } else {
            $message = "Seating plan generated successfully!";
        }

        // Batch Insert
        $sql = "INSERT INTO seating_allocations (exam_id, subject_id, room_id, student_id, seat_no) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        foreach ($allocations as $row) {
            $stmt->execute([$row['exam_id'], $row['subject_id'], $row['room_id'], $row['student_id'], $row['seat_no']]);
        }
    }
}

// Fetch Exams and Subjects
$exams = $pdo->query("SELECT * FROM exams WHERE status IN ('Upcoming', 'Ongoing')")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();

// View Plan
if (isset($_GET['view_exam']) && isset($_GET['view_subject'])) {
    $stmt = $pdo->prepare("
        SELECT sa.*, sp.full_name, sp.roll_number, c.room_no, c.floor_no, b.name as building
        FROM seating_allocations sa
        JOIN users u ON sa.student_id = u.id
        JOIN student_profiles sp ON u.id = sp.user_id
        JOIN classrooms c ON sa.room_id = c.id
        JOIN buildings b ON c.building_id = b.id
        WHERE sa.exam_id = ? AND sa.subject_id = ?
        ORDER BY c.room_no, sa.seat_no
    ");
    $stmt->execute([$_GET['view_exam'], $_GET['view_subject']]);
    $generated_plan = $stmt->fetchAll();
}

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2>Seating Plan Generator</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-5">
                    <label>Exam Event</label>
                    <select name="exam_id" class="form-select" required>
                        <?php foreach ($exams as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label>Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_code']) ?> - <?= htmlspecialchars($s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" name="generate_seating" class="btn btn-primary w-100">Auto Generate</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_POST['generate_seating']) || (isset($_GET['view_exam']))): ?>
        <?php
            // Reuse ID from post or get
            $v_exam = $_POST['exam_id'] ?? $_GET['view_exam'];
            $v_sub = $_POST['subject_id'] ?? $_GET['view_subject'];
            // If not already fetched above
            if (empty($generated_plan)) {
                 $stmt = $pdo->prepare("
                    SELECT sa.*, sp.full_name, sp.roll_number, c.room_no, c.floor_no, b.name as building
                    FROM seating_allocations sa
                    JOIN users u ON sa.student_id = u.id
                    JOIN student_profiles sp ON u.id = sp.user_id
                    JOIN classrooms c ON sa.room_id = c.id
                    JOIN buildings b ON c.building_id = b.id
                    WHERE sa.exam_id = ? AND sa.subject_id = ?
                    ORDER BY c.room_no, sa.seat_no
                ");
                $stmt->execute([$v_exam, $v_sub]);
                $generated_plan = $stmt->fetchAll();
            }
        ?>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <span>Generated Seating Plan</span>
                <div>
                     <a href="reports/desk_chits.php?exam_id=<?= $v_exam ?>&subject_id=<?= $v_sub ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print"></i> Desk Chits
                    </a>
                    <a href="reports/attendance_sheet.php?exam_id=<?= $v_exam ?>&subject_id=<?= $v_sub ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-list"></i> Attendance Sheet
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($generated_plan)): ?>
                    <p class="text-center text-muted">No seating plan generated yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Seat No</th>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($generated_plan as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['room_no']) ?> (<?= htmlspecialchars($row['building']) ?>)</td>
                                    <td><strong><?= htmlspecialchars($row['seat_no']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['roll_number']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
