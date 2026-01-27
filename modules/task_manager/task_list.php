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

// Get Search Filters
$filter_status = $_GET['status'] ?? '';
$filter_prio = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

// Build Query
$query = "SELECT t.*, u.username as assignee_name, u2.username as assigner_name
          FROM tasks t
          LEFT JOIN users u ON t.assigned_to = u.id
          LEFT JOIN users u2 ON t.assigned_by = u2.id
          WHERE 1=1";
$params = [];

// Role Scope
if ($role === 'staff') {
    $query .= " AND t.assigned_to = ?";
    $params[] = $user_id;
} elseif ($role === 'dept_head') {
    $query .= " AND t.department = ?";
    $params[] = $department;
}
// Admin sees all

// Filters
if ($filter_status) {
    $query .= " AND t.status = ?";
    $params[] = $filter_status;
}
if ($filter_prio) {
    $query .= " AND t.priority = ?";
    $params[] = $filter_prio;
}
if ($search) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Task List</h2>
        <?php if($role !== 'staff'): ?>
            <a href="create_task.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Task</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $filter_status=='Pending'?'selected':''; ?>>Pending</option>
                        <option value="In Progress" <?php echo $filter_status=='In Progress'?'selected':''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $filter_status=='Completed'?'selected':''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <option value="High" <?php echo $filter_prio=='High'?'selected':''; ?>>High</option>
                        <option value="Medium" <?php echo $filter_prio=='Medium'?'selected':''; ?>>Medium</option>
                        <option value="Low" <?php echo $filter_prio=='Low'?'selected':''; ?>>Low</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
             <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Title</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tasks as $task): ?>
                        <tr>
                            <td class="ps-4">
                                <a href="view_task.php?id=<?php echo $task['id']; ?>" class="fw-bold text-dark text-decoration-none"><?php echo htmlspecialchars($task['title']); ?></a>
                                <?php if($role === 'admin'): ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($task['department']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $prioClass = match($task['priority']) {
                                        'High', 'Urgent' => 'text-danger fw-bold',
                                        'Medium' => 'text-warning fw-bold',
                                        'Low' => 'text-success',
                                        default => 'text-secondary'
                                    };
                                ?>
                                <span class="<?php echo $prioClass; ?>"><?php echo $task['priority']; ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo $task['status']; ?></span></td>
                            <td>
                                <div class="progress" style="height: 6px; width: 80px;">
                                    <div class="progress-bar bg-primary" style="width: <?php echo $task['progress']; ?>%"></div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($task['assignee_name']); ?></td>
                            <td><?php echo $task['due_date']; ?></td>
                            <td class="text-end pe-4">
                                <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($tasks)): ?>
                            <tr><td colspan="7" class="text-center py-5">No tasks found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
