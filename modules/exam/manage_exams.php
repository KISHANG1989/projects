<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id']) || (!hasRole('admin') && !hasRole('registrar'))) {
    header("Location: ../../login.php");
    exit();
}

$pdo = getDBConnection();
$message = '';

// Handle Add Exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exam'])) {
    $name = $_POST['name'];
    $session = $_POST['session'];
    $program = $_POST['program_name'];
    $semester = $_POST['semester'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("INSERT INTO exams (name, session, program_name, semester, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $session, $program, $semester, $status]);
        $message = "Exam created successfully!";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch Exams
$stmt = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC");
$exams = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left"></i> Back to Exam Dashboard</a>
            <h2 class="mb-0">Manage Exam Events</h2>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
            <i class="fas fa-plus"></i> Create New Exam
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Exam Name</th>
                        <th>Session</th>
                        <th>Program</th>
                        <th>Sem</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($exam['name']) ?></strong></td>
                        <td><?= htmlspecialchars($exam['session']) ?></td>
                        <td><?= htmlspecialchars($exam['program_name']) ?></td>
                        <td><?= htmlspecialchars($exam['semester']) ?></td>
                        <td>
                            <?php
                                $badgeClass = 'secondary';
                                if($exam['status'] == 'Ongoing') $badgeClass = 'warning';
                                if($exam['status'] == 'Completed') $badgeClass = 'success';
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($exam['status']) ?></span>
                        </td>
                        <td>
                            <a href="timetable.php?exam_id=<?= $exam['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-calendar-alt"></i> Timetable
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Exam Modal -->
<div class="modal fade" id="addExamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Exam Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Exam Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Dec 2024 End Semester">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Session</label>
                            <input type="text" name="session" class="form-control" value="2024-2025" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option>Upcoming</option>
                                <option>Ongoing</option>
                                <option>Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program</label>
                        <select name="program_name" class="form-select" required>
                            <option value="B.Tech Computer Science">B.Tech Computer Science</option>
                            <option value="B.Sc Physics">B.Sc Physics</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select" required>
                             <?php for($i=1; $i<=8; $i++) echo "<option value='$i'>$i</option>"; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_exam" class="btn btn-primary">Create Exam</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
