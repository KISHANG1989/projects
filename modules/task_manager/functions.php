<?php
require_once __DIR__ . '/../../includes/auth.php';

function getTaskStats($pdo, $user_id, $role, $department) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'high_priority' => 0,
        'status_counts' => [],
        'priority_counts' => []
    ];

    $where = "WHERE 1=1";
    $params = [];

    if ($role === 'staff') {
        $where .= " AND assigned_to = ?";
        $params[] = $user_id;
    } elseif ($role === 'dept_head') {
        $where .= " AND department = ?";
        $params[] = $department;
    }
    // Admin sees all, or filtered by department passed in args (handled by caller usually, but here we stick to base role scope)

    // Status Counts
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks $where GROUP BY status");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stats['status_counts'] = $rows;
    $stats['total'] = array_sum($rows);
    $stats['pending'] = $rows['Pending'] ?? 0;
    $stats['in_progress'] = $rows['In Progress'] ?? 0;
    $stats['completed'] = $rows['Completed'] ?? 0;

    // Priority Counts
    $stmt = $pdo->prepare("SELECT priority, COUNT(*) as count FROM tasks $where GROUP BY priority");
    $stmt->execute($params);
    $p_rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stats['priority_counts'] = $p_rows;
    $stats['high_priority'] = $p_rows['High'] ?? 0; // + Urgent?
    if (isset($p_rows['Urgent'])) $stats['high_priority'] += $p_rows['Urgent'];

    return $stats;
}

function getRecentTasks($pdo, $user_id, $role, $department, $limit = 5) {
    $where = "WHERE 1=1";
    $params = [];

    if ($role === 'staff') {
        $where .= " AND t.assigned_to = ?";
        $params[] = $user_id;
    } elseif ($role === 'dept_head') {
        $where .= " AND t.department = ?";
        $params[] = $department;
    }

    $sql = "SELECT t.*, u.username as assignee_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            $where
            ORDER BY t.created_at DESC LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAllDepartments($pdo) {
    // Since we don't have a departments table, we distinct users' departments or hardcode.
    // Querying users is safer to find active departments.
    return $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
}

function getDepartmentStaff($pdo, $department) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE department = ? AND role IN ('staff', 'dept_head', 'faculty') ORDER BY username");
    $stmt->execute([$department]);
    return $stmt->fetchAll();
}
