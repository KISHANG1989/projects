<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

// Ensure only registrar/admin can access
if (!hasRole('registrar') && !hasRole('admin')) {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDBConnection();

    $student_id = (int)$_POST['student_id'];
    $new_status = sanitize($_POST['status']);
    $remarks = sanitize($_POST['remarks']);
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Get current status
        $stmt = $pdo->prepare("SELECT student_status FROM student_profiles WHERE id = ?");
        $stmt->execute([$student_id]);
        $current = $stmt->fetch();

        if (!$current) {
            throw new Exception("Student profile not found.");
        }

        $old_status = $current['student_status'];

        if ($old_status !== $new_status) {
            // Update Profile
            $upd = $pdo->prepare("UPDATE student_profiles SET student_status = ? WHERE id = ?");
            $upd->execute([$new_status, $student_id]);

            // Log Change
            $log = $pdo->prepare("INSERT INTO student_status_logs (student_profile_id, old_status, new_status, changed_by, remarks) VALUES (?, ?, ?, ?, ?)");
            $log->execute([$student_id, $old_status, $new_status, $user_id, $remarks]);
        }

        $pdo->commit();

        // Redirect back
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=StatusUpdated");
        } else {
             header("Location: verification_list.php");
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error updating status: " . $e->getMessage());
    }
}
