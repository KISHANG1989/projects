<?php
require_once __DIR__ . '/../config/db.php';

echo "Initializing Database...\n";

$pdo = getDBConnection();

// Create users table - Added department
// Note: In SQLite ALTER TABLE is limited. Since this is a setup script, we create new if not exists.
// But if it exists without department, we might need to alter it.
// For this environment, we can assume we can recreate or check/add column.
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL,
    department TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);

// Check if department column exists (migration)
try {
    $pdo->query("SELECT department FROM users LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE users ADD COLUMN department TEXT");
    echo "Added 'department' column to users table.\n";
}

echo "Table 'users' check complete.\n";

// Create student_profiles table
$sql = "CREATE TABLE IF NOT EXISTS student_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    full_name TEXT NOT NULL,
    dob DATE,
    address TEXT,
    nationality TEXT NOT NULL DEFAULT 'Indian',
    category TEXT,
    course_applied TEXT,
    previous_marks TEXT,
    abc_id TEXT,
    roll_number TEXT,
    enrollment_status TEXT DEFAULT 'Pending',
    application_no TEXT,
    is_form_locked INTEGER DEFAULT 0,
    edit_permissions TEXT,
    extended_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$pdo->exec($sql);
echo "Table 'student_profiles' created.\n";

// Create international_details table
$sql = "CREATE TABLE IF NOT EXISTS international_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_profile_id INTEGER NOT NULL,
    passport_number TEXT,
    visa_details TEXT,
    country_of_origin TEXT,
    FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id)
)";
$pdo->exec($sql);
echo "Table 'international_details' created.\n";

// Create documents table
$sql = "CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    doc_type TEXT NOT NULL,
    file_path TEXT NOT NULL,
    status TEXT DEFAULT 'Pending',
    remarks TEXT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$pdo->exec($sql);
echo "Table 'documents' created.\n";

// --- TASK MANAGER TABLES ---

// Tasks Table
$sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    assigned_to INTEGER,
    assigned_by INTEGER,
    department TEXT,
    priority TEXT DEFAULT 'Medium', -- Low, Medium, High, Urgent
    status TEXT DEFAULT 'Pending', -- Pending, In Progress, Completed, On Hold
    progress INTEGER DEFAULT 0,
    due_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
)";
$pdo->exec($sql);
echo "Table 'tasks' created.\n";

// Task Comments Table
$sql = "CREATE TABLE IF NOT EXISTS task_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$pdo->exec($sql);
echo "Table 'task_comments' created.\n";


// Seed users
// Roles: admin, registrar, dept_head, staff, student, faculty
$users = [
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'admin', 'department' => 'Administration'],
    ['username' => 'registrar', 'password' => 'registrar123', 'role' => 'registrar', 'department' => 'Registrar'],
    ['username' => 'faculty', 'password' => 'faculty123', 'role' => 'faculty', 'department' => 'Academic'],
    ['username' => 'student', 'password' => 'student123', 'role' => 'student', 'department' => 'Academic'],

    // Task Manager Demo Users
    ['username' => 'head_cse', 'password' => 'password', 'role' => 'dept_head', 'department' => 'CSE'],
    ['username' => 'staff_cse_1', 'password' => 'password', 'role' => 'staff', 'department' => 'CSE'],
    ['username' => 'staff_cse_2', 'password' => 'password', 'role' => 'staff', 'department' => 'CSE'],

    ['username' => 'head_hr', 'password' => 'password', 'role' => 'dept_head', 'department' => 'HR'],
    ['username' => 'staff_hr', 'password' => 'password', 'role' => 'staff', 'department' => 'HR'],
];

foreach ($users as $user) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user['username']]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, department) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user['username'],
            password_hash($user['password'], PASSWORD_DEFAULT),
            $user['role'],
            $user['department']
        ]);
        echo "User '{$user['username']}' created.\n";
    } else {
        // Update department if missing (for existing users like admin)
        if (empty($existing['department']) && !empty($user['department'])) {
             $stmt = $pdo->prepare("UPDATE users SET department = ? WHERE id = ?");
             $stmt->execute([$user['department'], $existing['id']]);
             echo "User '{$user['username']}' updated with department.\n";
        }
        echo "User '{$user['username']}' verified.\n";
    }
}

// Seed some tasks if empty
$stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
if ($stmt->fetchColumn() == 0) {
    echo "Seeding sample tasks...\n";

    // Get User IDs
    $u_stm = $pdo->prepare("SELECT id FROM users WHERE username = ?");

    $u_stm->execute(['head_cse']); $id_head_cse = $u_stm->fetchColumn();
    $u_stm->execute(['staff_cse_1']); $id_staff_1 = $u_stm->fetchColumn();
    $u_stm->execute(['staff_cse_2']); $id_staff_2 = $u_stm->fetchColumn();

    if ($id_head_cse && $id_staff_1) {
        $tasks = [
            [
                'title' => 'Prepare Syllabus Update',
                'desc' => 'Update the syllabus for next semester according to NEP.',
                'to' => $id_staff_1, 'by' => $id_head_cse, 'dept' => 'CSE', 'prio' => 'High', 'status' => 'In Progress', 'prog' => 40
            ],
            [
                'title' => 'Lab Equipment Audit',
                'desc' => 'Check all computers in Lab 3.',
                'to' => $id_staff_2, 'by' => $id_head_cse, 'dept' => 'CSE', 'prio' => 'Medium', 'status' => 'Pending', 'prog' => 0
            ],
             [
                'title' => 'Student Attendance Report',
                'desc' => 'Compile attendance for last month.',
                'to' => $id_staff_1, 'by' => $id_head_cse, 'dept' => 'CSE', 'prio' => 'Low', 'status' => 'Completed', 'prog' => 100
            ]
        ];

        $ins = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, department, priority, status, progress, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, date('now', '+7 days'))");

        foreach ($tasks as $t) {
            $ins->execute([$t['title'], $t['desc'], $t['to'], $t['by'], $t['dept'], $t['prio'], $t['status'], $t['prog']]);
        }
        echo "Sample tasks seeded.\n";
    }
}

echo "Database setup complete.\n";
