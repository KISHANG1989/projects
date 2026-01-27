<?php
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
$stmt = $pdo->query("PRAGMA table_info(student_profiles)");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1); // Index 1 is name

$expected = ['course_applied', 'previous_marks', 'abc_id', 'roll_number'];
$missing = array_diff($expected, $columns);

if (empty($missing)) {
    echo "PASS: All new columns exist in student_profiles.\n";
} else {
    echo "FAIL: Missing columns: " . implode(', ', $missing) . "\n";
    exit(1);
}

// Check documents for remarks
$stmt = $pdo->query("PRAGMA table_info(documents)");
$doc_cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
if (in_array('remarks', $doc_cols)) {
    echo "PASS: 'remarks' column exists in documents.\n";
} else {
    echo "FAIL: 'remarks' column missing in documents.\n";
    exit(1);
}
