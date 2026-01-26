<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
if (!hasRole('registrar') && !hasRole('admin')) {
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDBConnection();
    $action = $_POST['action'];

    if ($action === 'verify_doc' || $action === 'reject_doc') {
        $doc_id = (int)$_POST['doc_id'];
        $status = ($action === 'verify_doc') ? 'Verified' : 'Rejected';

        $stmt = $pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
        $stmt->execute([$status, $doc_id]);

        // We need to redirect back. We don't have the student profile ID easily unless passed.
        // But we can get it from HTTP_REFERER or fetch user_id from doc then profile.
        // Let's rely on Referer for simplicity or redirect to list if fails.
        if (isset($_SERVER['HTTP_REFERER'])) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect('verification_list.php');
        }
    }
    elseif ($action === 'final_decision') {
        $profile_id = (int)$_POST['profile_id'];
        $decision = $_POST['decision']; // Approved or Rejected

        $stmt = $pdo->prepare("UPDATE student_profiles SET enrollment_status = ? WHERE id = ?");
        $stmt->execute([$decision, $profile_id]);

        redirect('verification_list.php');
    }
}
