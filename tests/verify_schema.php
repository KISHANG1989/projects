<?php
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$expected = ['users', 'student_profiles', 'international_details', 'documents'];
$missing = array_diff($expected, $tables);

if (empty($missing)) {
    echo "PASS: All expected tables exist.\n";
} else {
    echo "FAIL: Missing tables: " . implode(', ', $missing) . "\n";
    exit(1);
}
