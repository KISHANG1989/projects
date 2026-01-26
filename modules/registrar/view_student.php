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

// Fetch International Details
$stmt = $pdo->prepare("SELECT * FROM international_details WHERE student_profile_id = ?");
$stmt->execute([$id]);
$intl = $stmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <a href="verification_list.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Back to Queue</a>
            <h2 class="fw-bold mb-1">Student Profile</h2>
            <p class="text-muted">Review student details before verifying documents.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="view_documents.php?id=<?php echo $id; ?>" class="btn btn-primary btn-lg shadow-sm">
                <i class="fas fa-file-check me-2"></i> View & Verify Documents
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Profile Summary -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 100px; height: 100px;">
                        <i class="fas fa-user fa-4x text-secondary"></i>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($profile['full_name']); ?></h3>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($profile['course_applied'] ?? 'N/A'); ?></p>

                    <span class="badge bg-<?php echo $profile['enrollment_status'] == 'Approved' ? 'success' : ($profile['enrollment_status'] == 'Rejected' ? 'danger' : 'warning'); ?> fs-6 px-3 py-2 mb-3">
                        <?php echo htmlspecialchars($profile['enrollment_status']); ?>
                    </span>

                    <?php if (!empty($profile['roll_number'])): ?>
                        <div class="bg-light rounded p-3 border mt-2">
                            <small class="text-muted d-block text-uppercase fw-bold">Roll Number</small>
                            <span class="fs-5 fw-bold text-primary font-monospace"><?php echo htmlspecialchars($profile['roll_number']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detailed Info -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3"><i class="fas fa-info-circle me-2"></i>Personal Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Date of Birth</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($profile['dob']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Nationality</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($profile['nationality']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Category</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($profile['category']); ?></span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Address</small>
                            <span class="fw-bold"><?php echo nl2br(htmlspecialchars($profile['address'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3"><i class="fas fa-graduation-cap me-2"></i>Academic Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Previous Marks</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($profile['previous_marks'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">NEP ABC ID</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($profile['abc_id'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($intl): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white fw-bold py-3"><i class="fas fa-globe me-2"></i>International Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Passport Number</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($intl['passport_number']); ?></span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Visa Details</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($intl['visa_details']); ?></span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Country of Origin</small>
                            <span class="fw-bold"><?php echo htmlspecialchars($intl['country_of_origin']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Final Decision Block moved from Documents Panel to here -->
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Final Decision</h5>
                    <p class="text-muted mb-4">Ensure all documents are verified in the <a href="view_documents.php?id=<?php echo $id; ?>">Document View</a> before making a final decision.</p>
                    <form method="POST" action="verify_action.php" class="d-flex gap-3">
                        <input type="hidden" name="profile_id" value="<?php echo $profile['id']; ?>">
                        <input type="hidden" name="action" value="final_decision">
                        <button type="submit" name="decision" value="Approved" class="btn btn-success flex-grow-1 py-2 fw-bold">
                            <i class="fas fa-user-check me-2"></i> Approve Admission
                        </button>
                        <button type="submit" name="decision" value="Rejected" class="btn btn-danger flex-grow-1 py-2 fw-bold">
                            <i class="fas fa-user-times me-2"></i> Reject Admission
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
