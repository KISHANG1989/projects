<?php
define('TEST_MODE', true);

// Include auth FIRST to start session
require_once __DIR__ . '/../includes/auth.php';

// Now populate Session
$_SESSION['user_id'] = 4; // student
$_SESSION['username'] = 'student';
$_SESSION['role'] = 'student';

// Mock Request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'full_name' => 'Test Student',
    'dob' => '2000-01-01',
    'address' => '123 Test St',
    'nationality' => 'International',
    'category' => 'General',
    'course_applied' => 'B.Tech',
    'previous_marks' => '90%',
    'abc_id' => 'ABC123456789',
    'passport_number' => 'P123456',
    'visa_details' => 'Student Visa',
    'country_of_origin' => 'Testland'
];

// Mock Files
$tmp_file = sys_get_temp_dir() . '/test_doc.txt';
file_put_contents($tmp_file, 'dummy content');

$_FILES = [
    'photo' => [
        'name' => 'photo.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => $tmp_file,
        'error' => 0,
        'size' => 123
    ],
    'id_proof' => [
        'name' => 'id.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tmp_file,
        'error' => 0,
        'size' => 123
    ],
    'passport_copy' => [
        'name' => 'pass.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tmp_file,
        'error' => 0,
        'size' => 123
    ],
    'visa_copy' => [
        'name' => 'visa.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tmp_file,
        'error' => 0,
        'size' => 123
    ],
    'previous_marksheet' => [
        'name' => 'marks.pdf',
        'type' => 'application/pdf',
        'tmp_name' => $tmp_file,
        'error' => 0,
        'size' => 123
    ]
];

require_once __DIR__ . '/../modules/registrar/registration_handler.php';

// Verify DB
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM student_profiles WHERE full_name = 'Test Student'");
$profile = $stmt->fetch();

if ($profile) {
    echo "PASS: Student profile created.\n";
} else {
    echo "FAIL: Student profile not created.\n";
}

$stmt = $pdo->query("SELECT * FROM international_details WHERE passport_number = 'P123456'");
$intl = $stmt->fetch();

if ($intl) {
    echo "PASS: International details created.\n";
} else {
    echo "FAIL: International details not created.\n";
}

$stmt = $pdo->query("SELECT COUNT(*) FROM documents WHERE user_id = 4");
$count = $stmt->fetchColumn();

if ($count == 5) { // photo, id, passport, visa, marksheet
    echo "PASS: 5 documents records created.\n";
} else {
    echo "FAIL: Expected 5 documents, found $count.\n";
}

// Verify New Fields
if ($profile['course_applied'] === 'B.Tech' && $profile['previous_marks'] === '90%' && $profile['abc_id'] === 'ABC123456789') {
    echo "PASS: New academic fields saved correctly.\n";
} else {
    echo "FAIL: Academic fields mismatch.\n";
}
