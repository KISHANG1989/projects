<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
if (!hasRole('registrar') && !hasRole('admin')) {
    redirect('/');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pdo = getDBConnection();

// Fetch Profile
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE id = ?");
$stmt->execute([$id]);
$profile = $stmt->fetch();

if (!$profile) {
    echo "Student not found.";
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="verification_list.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left me-2"></i>Back to Queue</a>
            <h2 class="fw-bold mb-0">Student Profile</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="view_profile_printable.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-outline-dark"><i class="fas fa-print me-2"></i>Print Application</a>
            <a href="view_documents.php?id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-file-check me-2"></i>View & Verify Documents</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> Action completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
             <span class="fw-bold"><i class="fas fa-cogs me-2"></i>Edit Permissions & Locking</span>
             <span class="badge bg-<?php echo $profile['is_form_locked'] ? 'danger' : 'success'; ?>">
                 <?php echo $profile['is_form_locked'] ? '<i class="fas fa-lock me-1"></i> Form Locked' : '<i class="fas fa-lock-open me-1"></i> Form Unlocked'; ?>
             </span>
        </div>
        <div class="card-body">
            <p class="text-muted small">Select sections to allow the student to edit. Unlocking the form will set the status to "Correction Required". Approving changes will re-lock the form.</p>

            <form method="POST" action="verify_action.php">
                <input type="hidden" name="action" value="manage_permissions">
                <input type="hidden" name="profile_id" value="<?php echo $id; ?>">

                <?php
                    $perms = json_decode($profile['edit_permissions'] ?? '[]', true);
                ?>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">Grant Edit Access:</label>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="form-check p-3 border rounded bg-light">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="basic" id="permBasic" <?php echo in_array('basic', $perms) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="permBasic">Basic & Academic</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check p-3 border rounded bg-light">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="personal" id="permPersonal" <?php echo in_array('personal', $perms) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="permPersonal">Personal Details</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check p-3 border rounded bg-light">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="family" id="permFamily" <?php echo in_array('family', $perms) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="permFamily">Family Details</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check p-3 border rounded bg-light">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="uploads" id="permUploads" <?php echo in_array('uploads', $perms) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="permUploads">Uploads</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="sub_action" value="unlock" class="btn btn-warning text-dark fw-bold">
                        <i class="fas fa-unlock me-2"></i> Update Permissions & Unlock
                    </button>

                    <button type="submit" name="sub_action" value="approve" class="btn btn-success fw-bold">
                        <i class="fas fa-check-double me-2"></i> Approve Changes & Lock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 text-center">
         <iframe src="view_profile_printable.php?id=<?php echo $id; ?>" style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;" title="Profile Preview"></iframe>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
