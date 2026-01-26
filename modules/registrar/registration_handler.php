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

            // Check Profile Lock Status
            $pStmt = $pdo->prepare("SELECT is_form_locked, edit_permissions FROM student_profiles WHERE user_id = ?");
            $pStmt->execute([$user_id]);
            $prof = $pStmt->fetch();

            if ($prof && $prof['is_form_locked'] && $doc['status'] !== 'Rejected') {
                $perms = json_decode($prof['edit_permissions'] ?? '[]', true);
                if (!in_array('uploads', $perms)) {
                     throw new Exception("Application is locked. You cannot replace pending documents.");
                }
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

    // Check if updating existing profile (Edit Mode)
    $user_id = $_SESSION['user_id'];
    $edit_mode = false;
    // We need all current data for partial update
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing_profile = $stmt->fetch();

    if ($existing_profile) {
        if ($existing_profile['is_form_locked'] && empty($existing_profile['edit_permissions'])) {
            redirect('/modules/registrar/registration.php?error=' . urlencode("Form is locked."));
        }
        $edit_mode = true;
        $profile_id = $existing_profile['id'];
    }

    // Extract POST Data
    $post_full_name = sanitize($_POST['full_name']);
    $post_dob = sanitize($_POST['dob']);
    $post_address = sanitize($_POST['address']);
    $post_nationality = sanitize($_POST['nationality']);
    $post_category = sanitize($_POST['category']);
    $post_course_applied = sanitize($_POST['course_applied']);
    $post_previous_marks = sanitize($_POST['previous_marks']);
    $post_abc_id = isset($_POST['abc_id']) ? sanitize($_POST['abc_id']) : null;

    $post_extended_data = [
        'family' => [
            'father_name' => sanitize($_POST['father_name'] ?? ''),
            'mother_name' => sanitize($_POST['mother_name'] ?? ''),
            'guardian_contact' => sanitize($_POST['guardian_contact'] ?? '')
        ],
        'awards' => sanitize($_POST['awards'] ?? ''),
        'nep_details' => sanitize($_POST['nep_details'] ?? ''),
        'regulatory' => [
            'anti_ragging' => sanitize($_POST['anti_ragging'] ?? ''),
            'declaration' => 'Agreed'
        ]
    ];

    try {
        $pdo->beginTransaction();

        if ($edit_mode) {
             // Secure Partial Update Logic
             $perms = json_decode($existing_profile['edit_permissions'] ?? '[]', true);
             $is_locked = (bool)$existing_profile['is_form_locked'];

             // If unlocked completely (new draft or full unlock), allow all.
             // If locked but has permissions, restrict.
             // If unlocked (0) and permissions is set, restrict (Registrar Correction Mode).

             // Check if permissions logic applies
             $restrict = false;
             if ($is_locked) {
                 $restrict = true;
             } elseif (!empty($perms)) {
                 $restrict = true; // Unlocked for specific corrections
             }

             // Helper to check permission
             $can_edit = function($group) use ($restrict, $perms) {
                 if (!$restrict) return true; // Full access
                 return in_array($group, $perms);
             };

             // Determine new values
             $new_full_name = $can_edit('personal') ? $post_full_name : $existing_profile['full_name'];
             $new_dob = $can_edit('personal') ? $post_dob : $existing_profile['dob'];
             $new_address = $can_edit('personal') ? $post_address : $existing_profile['address'];
             $new_nationality = $can_edit('personal') ? $post_nationality : $existing_profile['nationality'];
             $new_category = $can_edit('personal') ? $post_category : $existing_profile['category'];

             $new_course_applied = $can_edit('basic') ? $post_course_applied : $existing_profile['course_applied'];
             $new_previous_marks = $can_edit('basic') ? $post_previous_marks : $existing_profile['previous_marks'];
             $new_abc_id = $can_edit('basic') ? $post_abc_id : $existing_profile['abc_id'];

             // Extended Data Merging
             $current_extended = json_decode($existing_profile['extended_data'] ?? '{}', true);
             $new_extended = $current_extended;

             if ($can_edit('family')) {
                 $new_extended['family'] = $post_extended_data['family'];
             }
             // NEP details? Put in basic for now as discussed
             if ($can_edit('basic')) {
                 $new_extended['awards'] = $post_extended_data['awards'];
                 $new_extended['nep_details'] = $post_extended_data['nep_details'];
                 $new_extended['regulatory'] = $post_extended_data['regulatory'];
             }

             $new_extended_json = json_encode($new_extended);

             $stmt = $pdo->prepare("UPDATE student_profiles SET full_name=?, dob=?, address=?, nationality=?, category=?, course_applied=?, previous_marks=?, abc_id=?, extended_data=? WHERE id=?");
             $stmt->execute([$new_full_name, $new_dob, $new_address, $new_nationality, $new_category, $new_course_applied, $new_previous_marks, $new_abc_id, $new_extended_json, $profile_id]);

        } else {
             // New Registration
            $created_at = date('Y-m-d H:i:s');
            $app_no = 'APP-' . date('Y') . '-' . mt_rand(10000, 99999);
            $extended_json = json_encode($post_extended_data);

            $stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, full_name, dob, address, nationality, category, course_applied, previous_marks, abc_id, created_at, application_no, extended_data, is_form_locked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$user_id, $post_full_name, $post_dob, $post_address, $post_nationality, $post_category, $post_course_applied, $post_previous_marks, $post_abc_id, $created_at, $app_no, $extended_json]);
            $profile_id = $pdo->lastInsertId();

            // International Details
            if ($post_nationality === 'International') {
                $passport = sanitize($_POST['passport_number']);
                $visa = sanitize($_POST['visa_details']);
                $country = sanitize($_POST['country_of_origin']);

                $stmt = $pdo->prepare("INSERT INTO international_details (student_profile_id, passport_number, visa_details, country_of_origin) VALUES (?, ?, ?, ?)");
                $stmt->execute([$profile_id, $passport, $visa, $country]);
            }
        }

        // File Uploads (Check 'uploads' permission if editing)
        $upload_dir = __DIR__ . '/../../uploads/documents/';

        // If edit mode, check permission for uploads
        // Actually, file uploads are handled partly in 'update_doc' action above (replacements).
        // But for *new* uploads (if any added later to form) or initial uploads:
        // The wizard submits files in step 5.
        // If editing, user might re-upload files via the main form (if I allowed it in UI).
        // In current UI, files are in step 5.
        // If 'uploads' permission is ON, we allow overwriting.

        $process_uploads = true;
        if ($edit_mode) {
             $perms = json_decode($existing_profile['edit_permissions'] ?? '[]', true);
             if ($existing_profile['is_form_locked'] || !empty($perms)) {
                 if (!in_array('uploads', $perms)) {
                     $process_uploads = false;
                 }
             }
        }

        if ($process_uploads) {
            $required_docs = ['photo', 'signature', 'id_proof', 'previous_marksheet'];
            $nat = $edit_mode ? $new_nationality : $post_nationality; // Use determind nationality

            if ($nat === 'International') {
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
                        throw new Exception("Invalid file type for $doc_key.");
                    }

                    $new_name = $user_id . '_' . $doc_key . '_' . time() . '.' . $ext;
                    $target = $upload_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $target)) {
                        // Check if doc exists
                        $check = $pdo->prepare("SELECT id FROM documents WHERE user_id = ? AND doc_type = ?");
                        $check->execute([$user_id, $doc_key]);
                        if ($check->fetch()) {
                            // Update
                            $upd = $pdo->prepare("UPDATE documents SET file_path = ?, status = 'Pending' WHERE user_id = ? AND doc_type = ?");
                            $upd->execute([$new_name, $user_id, $doc_key]);
                        } else {
                            // Insert
                            $ins = $pdo->prepare("INSERT INTO documents (user_id, doc_type, file_path, status) VALUES (?, ?, ?, 'Pending')");
                            $ins->execute([$user_id, $doc_key, $new_name]);
                        }
                    }
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
