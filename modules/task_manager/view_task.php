<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Task
$stmt = $pdo->prepare("SELECT t.*, u.username as assignee_name, u2.username as assigner_name
                       FROM tasks t
                       LEFT JOIN users u ON t.assigned_to = u.id
                       LEFT JOIN users u2 ON t.assigned_by = u2.id
                       WHERE t.id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) die("Task not found.");

// Check Permissions
$stmt = $pdo->prepare("SELECT role, department FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$role = $user['role'];
$dept = $user['department'];

// Access Rules:
// 1. Admin: All
// 2. Head: Own Department
// 3. Staff: Assigned To Me
$hasAccess = false;
if ($role === 'admin') $hasAccess = true;
elseif ($role === 'dept_head' && $dept === $task['department']) $hasAccess = true;
elseif ($task['assigned_to'] == $user_id) $hasAccess = true;

if (!$hasAccess) die("Unauthorized access.");

// Handle Updates (Status/Progress/Comment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $status = $_POST['status'];
        $progress = (int)$_POST['progress'];

        $upd = $pdo->prepare("UPDATE tasks SET status = ?, progress = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $upd->execute([$status, $progress, $task_id]);

        // Refresh
        redirect("view_task.php?id=$task_id");
    }
    elseif (isset($_POST['add_comment'])) {
        $comment = sanitize($_POST['comment']);
        if (!empty($comment)) {
            $ins = $pdo->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            $ins->execute([$task_id, $user_id, $comment]);
            redirect("view_task.php?id=$task_id");
        }
    }
}

// Fetch Comments
$c_stmt = $pdo->prepare("SELECT c.*, u.username FROM task_comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at DESC");
$c_stmt->execute([$task_id]);
$comments = $c_stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Task Details -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <a href="index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                        <h4 class="fw-bold mb-0 mt-1"><?php echo htmlspecialchars($task['title']); ?></h4>
                    </div>
                    <span class="badge bg-light text-dark border"><?php echo $task['department']; ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                         <div class="col-md-3 text-muted">Priority</div>
                         <div class="col-md-9 fw-bold <?php echo $task['priority']=='High'?'text-danger':''; ?>"><?php echo $task['priority']; ?></div>

                         <div class="col-md-3 text-muted mt-2">Due Date</div>
                         <div class="col-md-9 mt-2"><?php echo $task['due_date']; ?></div>

                         <div class="col-md-3 text-muted mt-2">Assigned To</div>
                         <div class="col-md-9 mt-2"><?php echo htmlspecialchars($task['assignee_name']); ?></div>

                         <div class="col-md-3 text-muted mt-2">Assigned By</div>
                         <div class="col-md-9 mt-2"><?php echo htmlspecialchars($task['assigner_name']); ?></div>
                    </div>

                    <h6 class="fw-bold">Description</h6>
                    <p class="text-secondary bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Collaboration / Comments</div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="add_comment" value="1">
                        <div class="input-group">
                            <textarea name="comment" class="form-control" rows="2" placeholder="Write a comment..." required></textarea>
                            <button class="btn btn-primary">Post</button>
                        </div>
                    </form>

                    <div class="comments-list">
                        <?php foreach($comments as $c): ?>
                            <div class="d-flex mb-3 pb-3 border-bottom">
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($c['username'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold small">
                                        <?php echo htmlspecialchars($c['username']); ?>
                                        <span class="text-muted fw-normal ms-2"><?php echo date('M d, H:i', strtotime($c['created_at'])); ?></span>
                                    </div>
                                    <div class="text-dark mt-1"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($comments)): ?>
                            <p class="text-muted text-center py-3">No comments yet. Start the discussion.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Update Status</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="update_status" value="1">

                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <select name="status" class="form-select">
                                <option value="Pending" <?php echo $task['status']=='Pending'?'selected':''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $task['status']=='In Progress'?'selected':''; ?>>In Progress</option>
                                <option value="Completed" <?php echo $task['status']=='Completed'?'selected':''; ?>>Completed</option>
                                <option value="On Hold" <?php echo $task['status']=='On Hold'?'selected':''; ?>>On Hold</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Progress: <span id="progVal"><?php echo $task['progress']; ?></span>%</label>
                            <input type="range" name="progress" class="form-range" min="0" max="100" value="<?php echo $task['progress']; ?>" oninput="document.getElementById('progVal').innerText = this.value">
                        </div>

                        <button type="submit" class="btn btn-success w-100">Update Task</button>
                    </form>
                </div>
            </div>

            <div class="mt-3 small text-muted text-center">
                Last updated: <?php echo $task['updated_at']; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
