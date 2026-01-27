<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!hasRole('student')) {
    die("Access Denied. Only students can view results.");
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Fetch Student Profile
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) die("Student profile not found.");

// View Result for Specific Exam
if (isset($_GET['exam_id'])) {
    $exam_id = $_GET['exam_id'];

    // Fetch Exam Info
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    // Fetch Marks
    $stmt = $pdo->prepare("
        SELECT sm.*, s.subject_name, s.subject_code, s.credits
        FROM student_marks sm
        JOIN subjects s ON sm.subject_id = s.id
        WHERE sm.student_id = ? AND sm.exam_id = ?
    ");
    $stmt->execute([$user_id, $exam_id]);
    $results = $stmt->fetchAll();

    // Calculate SGPA
    $total_credits = 0;
    $total_points = 0;
    $has_failed = false;

    foreach ($results as $row) {
        $total_credits += $row['credits'];
        $total_points += ($row['credits'] * $row['grade_point']);
        if ($row['grade'] == 'F') $has_failed = true;
    }

    $sgpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
    $final_result = $has_failed ? 'FAIL' : 'PASS';

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Marksheet - <?= htmlspecialchars($student['full_name']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f8f9fa; padding: 20px; }
            .marksheet {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border: 2px solid #333;
                padding: 40px;
            }
            .university-header {
                text-align: center;
                border-bottom: 2px solid #333;
                margin-bottom: 30px;
                padding-bottom: 20px;
            }
            .logo { font-size: 40px; }
        </style>
    </head>
    <body>
        <div class="text-center mb-4 d-print-none">
            <button onclick="window.print()" class="btn btn-primary">Print Marksheet</button>
            <a href="results.php" class="btn btn-secondary">Back to List</a>
        </div>

        <div class="marksheet">
            <div class="university-header">
                <div class="logo">🏛️</div>
                <h2>INDIAN UNIVERSITY ERP</h2>
                <h4>STATEMENT OF MARKS</h4>
                <p><strong><?= htmlspecialchars($exam['name']) ?> (<?= htmlspecialchars($exam['session']) ?>)</strong></p>
            </div>

            <table class="table table-borderless mb-4">
                <tr>
                    <td><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></td>
                    <td><strong>Roll No:</strong> <?= htmlspecialchars($student['roll_number']) ?></td>
                </tr>
                <tr>
                    <td><strong>Program:</strong> <?= htmlspecialchars($student['course_applied']) ?></td>
                    <td><strong>Semester:</strong> <?= htmlspecialchars($exam['semester']) ?></td>
                </tr>
            </table>

            <table class="table table-bordered text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Credits</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $res): ?>
                    <tr>
                        <td><?= htmlspecialchars($res['subject_code']) ?></td>
                        <td><?= htmlspecialchars($res['subject_name']) ?></td>
                        <td><?= htmlspecialchars($res['credits']) ?></td>
                        <td><strong><?= htmlspecialchars($res['grade']) ?></strong></td>
                        <td><?= htmlspecialchars($res['grade_point']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="5">Result withheld or not declared.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2" class="text-end">Total</th>
                        <th><?= $total_credits ?></th>
                        <th colspan="2">SGPA: <?= $sgpa ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="row mt-4">
                <div class="col-6">
                    <p><strong>Result:</strong> <span class="badge bg-<?= $has_failed ? 'danger' : 'success' ?> fs-6"><?= $final_result ?></span></p>
                </div>
                <div class="col-6 text-end">
                    <br><br>
                    <p><strong>Controller of Examinations</strong></p>
                </div>
            </div>

             <div class="mt-4 text-center text-muted small">
                <p>This is a computer-generated document.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// List Declared Results
// Fetch exams where the student has at least one mark entry
$stmt = $pdo->prepare("
    SELECT DISTINCT e.*
    FROM exams e
    JOIN student_marks sm ON e.id = sm.exam_id
    WHERE sm.student_id = ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$user_id]);
$exams = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2>Examination Results</h2>
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-body">
            <?php if (empty($exams)): ?>
                <div class="alert alert-info">
                    No results declared yet.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($exams as $exam): ?>
                    <a href="?exam_id=<?= $exam['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($exam['name']) ?></h5>
                            <small><?= htmlspecialchars($exam['session']) ?> | Sem <?= htmlspecialchars($exam['semester']) ?></small>
                        </div>
                        <span class="badge bg-success rounded-pill">View Result</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
