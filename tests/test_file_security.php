<?php
define('TEST_MODE', true);

// Include auth FIRST to start session
require_once __DIR__ . '/../includes/auth.php';

// Mock Session
$_SESSION['user_id'] = 5; // new user
$_SESSION['username'] = 'hacker';
$_SESSION['role'] = 'student';

// Mock Request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'full_name' => 'Hacker',
    'dob' => '2000-01-01',
    'address' => 'Dark Web',
    'nationality' => 'Indian',
    'category' => 'General',
    'course_applied' => 'B.Tech',
    'previous_marks' => '0%'
];

// Mock Malicious File
$tmp_file = sys_get_temp_dir() . '/exploit.php';
file_put_contents($tmp_file, '<?php echo "pwned"; ?>');

$_FILES = [
    'photo' => [
        'name' => 'exploit.php',
        'type' => 'application/x-php',
        'tmp_name' => $tmp_file,
        'error' => 0,
        'size' => 123
    ],
    // Fill others to avoid "missing file" errors if checks come first
    'id_proof' => ['error' => 4],
    'previous_marksheet' => ['error' => 4]
];

// We need to capture the redirect URL to see if it contains error
$redirect_url = '';
// Override redirect in this scope? No, functions.php is already included.
// But redirect function in functions.php checks TEST_MODE and echoes.
// So we capture output.

ob_start();
require __DIR__ . '/../modules/registrar/registration_handler.php';
$output = ob_get_clean();

// Check for error message in redirect URL
if (strpos($output, 'Invalid+file+type') !== false) {
    echo "PASS: Upload rejected with invalid file type error.\n";
} else {
    echo "FAIL: Upload was not rejected properly. Output: $output\n";
}
