<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check Login & Role (Admin or Exam Controller - using 'admin' or 'registrar' for now as proxy, or 'faculty'?)
// Let's assume 'admin' or a new role 'exam_controller'. For simplicity, 'admin' and 'registrar' (often share duties).
if (!isset($_SESSION['user_id']) || (!hasRole('admin') && !hasRole('registrar') && !hasRole('faculty'))) {
    header("Location: ../../login.php");
    exit();
}

$pdo = getDBConnection();
$message = '';

// Handle Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $code = $_POST['subject_code'];
    $name = $_POST['subject_name'];
    $program = $_POST['program_name'];
    $semester = $_POST['semester'];
    $credits = $_POST['credits'];
    $type = $_POST['type'];

    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, program_name, semester, credits, type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $program, $semester, $credits, $type]);
        $message = "Subject added successfully!";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
    header("Location: manage_subjects.php");
    exit();
}

// Fetch Subjects
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY program_name, semester, subject_code");
$subjects = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left"></i> Back to Exam Dashboard</a>
            <h2 class="mb-0">Manage Subjects</h2>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="fas fa-plus"></i> Add New Subject
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Program</th>
                            <th>Sem</th>
                            <th>Credits</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $sub): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sub['subject_code']) ?></strong></td>
                            <td><?= htmlspecialchars($sub['subject_name']) ?></td>
                            <td><?= htmlspecialchars($sub['program_name']) ?></td>
                            <td><?= htmlspecialchars($sub['semester']) ?></td>
                            <td><?= htmlspecialchars($sub['credits']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($sub['type']) ?></span></td>
                            <td>
                                <a href="?delete=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subjects)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No subjects found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject Code</label>
                            <input type="text" name="subject_code" class="form-control" required placeholder="e.g. CS101">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Data Structures">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program</label>
                        <select name="program_name" class="form-select" required>
                            <option value="B.Tech Computer Science">B.Tech Computer Science</option>
                            <option value="B.Sc Physics">B.Sc Physics</option>
                            <option value="MBA">MBA</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select" required>
                                <?php for($i=1; $i<=8; $i++) echo "<option value='$i'>$i</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Credits</label>
                            <input type="number" name="credits" class="form-control" value="3" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="Core">Core</option>
                                <option value="Elective">Elective</option>
                                <option value="Practical">Practical</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
