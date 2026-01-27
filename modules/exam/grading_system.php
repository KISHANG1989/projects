<?php
// Grading System Logic

function calculateGrade($total_marks) {
    if ($total_marks >= 90) return ['O', 10];
    if ($total_marks >= 80) return ['A+', 9];
    if ($total_marks >= 70) return ['A', 8];
    if ($total_marks >= 60) return ['B+', 7];
    if ($total_marks >= 50) return ['B', 6];
    if ($total_marks >= 40) return ['P', 5]; // Pass
    return ['F', 0]; // Fail
}

function calculateSGPA($student_id, $exam_id, $pdo) {
    // Fetch marks and credits
    $sql = "
        SELECT sm.grade_point, s.credits
        FROM student_marks sm
        JOIN subjects s ON sm.subject_id = s.id
        WHERE sm.student_id = ? AND sm.exam_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $exam_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_credits = 0;
    $total_points = 0;
    $has_fail = false;

    foreach ($records as $r) {
        if ($r['grade_point'] == 0) $has_fail = true;
        $total_credits += $r['credits'];
        $total_points += ($r['grade_point'] * $r['credits']);
    }

    if ($total_credits == 0) return [0, 0, 'Fail'];

    $sgpa = round($total_points / $total_credits, 2);
    $status = $has_fail ? 'Fail' : 'Pass';

    return [$sgpa, $total_credits, $status];
}
?>
