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
        $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : null;

        $stmt = $pdo->prepare("UPDATE documents SET status = ?, remarks = ? WHERE id = ?");
        $stmt->execute([$status, $remarks, $doc_id]);

        if (isset($_SERVER['HTTP_REFERER'])) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect('verification_list.php');
        }
    }
    elseif ($action === 'delete_doc') {
        $doc_id = (int)$_POST['doc_id'];

        // Get file path
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        $stmt->execute([$doc_id]);
        $doc = $stmt->fetch();

        if ($doc) {
            $filepath = __DIR__ . '/../../uploads/documents/' . $doc['file_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$doc_id]);
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect('verification_list.php');
        }
    }
    elseif ($action === 'replace_doc') {
        $doc_id = (int)$_POST['doc_id'];

        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            // Fetch doc to get user_id and doc_type
            $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
            $stmt->execute([$doc_id]);
            $doc = $stmt->fetch();

            if ($doc) {
                // Remove old file
                $old_file = __DIR__ . '/../../uploads/documents/' . $doc['file_path'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }

                // Upload new
                $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                $new_name = $doc['user_id'] . '_' . $doc['doc_type'] . '_' . time() . '.' . $ext;
                $target = __DIR__ . '/../../uploads/documents/' . $new_name;

                if (move_uploaded_file($_FILES['document']['tmp_name'], $target)) {
                    $stmt = $pdo->prepare("UPDATE documents SET file_path = ?, status = 'Pending', remarks = NULL WHERE id = ?");
                    $stmt->execute([$new_name, $doc_id]);
                }
            }
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect('verification_list.php');
        }
    }
    elseif ($action === 'final_decision') {
        $profile_id = (int)$_POST['profile_id'];
        $decision = $_POST['decision']; // Approved or Rejected

        if ($decision === 'Approved') {
            // Generate Enrollment Number: ENR-{YEAR}-{ID}
            $year = date('Y');
            $roll_number = sprintf("ENR-%s-%03d", $year, $profile_id);

            // Also lock the form if approved
            $stmt = $pdo->prepare("UPDATE student_profiles SET enrollment_status = ?, roll_number = ?, is_form_locked = 1, edit_permissions = NULL WHERE id = ?");
            $stmt->execute([$decision, $roll_number, $profile_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE student_profiles SET enrollment_status = ? WHERE id = ?");
            $stmt->execute([$decision, $profile_id]);
        }

        redirect($_SERVER['HTTP_REFERER']);
    }
    elseif ($action === 'manage_permissions') {
        $profile_id = (int)$_POST['profile_id'];
        $sub_action = $_POST['sub_action'];

        if ($sub_action === 'unlock') {
            $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
            // Unlock form, set permissions, set status to indicate needs correction?
            // The requirement says "Authorisation and approval option for that specific data and lock the form again".
            // So we unlock specific fields.
            $stmt = $pdo->prepare("UPDATE student_profiles SET is_form_locked = 0, edit_permissions = ? WHERE id = ?");
            $stmt->execute([$permissions, $profile_id]);
        }
        elseif ($sub_action === 'approve') {
            // Lock form, clear permissions
            $stmt = $pdo->prepare("UPDATE student_profiles SET is_form_locked = 1, edit_permissions = NULL WHERE id = ?");
            $stmt->execute([$profile_id]);
        }

        redirect('view_student.php?id=' . $profile_id);
    }
}
