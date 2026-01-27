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

// Fetch Documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ?");
$stmt->execute([$profile['user_id']]);
$documents = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    /* Fixed Layout: Left 30%, Right 70% */
    .split-container {
        height: calc(100vh - 80px); /* Adjust based on header height */
        display: flex;
        overflow: hidden;
    }
    .left-panel {
        width: 30%;
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
        padding: 20px;
        overflow-y: auto;
    }
    .right-panel {
        width: 70%;
        padding: 20px;
        overflow-y: auto;
        background-color: #fff;
    }
    .doc-preview-frame {
        width: 100%;
        height: 500px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: #eee;
    }
</style>

<div class="container-fluid p-0">
    <!-- Top Action Bar -->
    <div class="bg-white border-bottom p-3 d-flex justify-content-between align-items-center shadow-sm" style="height: 70px;">
        <div class="d-flex align-items-center">
             <a href="view_student.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-sm me-3"><i class="fas fa-arrow-left me-2"></i>Back to Profile</a>
             <h5 class="mb-0 fw-bold">Verification: <?php echo htmlspecialchars($profile['application_no']); ?></h5>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning text-dark btn-sm fw-bold" onclick="alert('Notification sent to student email!')">
                <i class="fas fa-bell me-2"></i> Notify Student
            </button>

            <form method="POST" action="verify_action.php" class="d-inline">
                 <input type="hidden" name="action" value="final_decision">
                 <input type="hidden" name="profile_id" value="<?php echo $id; ?>">

                 <?php if(empty($profile['roll_number'])): ?>
                    <button type="submit" name="decision" value="Approved" class="btn btn-success btn-sm fw-bold" onclick="return confirm('Are you sure? This will generate the Enrollment Number and lock admission.');">
                        <i class="fas fa-id-card me-2"></i> Generate Enrollment No
                    </button>
                 <?php else: ?>
                    <button type="button" class="btn btn-secondary btn-sm fw-bold" disabled>
                         <i class="fas fa-check-circle me-2"></i> Enrolled: <?php echo htmlspecialchars($profile['roll_number']); ?>
                    </button>
                 <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Split Screen -->
    <div class="split-container">
        <!-- Left Panel: Basic Info -->
        <div class="left-panel">
            <div class="text-center mb-4">
                 <?php
                    // Try to find photo
                    $photoPath = null;
                    foreach($documents as $d) { if($d['doc_type'] == 'photo') $photoPath = $d['file_path']; }
                 ?>
                 <?php if($photoPath): ?>
                    <img src="/uploads/documents/<?php echo $photoPath; ?>" class="rounded shadow-sm" style="width: 120px; height: 150px; object-fit: cover;">
                 <?php else: ?>
                    <div class="bg-secondary text-white rounded shadow-sm d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 150px;">
                        No Photo
                    </div>
                 <?php endif; ?>
                 <h5 class="fw-bold mt-3 mb-1"><?php echo htmlspecialchars($profile['full_name']); ?></h5>
                 <p class="text-muted small"><?php echo htmlspecialchars($profile['course_applied']); ?></p>
            </div>

            <h6 class="fw-bold border-bottom pb-2 mb-3">Basic Details</h6>
            <dl class="row small mb-4">
                <dt class="col-sm-4 text-muted">DOB</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($profile['dob']); ?></dd>

                <dt class="col-sm-4 text-muted">Nationality</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($profile['nationality']); ?></dd>

                <dt class="col-sm-4 text-muted">Category</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($profile['category']); ?></dd>

                <dt class="col-sm-4 text-muted">Address</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($profile['address']); ?></dd>

                <dt class="col-sm-4 text-muted">Prev. Marks</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($profile['previous_marks']); ?></dd>

                <dt class="col-sm-4 text-muted">ABC ID</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($profile['abc_id']); ?></dd>
            </dl>
        </div>

        <!-- Right Panel: Documents -->
        <div class="right-panel">
            <h5 class="mb-4">Uploaded Documents (<?php echo count($documents); ?>)</h5>

            <?php foreach ($documents as $doc): ?>
                <?php
                    $docUrl = "/uploads/documents/" . $doc['file_path'];
                    $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                    $isPdf = $ext === 'pdf';
                    $downloadName = $profile['user_id'] . '-' . $doc['doc_type'] . '.' . $ext;
                ?>
                <div class="card mb-4 border shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase text-primary"><?php echo str_replace('_', ' ', $doc['doc_type']); ?></span>

                        <div class="d-flex align-items-center gap-2">
                             <?php
                                $statusBadge = match($doc['status']) {
                                    'Verified' => 'bg-success',
                                    'Rejected' => 'bg-danger',
                                    default => 'bg-warning text-dark'
                                };
                            ?>
                            <span class="badge <?php echo $statusBadge; ?> rounded-pill"><?php echo $doc['status']; ?></span>

                            <!-- Download -->
                            <a href="<?php echo $docUrl; ?>" download="<?php echo $downloadName; ?>" class="btn btn-light btn-sm border" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0 bg-light text-center">
                         <?php if ($isPdf): ?>
                            <iframe src="<?php echo $docUrl; ?>" class="doc-preview-frame"></iframe>
                        <?php else: ?>
                            <img src="<?php echo $docUrl; ?>" class="img-fluid my-3" style="max-height: 500px;" onclick="window.open(this.src, '_blank')">
                        <?php endif; ?>
                    </div>

                    <div class="card-footer bg-white">
                         <div class="row align-items-center">
                            <div class="col-md-6">
                                <?php if ($doc['status'] == 'Rejected' && !empty($doc['remarks'])): ?>
                                    <div class="alert alert-danger mb-0 py-1 small">
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($doc['remarks']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <form method="POST" action="verify_action.php" class="d-inline-flex gap-2">
                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">

                                    <?php if ($doc['status'] !== 'Verified'): ?>
                                    <button type="submit" name="action" value="verify_doc" class="btn btn-success btn-sm fw-bold">
                                        <i class="fas fa-check"></i> Verify
                                    </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="fas fa-lock"></i> Locked</button>
                                    <?php endif; ?>

                                    <?php if ($doc['status'] !== 'Verified' && $doc['status'] !== 'Rejected'): ?>
                                    <div class="input-group input-group-sm" style="width: 200px;">
                                        <input type="text" name="remarks" class="form-control" placeholder="Reason...">
                                        <button type="submit" name="action" value="reject_doc" class="btn btn-outline-danger">Reject</button>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
             <?php if(empty($documents)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-file-excel fa-3x mb-3"></i>
                    <p>No documents uploaded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
