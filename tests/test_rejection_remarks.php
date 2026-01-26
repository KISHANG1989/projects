<?php
define('TEST_MODE', true);

require_once __DIR__ . '/../includes/auth.php';

// Mock Session for Registrar
$_SESSION['user_id'] = 2; // registrar
$_SESSION['role'] = 'registrar';

// Setup Data: Insert a dummy document
$pdo = getDBConnection();
$pdo->exec("INSERT INTO documents (user_id, doc_type, file_path, status) VALUES (999, 'test_doc_reject', 'path/to/doc', 'Pending')");
$doc_id = $pdo->lastInsertId();

// Mock Request for Reject Document
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_REFERER'] = 'http://localhost/view_student.php';
$_POST = [
    'action' => 'reject_doc',
    'doc_id' => $doc_id,
    'remarks' => 'Image blurred'
];

// Include Handler
ob_start();
require __DIR__ . '/../modules/registrar/verify_action.php';
ob_end_clean();

// Check DB
$stmt = $pdo->prepare("SELECT status, remarks FROM documents WHERE id = ?");
$stmt->execute([$doc_id]);
$row = $stmt->fetch();

if ($row['status'] === 'Rejected' && $row['remarks'] === 'Image blurred') {
    echo "PASS: Document rejected with remarks.\n";
} else {
    echo "FAIL: Document status is {$row['status']} and remarks are {$row['remarks']}.\n";
}

// Clean up
$pdo->exec("DELETE FROM documents WHERE id = $doc_id");
