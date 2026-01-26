<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/functions.php';

requireLogin();

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
// Fetch user details to get role and department (session might be stale if we just updated DB)
$stmt = $pdo->prepare("SELECT role, department FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();
$role = $u['role'];
$department = $u['department'];

// Handle Admin Filter
$selected_dept = $department;
if ($role === 'admin' && isset($_GET['dept'])) {
    $selected_dept = $_GET['dept'];
    // For admin stats, we treat them as "dept_head" of the selected department context,
    // or if no dept selected, we might want global stats.
    // Let's modify logic: if Admin, pass null or specific dept to stats function.
}

// Logic for Stats
if ($role === 'admin' && empty($_GET['dept'])) {
    // Global Stats
    // We need to adjust getTaskStats to handle "All" if no department and role is admin.
    // Let's hack: call with role='admin' which bypasses filters in function.
    $stats_role = 'admin';
    $stats_dept = null;
} elseif ($role === 'admin') {
    $stats_role = 'dept_head'; // Simulate head view for admin
    $stats_dept = $selected_dept;
} else {
    $stats_role = $role;
    $stats_dept = $department;
}

$stats = getTaskStats($pdo, $user_id, $stats_role, $stats_dept);
$recent_tasks = getRecentTasks($pdo, $user_id, $stats_role, $stats_dept, 10);
$all_depts = getAllDepartments($pdo);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">
                <?php if($role === 'staff'): ?>My Task Dashboard<?php else: ?>Task Manager Dashboard<?php endif; ?>
            </h2>
            <p class="text-muted">
                <?php if($role === 'admin'): ?>
                    Overview of University Tasks
                <?php elseif($role === 'dept_head'): ?>
                    <?php echo htmlspecialchars($department); ?> Department Overview
                <?php else: ?>
                    Track your progress and pending work
                <?php endif; ?>
            </p>
        </div>
        <div>
            <?php if($role === 'admin' || $role === 'dept_head'): ?>
                <a href="create_task.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Create New Task</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($role === 'admin'): ?>
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="fw-bold">Select Department:</label></div>
                <div class="col-auto">
                    <select name="dept" class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach($all_depts as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($selected_dept == $d) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Total Tasks</div>
                    <div class="fs-2 fw-bold text-dark"><?php echo $stats['total']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Pending</div>
                    <div class="fs-2 fw-bold text-dark"><?php echo $stats['pending']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">In Progress</div>
                    <div class="fs-2 fw-bold text-dark"><?php echo $stats['in_progress']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Completed</div>
                    <div class="fs-2 fw-bold text-dark"><?php echo $stats['completed']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Graphs -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Task Status Distribution</div>
                <div class="card-body">
                    <canvas id="statusChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Task Priority Breakdown</div>
                <div class="card-body">
                     <canvas id="priorityChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tasks List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Recent Tasks</h5>
            <a href="task_list.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Task</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <?php if($role !== 'staff'): ?><th>Assigned To</th><?php endif; ?>
                            <th>Due Date</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_tasks as $task): ?>
                        <tr>
                            <td class="ps-4">
                                <a href="view_task.php?id=<?php echo $task['id']; ?>" class="text-decoration-none fw-bold text-dark">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                                <div class="small text-muted text-truncate" style="max-width: 200px;">
                                    <?php echo htmlspecialchars($task['description']); ?>
                                </div>
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
                            <td>
                                <?php
                                    $statusBadge = match($task['status']) {
                                        'Completed' => 'bg-success',
                                        'In Progress' => 'bg-info',
                                        'Pending' => 'bg-warning text-dark',
                                        'On Hold' => 'bg-secondary',
                                        default => 'bg-light text-dark border'
                                    };
                                ?>
                                <span class="badge <?php echo $statusBadge; ?> rounded-pill"><?php echo $task['status']; ?></span>
                            </td>
                            <td style="width: 15%;">
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $task['progress']; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $task['progress']; ?>%</small>
                            </td>
                            <?php if($role !== 'staff'): ?>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                        <?php echo strtoupper(substr($task['assignee_name'] ?? '?', 0, 2)); ?>
                                    </div>
                                    <small><?php echo htmlspecialchars($task['assignee_name'] ?? 'Unassigned'); ?></small>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php
                                    $due = new DateTime($task['due_date']);
                                    $now = new DateTime();
                                    $isOverdue = $due < $now && $task['status'] !== 'Completed';
                                ?>
                                <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo $due->format('M d, Y'); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_tasks)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No tasks found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Data from PHP
    const statusData = <?php echo json_encode($stats['status_counts']); ?>;
    const priorityData = <?php echo json_encode($stats['priority_counts']); ?>;

    // Status Chart (Pie)
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#6c757d'], // Warning, Info, Success, Secondary
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Priority Chart (Bar)
    new Chart(document.getElementById('priorityChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(priorityData),
            datasets: [{
                label: 'Tasks',
                data: Object.values(priorityData),
                backgroundColor: ['#20c997', '#ffc107', '#dc3545', '#6610f2'], // Colors...
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
