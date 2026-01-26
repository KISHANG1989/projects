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
