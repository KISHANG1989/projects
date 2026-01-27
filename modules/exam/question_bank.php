<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Allow Admin and Faculty
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'faculty') {
    echo "<div class='alert alert-danger'>Access Denied</div>";
    require_once '../../includes/footer.php';
    exit;
}

$pdo = getDBConnection();
$message = "";

// Handle Question Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $subject_id = $_POST['subject_id'];
    $unit = $_POST['unit'];
    $question_text = $_POST['question_text'];
    $marks = $_POST['marks'];
    $complexity = $_POST['complexity'];
    $question_type = $_POST['question_type'];

    $options = null;
    $correct_option = null;

    if ($question_type == 'MCQ') {
        $opts = [
            'A' => $_POST['option_a'],
            'B' => $_POST['option_b'],
            'C' => $_POST['option_c'],
            'D' => $_POST['option_d']
        ];
        $options = json_encode($opts);
        $correct_option = $_POST['correct_option'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO questions (subject_id, unit, question_text, question_type, marks, complexity, options, correct_option, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$subject_id, $unit, $question_text, $question_type, $marks, $complexity, $options, $correct_option, $_SESSION['user_id']]);
        $message = "Question added successfully!";
    } catch (PDOException $e) {
        $message = "Error adding question: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "Question deleted.";
}

// Fetch Subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Questions (Filterable)
$filter_subject = $_GET['subject_id'] ?? '';
$sql = "SELECT q.*, s.subject_name, s.subject_code FROM questions q JOIN subjects s ON q.subject_id = s.id";
$params = [];
if ($filter_subject) {
    $sql .= " WHERE q.subject_id = ?";
    $params[] = $filter_subject;
}
$sql .= " ORDER BY q.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Question Bank Management</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Add Question Form -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">Add New Question</div>
    <div class="card-body">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" <?= ($filter_subject == $sub['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sub['subject_name'] . ' (' . $sub['subject_code'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-select" required>
                        <option value="1">Unit 1</option>
                        <option value="2">Unit 2</option>
                        <option value="3">Unit 3</option>
                        <option value="4">Unit 4</option>
                        <option value="5">Unit 5</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Marks</label>
                    <input type="number" name="marks" class="form-control" value="5" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Complexity</label>
                    <select name="complexity" class="form-select">
                        <option value="Easy">Easy</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Hard">Hard</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="question_type" class="form-select" id="qType" onchange="toggleOptions()">
                        <option value="Descriptive">Descriptive</option>
                        <option value="MCQ">MCQ</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Question Text</label>
                <textarea name="question_text" class="form-control" rows="3" required></textarea>
            </div>

            <div id="mcqOptions" class="row mb-3" style="display:none;">
                <div class="col-md-6">
                    <input type="text" name="option_a" class="form-control mb-2" placeholder="Option A">
                    <input type="text" name="option_b" class="form-control mb-2" placeholder="Option B">
                </div>
                <div class="col-md-6">
                    <input type="text" name="option_c" class="form-control mb-2" placeholder="Option C">
                    <input type="text" name="option_d" class="form-control mb-2" placeholder="Option D">
                </div>
                <div class="col-md-4 mt-2">
                    <label>Correct Option</label>
                    <select name="correct_option" class="form-select">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
            </div>

            <button type="submit" name="add_question" class="btn btn-success">Add Question</button>
        </form>
    </div>
</div>

<!-- Filter -->
<form method="GET" class="mb-3">
    <div class="input-group w-50">
        <select name="subject_id" class="form-select">
            <option value="">All Subjects</option>
            <?php foreach ($subjects as $sub): ?>
                <option value="<?= $sub['id'] ?>" <?= ($filter_subject == $sub['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sub['subject_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-secondary">Filter</button>
    </div>
</form>

<!-- Question List -->
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Unit</th>
                <th>Type</th>
                <th>Question</th>
                <th>Marks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($questions) > 0): ?>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?= htmlspecialchars($q['subject_code']) ?></td>
                        <td><?= htmlspecialchars($q['unit']) ?></td>
                        <td><?= htmlspecialchars($q['question_type']) ?></td>
                        <td>
                            <?= htmlspecialchars(substr($q['question_text'], 0, 100)) ?>...
                            <?php if ($q['question_type'] == 'MCQ'): ?>
                                <br><small class="text-muted">Options: <?= htmlspecialchars($q['options']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($q['marks']) ?></td>
                        <td>
                            <a href="?delete=<?= $q['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No questions found. Select a subject or add new ones.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleOptions() {
    var type = document.getElementById('qType').value;
    var opts = document.getElementById('mcqOptions');
    if (type === 'MCQ') {
        opts.style.display = 'flex';
    } else {
        opts.style.display = 'none';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
