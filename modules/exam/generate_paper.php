<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../config/db.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'faculty') {
    die("Access Denied");
}

$pdo = getDBConnection();
$message = "";

// Fetch Exams and Subjects
$exams = $pdo->query("SELECT * FROM exams WHERE status='Upcoming' OR status='Ongoing'")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    $exam_id = $_POST['exam_id'];
    $subject_id = $_POST['subject_id'];
    $paper_title = $_POST['paper_title'];

    // Blueprint: Questions per unit
    $blueprint = [
        1 => $_POST['unit1_count'],
        2 => $_POST['unit2_count'],
        3 => $_POST['unit3_count'],
        4 => $_POST['unit4_count'],
        5 => $_POST['unit5_count']
    ];

    $selected_questions = [];
    $total_marks = 0;

    try {
        $pdo->beginTransaction();

        foreach ($blueprint as $unit => $count) {
            if ($count > 0) {
                // Fetch random questions for this unit
                // Ensure we get enough questions
                $stmt = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ? AND unit = ? ORDER BY RANDOM() LIMIT ?");
                $stmt->execute([$subject_id, $unit, $count]);
                $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($questions) < $count) {
                    throw new Exception("Not enough questions in Question Bank for Unit $unit. Required: $count, Available: " . count($questions));
                }

                foreach ($questions as $q) {
                    $selected_questions[$unit][] = $q;
                    $total_marks += $q['marks'];
                }
            }
        }

        // Generate HTML Content
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?= htmlspecialchars($paper_title) ?></title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; }
                .header { text-align: center; margin-bottom: 30px; }
                .question { margin-bottom: 20px; }
                .q-meta { float: right; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Indian University</h2>
                <h3><?= htmlspecialchars($paper_title) ?></h3>
                <p>Total Marks: <?= $total_marks ?> | Time: 3 Hours</p>
            </div>
            <hr>

            <?php foreach ($selected_questions as $unit => $qs): ?>
                <h4>Unit <?= $unit ?></h4>
                <?php foreach ($qs as $idx => $q): ?>
                    <div class="question">
                        <span class="q-meta">[<?= $q['marks'] ?>]</span>
                        <p><strong>Q<?= ($idx+1) ?>.</strong> <?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
                        <?php if($q['question_type'] == 'MCQ'):
                            $opts = json_decode($q['options'], true);
                        ?>
                            <ul style="list-style-type: none;">
                            <?php foreach($opts as $key => $val): ?>
                                <li><?= $key ?>) <?= htmlspecialchars($val) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

        </body>
        </html>
        <?php
        $html_content = ob_get_clean();

        // Save File
        $filename = "paper_" . time() . ".html";
        $filepath = "../../uploads/question_papers/" . $filename;
        if (!is_dir("../../uploads/question_papers/")) {
            mkdir("../../uploads/question_papers/", 0777, true);
        }
        file_put_contents($filepath, $html_content);

        // Insert Record
        $stmt = $pdo->prepare("INSERT INTO question_papers (exam_id, subject_id, paper_title, generated_by, file_path, status) VALUES (?, ?, ?, ?, ?, 'Draft')");
        $stmt->execute([$exam_id, $subject_id, $paper_title, $_SESSION['user_id'], $filepath]);

        $pdo->commit();
        $message = "Question Paper Generated Successfully! <a href='$filepath' target='_blank' class='btn btn-sm btn-info'>View Paper</a>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Generate Question Paper</h1>
    <a href="question_bank.php" class="btn btn-outline-primary">Go to Question Bank</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Exam Event</label>
                    <select name="exam_id" class="form-select" required>
                        <?php foreach ($exams as $ex): ?>
                            <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name'] . ' (' . $sub['subject_code'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Paper Title</label>
                <input type="text" name="paper_title" class="form-control" placeholder="e.g. Winter 2024 - Computer Science - Paper 1" required>
            </div>

            <h5 class="mt-4">Blueprint (Questions per Unit)</h5>
            <div class="row mb-4">
                <?php for($i=1; $i<=5; $i++): ?>
                <div class="col">
                    <label class="form-label">Unit <?= $i ?></label>
                    <input type="number" name="unit<?= $i ?>_count" class="form-control" value="2" min="0">
                </div>
                <?php endfor; ?>
            </div>

            <button type="submit" name="generate" class="btn btn-success w-100">Generate Paper</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
