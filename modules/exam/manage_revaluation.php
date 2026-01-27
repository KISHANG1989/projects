<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!hasRole('admin') && !hasRole('controller')) {
    die("Access Denied");
}

$pdo = getDBConnection();
$message = "";

// Handle Update
if (isset($_POST['update_reval'])) {
    $id = $_POST['request_id'];
    $new_marks = $_POST['new_marks'];
    $status = $_POST['status'];

    try {
        $pdo->beginTransaction();

        // Update Revaluation Record
        $stmt = $pdo->prepare("UPDATE exam_revaluations SET new_marks = ?, status = ? WHERE id = ?");
        $stmt->execute([$new_marks, $status, $id]);

        // If Completed, Update Actual Marks?
        // Usually, revaluation updates the main marks table if changed.
        if ($status == 'Completed' && $new_marks > 0) {
            // Fetch student/exam info
            $info = $pdo->prepare("SELECT student_id, exam_id, subject_id FROM exam_revaluations WHERE id = ?");
            $info->execute([$id]);
            $rec = $info->fetch();

            // Update Student Marks (Assume External Marks change or Total?)
            // Usually revaluation is on external marks.
            // But here we stored total. Let's update total and grade.
            // We need to fetch internal first to keep it? No, just update total for simplicity or fetch.

            // Simplified: Update total_marks in student_marks
            // Recalculate Grade
            require_once 'grading_system.php';
            list($grade, $point) = calculateGrade($new_marks);

            $upd = $pdo->prepare("UPDATE student_marks SET total_marks = ?, grade = ?, grade_point = ? WHERE student_id = ? AND exam_id = ? AND subject_id = ?");
            $upd->execute([$new_marks, $grade, $point, $rec['student_id'], $rec['exam_id'], $rec['subject_id']]);

            // Recalculate SGPA for this student
            $calc = calculateSGPA($rec['student_id'], $rec['exam_id'], $pdo);
            if ($calc) {
                list($sgpa, $total_credits, $res_status) = $calc;
                $upd_res = $pdo->prepare("UPDATE student_exam_results SET sgpa=?, total_credits=?, result_status=?, processed_at=CURRENT_TIMESTAMP WHERE student_id=? AND exam_id=?");
                $upd_res->execute([$sgpa, $total_credits, $res_status, $rec['student_id'], $rec['exam_id']]);
            }
        }

        $pdo->commit();
        $message = "Revaluation updated successfully.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch Requests
$requests = $pdo->query("
    SELECT er.*, u.username as student_name, e.name as exam_name, s.subject_name
    FROM exam_revaluations er
    JOIN users u ON er.student_id = u.id
    JOIN exams e ON er.exam_id = e.id
    JOIN subjects s ON er.subject_id = s.id
    ORDER BY er.requested_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Revaluation Requests</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Subject</th>
                        <th>Original Marks</th>
                        <th>New Marks</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= date('d-M-Y', strtotime($req['requested_at'])) ?></td>
                            <td><?= htmlspecialchars($req['student_name']) ?></td>
                            <td><?= htmlspecialchars($req['exam_name']) ?></td>
                            <td><?= htmlspecialchars($req['subject_name']) ?></td>
                            <td><?= htmlspecialchars($req['original_marks']) ?></td>
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <td>
                                    <input type="number" step="0.5" name="new_marks" class="form-control form-control-sm" value="<?= $req['new_marks'] ?: $req['original_marks'] ?>">
                                </td>
                                <td>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="Requested" <?= ($req['status'] == 'Requested') ? 'selected' : '' ?>>Requested</option>
                                        <option value="Processing" <?= ($req['status'] == 'Processing') ? 'selected' : '' ?>>Processing</option>
                                        <option value="Completed" <?= ($req['status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
                                        <option value="No Change" <?= ($req['status'] == 'No Change') ? 'selected' : '' ?>>No Change</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="update_reval" class="btn btn-sm btn-success">Update</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
