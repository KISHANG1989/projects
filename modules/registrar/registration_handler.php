<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDBConnection();

    // Basic Details
    $user_id = $_SESSION['user_id'];
    $full_name = sanitize($_POST['full_name']);
    $dob = sanitize($_POST['dob']);
    $address = sanitize($_POST['address']);
    $nationality = sanitize($_POST['nationality']); // Indian or International
    $category = sanitize($_POST['category']);

    try {
        $pdo->beginTransaction();

        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Profile already exists.");
        }

        // Insert Profile
        $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, full_name, dob, address, nationality, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $dob, $address, $nationality, $category]);
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

        $required_docs = ['photo', 'id_proof'];
        if ($nationality === 'International') {
            $required_docs[] = 'passport_copy';
            $required_docs[] = 'visa_copy';
        }

        foreach ($required_docs as $doc_key) {
            if (isset($_FILES[$doc_key]) && $_FILES[$doc_key]['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES[$doc_key]['tmp_name'];
                $name = basename($_FILES[$doc_key]['name']);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
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
