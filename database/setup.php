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

// Migration for new columns in student_profiles
try {
    $pdo->query("SELECT student_status FROM student_profiles LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE student_profiles ADD COLUMN student_status TEXT DEFAULT 'Provisional'");
    echo "Added 'student_status' column to student_profiles.\n";
}

try {
    $pdo->query("SELECT admission_mode FROM student_profiles LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE student_profiles ADD COLUMN admission_mode TEXT DEFAULT 'Regular'");
    echo "Added 'admission_mode' column to student_profiles.\n";
}

try {
    $pdo->query("SELECT current_semester FROM student_profiles LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE student_profiles ADD COLUMN current_semester INTEGER DEFAULT 1");
    echo "Added 'current_semester' column to student_profiles.\n";
}

echo "Table 'student_profiles' created/updated.\n";

// Create student_status_logs table
$sql = "CREATE TABLE IF NOT EXISTS student_status_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_profile_id INTEGER NOT NULL,
    old_status TEXT,
    new_status TEXT NOT NULL,
    changed_by INTEGER NOT NULL,
    remarks TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
)";
$pdo->exec($sql);
echo "Table 'student_status_logs' created.\n";

// Create convocation_events table
$sql = "CREATE TABLE IF NOT EXISTS convocation_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    event_date DATE NOT NULL,
    venue TEXT,
    batch_year TEXT NOT NULL,
    status TEXT DEFAULT 'Upcoming', -- Upcoming, Completed
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);
echo "Table 'convocation_events' created.\n";

// Create student_degrees table
$sql = "CREATE TABLE IF NOT EXISTS student_degrees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    convocation_id INTEGER,
    degree_serial_no TEXT UNIQUE NOT NULL,
    issue_date DATE NOT NULL,
    program_name TEXT NOT NULL,
    division TEXT,
    cgpa REAL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    file_path TEXT,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (convocation_id) REFERENCES convocation_events(id)
)";
$pdo->exec($sql);
echo "Table 'student_degrees' created.\n";

// --- EXAM MODULE TABLES ---

// Subjects Table
$sql = "CREATE TABLE IF NOT EXISTS subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject_code TEXT UNIQUE NOT NULL,
    subject_name TEXT NOT NULL,
    program_name TEXT NOT NULL,
    semester INTEGER NOT NULL,
    credits INTEGER NOT NULL DEFAULT 3,
    type TEXT DEFAULT 'Core', -- Core, Elective, Practical
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);
echo "Table 'subjects' created.\n";

// Exams Table
$sql = "CREATE TABLE IF NOT EXISTS exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    session TEXT NOT NULL,
    program_name TEXT NOT NULL,
    semester INTEGER NOT NULL,
    status TEXT DEFAULT 'Upcoming', -- Upcoming, Ongoing, Completed, Results Declared
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);
echo "Table 'exams' created.\n";

// Exam Timetable
$sql = "CREATE TABLE IF NOT EXISTS exam_timetable (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_id INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
)";
$pdo->exec($sql);
echo "Table 'exam_timetable' created.\n";

// Student Marks
$sql = "CREATE TABLE IF NOT EXISTS student_marks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    exam_id INTEGER NOT NULL,
    subject_id INTEGER NOT NULL,
    internal_marks REAL DEFAULT 0,
    external_marks REAL DEFAULT 0,
    total_marks REAL DEFAULT 0,
    grade TEXT,
    grade_point INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    UNIQUE(student_id, exam_id, subject_id)
)";
$pdo->exec($sql);
echo "Table 'student_marks' created.\n";

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
    attachment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id)
)";
$pdo->exec($sql);

// Migration for attachment column in tasks
try {
    $pdo->query("SELECT attachment FROM tasks LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE tasks ADD COLUMN attachment TEXT");
    echo "Added 'attachment' column to tasks table.\n";
}

echo "Table 'tasks' created.\n";

// Task Comments Table
$sql = "CREATE TABLE IF NOT EXISTS task_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    comment TEXT NOT NULL,
    attachment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$pdo->exec($sql);

// Migration for attachment column in comments
try {
    $pdo->query("SELECT attachment FROM task_comments LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE task_comments ADD COLUMN attachment TEXT");
    echo "Added 'attachment' column to task_comments table.\n";
}

echo "Table 'task_comments' created.\n";

// --- NOTIFICATIONS TABLE ---
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    link TEXT,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$pdo->exec($sql);
echo "Table 'notifications' created.\n";


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
