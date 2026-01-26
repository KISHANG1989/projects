<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDBConnection();

    if (isset($_POST['action']) && $_POST['action'] === 'update_doc') {
        try {
            $doc_id = (int)$_POST['doc_id'];
            $user_id = $_SESSION['user_id'];

            // Validate Document Ownership and Status
            $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
            $stmt->execute([$doc_id, $user_id]);
            $doc = $stmt->fetch();

            if (!$doc) {
                throw new Exception("Document not found.");
            }
            if ($doc['status'] === 'Verified') {
                throw new Exception("Cannot replace verified documents.");
            }

            // Upload Logic
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['document']['size'] > 200 * 1024) {
                     throw new Exception("File too large. Max 200KB.");
                }

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                $tmp_name = $_FILES['document']['tmp_name'];
                $name = basename($_FILES['document']['name']);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_extensions)) {
                    throw new Exception("Invalid file type.");
                }

                // Delete old file
                $old_file = __DIR__ . '/../../uploads/documents/' . $doc['file_path'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }

                $new_name = $user_id . '_' . $doc['doc_type'] . '_' . time() . '.' . $ext;
                $target = __DIR__ . '/../../uploads/documents/' . $new_name;

                if (move_uploaded_file($tmp_name, $target)) {
                    $stmt = $pdo->prepare("UPDATE documents SET file_path = ?, status = 'Pending', remarks = NULL WHERE id = ?");
                    $stmt->execute([$new_name, $doc_id]);
                    redirect('/modules/registrar/registration.php?success=1');
                } else {
                    throw new Exception("Upload failed.");
                }
            } else {
                throw new Exception("No file selected.");
            }

        } catch (Exception $e) {
            redirect('/modules/registrar/registration.php?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    // Basic Details
    $user_id = $_SESSION['user_id'];
    $full_name = sanitize($_POST['full_name']);
    $dob = sanitize($_POST['dob']);
    $address = sanitize($_POST['address']);
    $nationality = sanitize($_POST['nationality']); // Indian or International
    $category = sanitize($_POST['category']);
    $course_applied = sanitize($_POST['course_applied']);
    $previous_marks = sanitize($_POST['previous_marks']);
    $abc_id = isset($_POST['abc_id']) ? sanitize($_POST['abc_id']) : null;

    try {
        $pdo->beginTransaction();

        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Profile already exists.");
        }

        // Insert Profile
        $created_at = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, full_name, dob, address, nationality, category, course_applied, previous_marks, abc_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $dob, $address, $nationality, $category, $course_applied, $previous_marks, $abc_id, $created_at]);
        $profile_id = $pdo->lastInsertId();

        // International Details
        if ($nationality === 'International') {
            $passport = sanitize($_POST['passport_number']);
            $visa = sanitize($_POST['visa_details']);
            $country = sanitize($_POST['country_of_origin']);

            $stmt = $pdo->prepare("INSERT INTO international_details (student_profile_id, passport_number, visa_details, country_of_origin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$profile_id, $passport, $visa, $country]);
        }

        // File Uploads
        $upload_dir = __DIR__ . '/../../uploads/documents/';

        $required_docs = ['photo', 'id_proof', 'previous_marksheet'];
        if ($nationality === 'International') {
            $required_docs[] = 'passport_copy';
            $required_docs[] = 'visa_copy';
        }

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

        foreach ($required_docs as $doc_key) {
            if (isset($_FILES[$doc_key]) && $_FILES[$doc_key]['error'] === UPLOAD_ERR_OK) {
                if ($_FILES[$doc_key]['size'] > 200 * 1024) {
                    throw new Exception("File $doc_key too large. Max 200KB.");
                }
                $tmp_name = $_FILES[$doc_key]['tmp_name'];
                $name = basename($_FILES[$doc_key]['name']);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_extensions)) {
                    throw new Exception("Invalid file type for $doc_key. Allowed: " . implode(', ', $allowed_extensions));
                }

                $new_name = $user_id . '_' . $doc_key . '_' . time() . '.' . $ext;
                $target = $upload_dir . $new_name;

                $uploaded = false;
                if (defined('TEST_MODE') && TEST_MODE) {
                    $uploaded = copy($tmp_name, $target);
                } else {
                    $uploaded = move_uploaded_file($tmp_name, $target);
                }

                if ($uploaded) {
                    $stmt = $pdo->prepare("INSERT INTO documents (user_id, doc_type, file_path, status) VALUES (?, ?, ?, 'Pending')");
                    $stmt->execute([$user_id, $doc_key, $new_name]);
                } else {
                    throw new Exception("Failed to upload " . $doc_key);
                }
            }
        }

        $pdo->commit();
        redirect('/modules/registrar/registration.php?success=1');

    } catch (Exception $e) {
        $pdo->rollBack();
        redirect('/modules/registrar/registration.php?error=' . urlencode($e->getMessage()));
    }
}
