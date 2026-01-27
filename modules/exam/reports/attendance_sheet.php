<?php
session_start();
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

requireLogin();

if (!hasRole('admin') && !hasRole('registrar') && !hasRole('faculty')) {
    die("Access Denied");
}

$exam_id = $_GET['exam_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;

if (!$exam_id || !$subject_id) die("Invalid Request");

$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT sa.*, sp.full_name, sp.roll_number, sp.course_applied, c.room_no,
           e.name as exam_name, s.subject_name, s.subject_code, e.session
    FROM seating_allocations sa
    JOIN users u ON sa.student_id = u.id
    JOIN student_profiles sp ON u.id = sp.user_id
    JOIN classrooms c ON sa.room_id = c.id
    JOIN exams e ON sa.exam_id = e.id
    JOIN subjects s ON sa.subject_id = s.id
    WHERE sa.exam_id = ? AND sa.subject_id = ?
    ORDER BY c.room_no, sa.seat_no
");
$stmt->execute([$exam_id, $subject_id]);
$records = $stmt->fetchAll();

if (empty($records)) die("No records found.");

// Group by Room
$rooms = [];
foreach ($records as $r) {
    $rooms[$r['room_no']][] = $r;
}

$meta = $records[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Sheet</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .page-break { page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        .header { text-align: center; margin-bottom: 20px; }
        .meta-table { width: 100%; margin-bottom: 20px; border: none; }
        .meta-table td { border: none; padding: 5px; }
        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" style="padding: 10px; margin-bottom: 20px;">Print Attendance Sheets</button>

    <?php foreach ($rooms as $room_no => $students): ?>
        <div class="page-break">
            <div class="header">
                <h2>INDIAN UNIVERSITY ERP</h2>
                <h3>EXAMINATION ATTENDANCE SHEET</h3>
            </div>

            <table class="meta-table">
                <tr>
                    <td><strong>Exam:</strong> <?= htmlspecialchars($meta['exam_name']) ?> (<?= htmlspecialchars($meta['session']) ?>)</td>
                    <td><strong>Subject:</strong> <?= htmlspecialchars($meta['subject_code']) ?> - <?= htmlspecialchars($meta['subject_name']) ?></td>
                </tr>
                <tr>
                    <td><strong>Date:</strong> ______________________</td>
                    <td><strong>Room No:</strong> <?= htmlspecialchars($room_no) ?></td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <th width="50">S.No</th>
                        <th width="120">Roll Number</th>
                        <th>Student Name</th>
                        <th width="80">Seat No</th>
                        <th width="150">Answer Sheet No.</th>
                        <th width="150">Signature</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; foreach ($students as $stu): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($stu['roll_number']) ?></td>
                        <td><?= htmlspecialchars($stu['full_name']) ?></td>
                        <td><?= htmlspecialchars($stu['seat_no']) ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 50px;">
                <div style="float: left;">
                    <strong>Total Present: _______</strong><br><br>
                    <strong>Total Absent: _______</strong>
                </div>
                <div style="float: right; text-align: center;">
                    __________________________<br>
                    Invigilator Signature
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>
