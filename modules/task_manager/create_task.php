<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/functions.php';

requireLogin();

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get User Role & Dept
$stmt = $pdo->prepare("SELECT role, department FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();
$role = $u['role'];
$department = $u['department'];

// Only Admin or Head can create
if ($role !== 'admin' && $role !== 'dept_head') {
    die("Unauthorized access.");
}

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $assigned_to = (int)$_POST['assigned_to'];

    // Determine Department
    if ($role === 'dept_head') {
        $task_dept = $department;
        // Verify assignee belongs to dept
        $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND department = ?");
        $check->execute([$assigned_to, $department]);
        if (!$check->fetch()) {
            $error = "Cannot assign to user outside your department.";
        }
    } else {
        // Admin
        $task_dept = $_POST['department']; // Admin selects department or it's inferred from user
        // If Admin selects user directly, we get dept from user
        if (empty($task_dept)) {
             $ud = $pdo->prepare("SELECT department FROM users WHERE id = ?");
             $ud->execute([$assigned_to]);
             $task_dept = $ud->fetchColumn();
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, department, priority, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $desc, $assigned_to, $user_id, $task_dept, $priority, $due_date])) {
            $success = "Task created successfully!";
        } else {
            $error = "Failed to create task.";
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';

// Fetch potential assignees
$assignees = [];
if ($role === 'dept_head') {
    $assignees = getDepartmentStaff($pdo, $department);
} else {
    // Admin: Get All Staff grouped by Dept? Or just all.
    // Let's do all users for now.
    $assignees = $pdo->query("SELECT id, username, department FROM users WHERE role != 'student' ORDER BY department, username")->fetchAll();
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="fw-bold mb-0">Create New Task</h5>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
                <div class="card-body p-4">
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?> <a href="index.php">Go to Dashboard</a></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Task Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign To <span class="text-danger">*</span></label>
                                <select name="assigned_to" class="form-select" required id="assigneeSelect">
                                    <option value="">Select Staff</option>
                                    <?php foreach($assignees as $a): ?>
                                        <option value="<?php echo $a['id']; ?>" data-dept="<?php echo $a['department'] ?? ''; ?>">
                                            <?php echo htmlspecialchars($a['username']); ?>
                                            (<?php echo htmlspecialchars($a['department'] ?? 'No Dept'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                             <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <?php if($role === 'admin'): ?>
                                    <input type="text" name="department" id="deptInput" class="form-control" readonly placeholder="Auto-selected">
                                <?php else: ?>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($department); ?>" disabled>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Create Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($role === 'admin'): ?>
<script>
    document.getElementById('assigneeSelect').addEventListener('change', function() {
        var option = this.options[this.selectedIndex];
        var dept = option.getAttribute('data-dept');
        document.getElementById('deptInput').value = dept ? dept : '';
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
