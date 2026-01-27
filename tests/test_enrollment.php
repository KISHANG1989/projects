<?php
define('TEST_MODE', true);

require_once __DIR__ . '/../includes/auth.php';

// Mock Session for Registrar
$_SESSION['user_id'] = 2; // registrar
$_SESSION['role'] = 'registrar';

$pdo = getDBConnection();

// Create dummy profile
$pdo->exec("INSERT INTO student_profiles (user_id, full_name, enrollment_status) VALUES (888, 'Enrollment Test', 'Pending')");
$profile_id = $pdo->lastInsertId();

// Mock Request for Final Approval
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'final_decision',
    'profile_id' => $profile_id,
    'decision' => 'Approved'
];

// Include Handler
ob_start();
require __DIR__ . '/../modules/registrar/verify_action.php';
ob_end_clean();

// Check DB
$stmt = $pdo->prepare("SELECT enrollment_status, roll_number FROM student_profiles WHERE id = ?");
$stmt->execute([$profile_id]);
$row = $stmt->fetch();

$expected_roll = sprintf("UNIV-%s-%03d", date('Y'), $profile_id);

if ($row['enrollment_status'] === 'Approved' && $row['roll_number'] === $expected_roll) {
    echo "PASS: Student approved and roll number ($expected_roll) generated.\n";
} else {
    echo "FAIL: Status: {$row['enrollment_status']}, Roll: {$row['roll_number']}.\n";
}

// Clean up
$pdo->exec("DELETE FROM student_profiles WHERE id = $profile_id");
