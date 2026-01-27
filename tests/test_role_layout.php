<?php
// We need to bypass the session_start in auth/header if we want to run this in CLI without warnings,
// but for logic verification, warnings are okay.
// However, functions.php is required by header.php.

require_once __DIR__ . '/../includes/functions.php';

// Mock functions needed if they depend on real DB or session that isn't fully there?
// functions.php has: sanitize, redirect, isLoggedIn, requireLogin, hasRole.
// isLoggedIn checks $_SESSION['user_id'].
// hasRole checks $_SESSION['role'].

// So we just need to populate $_SESSION.

$_SESSION['user_id'] = 123;
$_SESSION['username'] = 'testuser';

function getHeaderOutput() {
    ob_start();
    include __DIR__ . '/../includes/header.php';
    return ob_get_clean();
}

echo "Testing Role Layouts...\n";

// Test 1: Student
$_SESSION['role'] = 'student';
$output = getHeaderOutput();
if (strpos($output, 'Admission Form') !== false && strpos($output, 'Verification Queue') === false) {
    echo "PASS: Student sees Admission Form only.\n";
} else {
    echo "FAIL: Student layout incorrect.\n";
}

// Test 2: Registrar
$_SESSION['role'] = 'registrar';
$output = getHeaderOutput();
if (strpos($output, 'Verification Queue') !== false && strpos($output, 'Admission Form') === false) {
    echo "PASS: Registrar sees Verification Queue only.\n";
} else {
    echo "FAIL: Registrar layout incorrect.\n";
}

// Test 3: Admin
$_SESSION['role'] = 'admin';
$output = getHeaderOutput();
if (strpos($output, 'Verification Queue') !== false) {
    echo "PASS: Admin sees Verification Queue.\n";
} else {
    echo "FAIL: Admin layout incorrect.\n";
}
