<?php
session_start();
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

// Check Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'registrar') {
    header("Location: ../../../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$pdo = getDBConnection();
$event_id = $_GET['id'];
$message = '';

// Fetch Event Details
$stmt = $pdo->prepare("SELECT * FROM convocation_events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found.");
}

// Handle Issue Degree
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_degree'])) {
    $student_id = $_POST['student_id'];
    $program = $_POST['program'];
    $cgpa = $_POST['cgpa'];
    $division = $_POST['division'];

    // Generate Serial Number (e.g., UNIV/YEAR/ID)
    $serial_no = "DEG/" . date('Y') . "/" . str_pad($student_id, 6, '0', STR_PAD_LEFT);

    try {
        $stmt = $pdo->prepare("INSERT INTO student_degrees (student_id, convocation_id, degree_serial_no, issue_date, program_name, division, cgpa) VALUES (?, ?, ?, DATE('now'), ?, ?, ?)");
        $stmt->execute([$student_id, $event_id, $serial_no, $program, $division, $cgpa]);
        $message = "Degree issued successfully!";
    } catch (PDOException $e) {
        $message = "Error issuing degree: " . $e->getMessage();
    }
}

// Fetch Issued Degrees for this event
$stmt = $pdo->prepare("
    SELECT sd.*, sp.full_name, sp.roll_number
    FROM student_degrees sd
    JOIN users u ON sd.student_id = u.id
    JOIN student_profiles sp ON u.id = sp.user_id
    WHERE sd.convocation_id = ?
");
$stmt->execute([$event_id]);
$issued_degrees = $stmt->fetchAll();
$issued_ids = array_column($issued_degrees, 'student_id');

// Fetch Eligible Students (excluding already issued)
$sql = "
    SELECT u.id, sp.full_name, sp.roll_number, sp.course_applied
    FROM users u
    JOIN student_profiles sp ON u.id = sp.user_id
    WHERE u.role = 'student'
";
// If we have issued degrees, exclude them
if (!empty($issued_ids)) {
    $placeholders = implode(',', array_fill(0, count($issued_ids), '?'));
    $sql .= " AND u.id NOT IN ($placeholders)";
}
$stmt = $pdo->prepare($sql);
$stmt->execute($issued_ids);
$eligible_students = $stmt->fetchAll();

require_once '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left"></i> Back to Events</a>
            <h2 class="mb-0">Manage Degrees: <?= htmlspecialchars($event['title']) ?></h2>
            <p class="text-muted"><?= htmlspecialchars($event['batch_year']) ?> | <?= htmlspecialchars($event['venue']) ?></p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="issued-tab" data-bs-toggle="tab" data-bs-target="#issued" type="button">Issued Degrees</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="issue-new-tab" data-bs-toggle="tab" data-bs-target="#issue-new" type="button">Issue New Degree</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <!-- Issued Degrees Tab -->
        <div class="tab-pane fade show active" id="issued">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Serial No</th>
                                <th>Student Name</th>
                                <th>Roll No</th>
                                <th>Program</th>
                                <th>CGPA</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issued_degrees as $degree): ?>
                            <tr>
                                <td><?= htmlspecialchars($degree['degree_serial_no']) ?></td>
                                <td><?= htmlspecialchars($degree['full_name']) ?></td>
                                <td><?= htmlspecialchars($degree['roll_number']) ?></td>
                                <td><?= htmlspecialchars($degree['program_name']) ?></td>
                                <td><?= htmlspecialchars($degree['cgpa']) ?></td>
                                <td>
                                    <a href="generate_degree.php?id=<?= $degree['id'] ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-print"></i> View Degree
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($issued_degrees)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No degrees issued yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Issue New Degree Tab -->
        <div class="tab-pane fade" id="issue-new">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Roll No</th>
                                <th>Course</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eligible_students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['full_name']) ?></td>
                                <td><?= htmlspecialchars($student['roll_number']) ?></td>
                                <td><?= htmlspecialchars($student['course_applied']) ?></td>
                                <td>
                                    <button class="btn btn-success btn-sm"
                                        onclick="openIssueModal(<?= $student['id'] ?>, '<?= htmlspecialchars($student['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($student['course_applied'], ENT_QUOTES) ?>')">
                                        Issue Degree
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Issue Degree Modal -->
<div class="modal fade" id="issueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Issue Degree</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="modal_student_id">
                    <div class="mb-3">
                        <label class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="modal_student_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program / Course</label>
                        <input type="text" name="program" class="form-control" id="modal_program" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CGPA</label>
                            <input type="number" step="0.01" name="cgpa" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Division</label>
                            <select name="division" class="form-select">
                                <option>First Class with Distinction</option>
                                <option>First Class</option>
                                <option>Second Class</option>
                                <option>Pass Class</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="issue_degree" class="btn btn-success">Confirm Issue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openIssueModal(id, name, course) {
        document.getElementById('modal_student_id').value = id;
        document.getElementById('modal_student_name').value = name;
        document.getElementById('modal_program').value = course;
        new bootstrap.Modal(document.getElementById('issueModal')).show();
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>
