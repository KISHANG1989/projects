<?php
define('TEST_MODE', true);

require_once __DIR__ . '/../includes/auth.php';

// Mock Session for Registrar
$_SESSION['user_id'] = 2; // registrar
$_SESSION['role'] = 'registrar';

// Setup Data: Insert a dummy document
$pdo = getDBConnection();
$pdo->exec("INSERT INTO documents (user_id, doc_type, file_path, status) VALUES (999, 'test_doc', 'path/to/doc', 'Pending')");
$doc_id = $pdo->lastInsertId();

// Mock Request for Verify Document
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_REFERER'] = 'http://localhost/view_student.php';
$_POST = [
    'action' => 'verify_doc',
    'doc_id' => $doc_id
];

// Include Handler
// Since handler redirects, we need to handle it. 'redirect' function handles TEST_MODE.
ob_start();
require __DIR__ . '/../modules/registrar/verify_action.php';
ob_end_clean();

// Check DB
$stmt = $pdo->prepare("SELECT status FROM documents WHERE id = ?");
$stmt->execute([$doc_id]);
$status = $stmt->fetchColumn();

if ($status === 'Verified') {
    echo "PASS: Document status updated to Verified.\n";
} else {
    echo "FAIL: Document status is $status.\n";
}

// Clean up
$pdo->exec("DELETE FROM documents WHERE id = $doc_id");
