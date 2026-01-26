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

// Handle Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_duty'])) {
    $exam_id = $_POST['exam_id'];
    $room_id = $_POST['room_id'];
    $date = $_POST['date'];
    $slot = $_POST['slot'];
    $staff1 = $_POST['staff1'];
    $staff2 = $_POST['staff2'];

    // Validate constraint: 1 Teaching, 1 Non-Teaching?
    // Or check clashing?
    // User request: "duty should not clash (one teaching and one non-teaching staff per class two invigilator)"

    // Check clash
    $clash = $pdo->prepare("SELECT * FROM invigilation_duties WHERE duty_date = ? AND time_slot = ? AND user_id IN (?, ?)");
    $clash->execute([$date, $slot, $staff1, $staff2]);
    if ($clash->fetch()) {
        $message = "Error: One of the selected staff is already assigned to another duty at this time.";
    } else {
        // Insert
        try {
            $stmt = $pdo->prepare("INSERT INTO invigilation_duties (exam_id, room_id, user_id, duty_date, time_slot) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$exam_id, $room_id, $staff1, $date, $slot]);
            $stmt->execute([$exam_id, $room_id, $staff2, $date, $slot]);
            $message = "Invigilators assigned successfully.";
        } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
    }
}

// Fetch Data
$exams = $pdo->query("SELECT * FROM exams WHERE status IN ('Upcoming', 'Ongoing')")->fetchAll();
$rooms = $pdo->query("SELECT * FROM classrooms")->fetchAll();
// Fetch Staff sorted by type
$staff = $pdo->query("SELECT * FROM users WHERE role IN ('faculty', 'staff') ORDER BY staff_type, username")->fetchAll();
$teaching_staff = array_filter($staff, fn($u) => $u['staff_type'] == 'Teaching' || $u['role'] == 'faculty');
$non_teaching_staff = array_filter($staff, fn($u) => $u['staff_type'] == 'Non-Teaching' || $u['role'] == 'staff');

// Fetch Duties
$duties = $pdo->query("
    SELECT id.*, e.name as exam, c.room_no, u.username, u.staff_type
    FROM invigilation_duties id
    JOIN exams e ON id.exam_id = e.id
    JOIN classrooms c ON id.room_id = c.id
    JOIN users u ON id.user_id = u.id
    ORDER BY id.duty_date, id.time_slot, c.room_no
")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2>Invigilation Duty Management</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">Assign Duty</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Exam</label>
                            <select name="exam_id" class="form-select" required>
                                <?php foreach ($exams as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Room</label>
                            <select name="room_id" class="form-select" required>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>">Room <?= htmlspecialchars($r['room_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label>Date</label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label>Time Slot</label>
                                <select name="slot" class="form-select">
                                    <option>09:00 - 12:00</option>
                                    <option>13:00 - 16:00</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Invigilator 1 (Teaching)</label>
                            <select name="staff1" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($teaching_staff as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['username']) ?> (<?= htmlspecialchars($s['staff_type'] ?? 'Faculty') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Invigilator 2 (Non-Teaching)</label>
                            <select name="staff2" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($non_teaching_staff as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['username']) ?> (<?= htmlspecialchars($s['staff_type'] ?? 'Staff') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="assign_duty" class="btn btn-primary w-100">Assign</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">Duty Roster</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Exam</th>
                                    <th>Room</th>
                                    <th>Staff</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($duties as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['duty_date']) ?></td>
                                    <td><?= htmlspecialchars($d['time_slot']) ?></td>
                                    <td><?= htmlspecialchars($d['exam']) ?></td>
                                    <td><?= htmlspecialchars($d['room_no']) ?></td>
                                    <td><?= htmlspecialchars($d['username']) ?> <small class="text-muted">(<?= htmlspecialchars($d['staff_type']) ?>)</small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
