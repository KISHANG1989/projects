<?php
require_once __DIR__ . '/../config/db.php';

echo "Initializing Database...\n";

$pdo = getDBConnection();

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

$pdo->exec($sql);
echo "Table 'users' created.\n";

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

// Seed users
$users = [
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'admin'],
    ['username' => 'registrar', 'password' => 'registrar123', 'role' => 'registrar'],
    ['username' => 'faculty', 'password' => 'faculty123', 'role' => 'faculty'],
    ['username' => 'student', 'password' => 'student123', 'role' => 'student'],
];

foreach ($users as $user) {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$user['username']]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([
            $user['username'],
            password_hash($user['password'], PASSWORD_DEFAULT),
            $user['role']
        ]);
        echo "User '{$user['username']}' created.\n";
    } else {
        echo "User '{$user['username']}' already exists.\n";
    }
}

echo "Database setup complete.\n";
