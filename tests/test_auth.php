<?php
// Mock session start for CLI if needed, but PHP CLI usually handles it.
// We need to suppress headers sent errors if any output happens before session_start,
// but in CLI headers are not sent the same way.

require_once __DIR__ . '/../includes/auth.php';

echo "Testing Authentication...\n";

// Test 1: Login with correct credentials
if (login('admin', 'admin123')) {
    echo "PASS: Login successful with correct credentials.\n";
    if (isset($_SESSION['user_id']) && $_SESSION['username'] === 'admin') {
        echo "PASS: Session variables set correctly.\n";
    } else {
        echo "FAIL: Session variables NOT set correctly.\n";
        print_r($_SESSION);
    }
} else {
    echo "FAIL: Login failed with correct credentials.\n";
}

// Test 2: Login with incorrect credentials
if (!login('admin', 'wrongpass')) {
    echo "PASS: Login failed with incorrect credentials.\n";
} else {
    echo "FAIL: Login successful with incorrect credentials.\n";
}

// Test 3: Logout
logout();
if (empty($_SESSION['user_id'])) {
    echo "PASS: Logout successful, session cleared.\n";
} else {
    echo "FAIL: Session not cleared after logout.\n";
}
