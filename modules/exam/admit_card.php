<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!hasRole('student')) {
    die("Access Denied. Only students can view admit cards.");
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Fetch Student Profile
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student profile not found.");
}

// Check for selected exam
if (isset($_GET['exam_id'])) {
    $exam_id = $_GET['exam_id'];

    // Validate Exam Access
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND program_name = ? AND semester = ?");
    $stmt->execute([$exam_id, $student['course_applied'], $student['current_semester']]);
    $exam = $stmt->fetch();

    if (!$exam) die("Invalid Admit Card Request");

    // Fetch Timetable
    $stmt = $pdo->prepare("
        SELECT et.*, s.subject_name, s.subject_code
        FROM exam_timetable et
        JOIN subjects s ON et.subject_id = s.id
        WHERE et.exam_id = ?
        ORDER BY et.exam_date, et.start_time
    ");
    $stmt->execute([$exam_id]);
    $schedule = $stmt->fetchAll();

    // Render Admit Card (Clean HTML for print)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admit Card - <?= htmlspecialchars($student['full_name']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f8f9fa; padding: 20px; }
            .admit-card {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border: 2px solid #000;
                padding: 30px;
                position: relative;
            }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
            .student-info { margin-bottom: 20px; }
            .photo-box {
                width: 120px;
                height: 150px;
                border: 1px solid #ccc;
                position: absolute;
                top: 30px;
                right: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #eee;
            }
            @media print {
                body { background: none; }
                .no-print { display: none; }
                .admit-card { border: 2px solid #000; }
            }
        </style>
    </head>
    <body>
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">Print Admit Card</button>
            <a href="admit_card.php" class="btn btn-secondary">Back to List</a>
        </div>

        <div class="admit-card">
            <div class="photo-box">
                <!-- Placeholder for photo -->
                <span class="text-muted small">Photo</span>
            </div>

            <div class="header">
                <h3>INDIAN UNIVERSITY ERP</h3>
                <h5>ADMIT CARD - <?= strtoupper($exam['status']) ?> EXAMINATION</h5>
                <p class="mb-0"><strong><?= htmlspecialchars($exam['name']) ?></strong> (<?= htmlspecialchars($exam['session']) ?>)</p>
            </div>

            <div class="student-info">
                <div class="row">
                    <div class="col-8">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="150">Name:</th>
                                <td><?= htmlspecialchars($student['full_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Roll Number:</th>
                                <td><strong><?= htmlspecialchars($student['roll_number']) ?></strong></td>
                            </tr>
                            <tr>
                                <th>Course:</th>
                                <td><?= htmlspecialchars($student['course_applied']) ?></td>
                            </tr>
                            <tr>
                                <th>Semester:</th>
                                <td><?= htmlspecialchars($student['current_semester']) ?></td>
                            </tr>
                             <tr>
                                <th>ABC ID:</th>
                                <td><?= htmlspecialchars($student['abc_id']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Invigilator Sign</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedule as $row): ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($row['exam_date'])) ?></td>
                        <td><?= date('H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?></td>
                        <td><?= htmlspecialchars($row['subject_code']) ?></td>
                        <td><?= htmlspecialchars($row['subject_name']) ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="mt-5 pt-3 border-top">
                <div class="row">
                    <div class="col-6">
                        <p><strong>Instructions:</strong></p>
                        <ul class="small">
                            <li>Bring this Admit Card and a valid ID proof.</li>
                            <li>Report 30 minutes before exam time.</li>
                            <li>Electronic gadgets are strictly prohibited.</li>
                        </ul>
                    </div>
                    <div class="col-6 text-end align-self-end">
                        <p><strong>Controller of Examinations</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// List Available Exams
$stmt = $pdo->prepare("SELECT * FROM exams WHERE program_name = ? AND semester = ? AND status IN ('Upcoming', 'Ongoing')");
$stmt->execute([$student['course_applied'], $student['current_semester']]);
$exams = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2>Download Admit Card</h2>
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-body">
            <?php if (empty($exams)): ?>
                <div class="alert alert-warning">
                    No upcoming exams found for your course/semester.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($exams as $exam): ?>
                    <a href="?exam_id=<?= $exam['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($exam['name']) ?></h5>
                            <small><?= htmlspecialchars($exam['session']) ?> | Sem <?= htmlspecialchars($exam['semester']) ?></small>
                        </div>
                        <span class="badge bg-primary rounded-pill">Download</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
