<?php
session_start();
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

requireLogin();

// Only Admin or Registrar or Faculty
if (!hasRole('admin') && !hasRole('registrar') && !hasRole('faculty')) {
    die("Access Denied");
}

$exam_id = $_GET['exam_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;

if (!$exam_id || !$subject_id) die("Invalid Request");

$pdo = getDBConnection();

// Fetch Seating Plan
$stmt = $pdo->prepare("
    SELECT sa.*, sp.full_name, sp.roll_number, c.room_no, c.floor_no, b.name as building,
           e.name as exam_name, s.subject_name, s.subject_code, s.semester
    FROM seating_allocations sa
    JOIN users u ON sa.student_id = u.id
    JOIN student_profiles sp ON u.id = sp.user_id
    JOIN classrooms c ON sa.room_id = c.id
    JOIN buildings b ON c.building_id = b.id
    JOIN exams e ON sa.exam_id = e.id
    JOIN subjects s ON sa.subject_id = s.id
    WHERE sa.exam_id = ? AND sa.subject_id = ?
    ORDER BY c.room_no, sa.seat_no
");
$stmt->execute([$exam_id, $subject_id]);
$records = $stmt->fetchAll();

if (empty($records)) die("No records found.");

// Group by Room for better printing
$rooms = [];
foreach ($records as $r) {
    $rooms[$r['room_no']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Desk Chits</title>
    <style>
        body { font-family: sans-serif; }
        .page-break { page-break-after: always; }
        .desk-chit {
            width: 45%;
            height: 180px;
            float: left;
            border: 2px solid #000;
            margin: 10px;
            padding: 10px;
            box-sizing: border-box;
            position: relative;
        }
        .header { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 10px; border-bottom: 1px solid #ccc; }
        .row { margin-bottom: 5px; }
        .label { font-weight: bold; width: 80px; display: inline-block; }
        .seat-big {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 24px;
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px;
            background: #eee;
        }
        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" style="padding: 10px; margin-bottom: 20px;">Print Desk Chits</button>

    <?php foreach ($rooms as $room_no => $students): ?>
        <div class="page-break">
            <h2 style="text-align: center;">Room: <?= $room_no ?> (<?= count($students) ?> Students)</h2>
            <?php foreach ($students as $stu): ?>
                <div class="desk-chit">
                    <div class="header">
                        <?= htmlspecialchars($stu['exam_name']) ?>
                    </div>
                    <div class="row"><span class="label">Name:</span> <?= htmlspecialchars($stu['full_name']) ?></div>
                    <div class="row"><span class="label">Roll No:</span> <strong><?= htmlspecialchars($stu['roll_number']) ?></strong></div>
                    <div class="row"><span class="label">Subject:</span> <?= htmlspecialchars($stu['subject_code']) ?></div>
                    <div class="row"><span class="label">Sem:</span> <?= htmlspecialchars($stu['semester']) ?></div>
                    <div class="seat-big"><?= htmlspecialchars($stu['seat_no']) ?></div>
                </div>
            <?php endforeach; ?>
            <div style="clear: both;"></div>
        </div>
    <?php endforeach; ?>
</body>
</html>
