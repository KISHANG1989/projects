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

// Fetch Documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ?");
$stmt->execute([$profile['user_id']]);
$documents = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    .doc-preview-container {
        height: 80vh;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 0.25rem;
    }
    .doc-card {
        transition: transform 0.2s;
    }
    .doc-img {
        max-height: 500px;
        width: auto;
        max-width: 100%;
        display: block;
        margin: 0 auto;
        cursor: zoom-in;
    }
    .pdf-frame {
        width: 100%;
        height: 500px;
        border: none;
    }
</style>

<div class="row g-0">
    <!-- Left Panel: Student Details (25%) -->
    <div class="col-md-3 pe-3" style="height: 85vh; overflow-y: auto;">
        <a href="verification_list.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="fas fa-arrow-left me-2"></i>Back</a>

        <!-- Profile Summary -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center">
                <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-2" style="width: 60px; height: 60px;">
                    <i class="fas fa-user fa-2x text-secondary"></i>
                </div>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($profile['full_name']); ?></h5>
                <p class="text-muted small mb-2"><?php echo htmlspecialchars($profile['course_applied'] ?? 'N/A'); ?></p>

                <span class="badge bg-<?php echo $profile['enrollment_status'] == 'Approved' ? 'success' : ($profile['enrollment_status'] == 'Rejected' ? 'danger' : 'warning'); ?> rounded-pill mb-2">
                    <?php echo htmlspecialchars($profile['enrollment_status']); ?>
                </span>

                <?php if (!empty($profile['roll_number'])): ?>
                    <div class="bg-light rounded p-1 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Roll Number</small>
                        <span class="fw-bold text-primary small font-monospace"><?php echo htmlspecialchars($profile['roll_number']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Personal Details -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2 small">Personal Details</div>
            <div class="card-body p-2">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-1">
                        <span class="text-muted">DOB</span>
                        <span><?php echo htmlspecialchars($profile['dob']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-1">
                        <span class="text-muted">Nationality</span>
                        <span><?php echo htmlspecialchars($profile['nationality']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-1">
                        <span class="text-muted">Category</span>
                        <span><?php echo htmlspecialchars($profile['category']); ?></span>
                    </li>
                    <li class="list-group-item px-0 py-1">
                        <span class="text-muted d-block">Address</span>
                        <span><?php echo nl2br(htmlspecialchars($profile['address'])); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Academic Details -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold py-2 small">Academic</div>
            <div class="card-body p-2">
                 <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-1">
                        <span class="text-muted">Prev. Marks</span>
                        <span><?php echo htmlspecialchars($profile['previous_marks'] ?? 'N/A'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-1">
                        <span class="text-muted">ABC ID</span>
                        <span><?php echo htmlspecialchars($profile['abc_id'] ?? 'N/A'); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <?php if ($intl): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-info text-white fw-bold py-2 small">International</div>
            <div class="card-body p-2">
                 <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 py-1">
                        <span class="text-muted d-block">Passport</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($intl['passport_number']); ?></span>
                    </li>
                     <li class="list-group-item px-0 py-1">
                        <span class="text-muted d-block">Visa</span>
                        <span><?php echo htmlspecialchars($intl['visa_details']); ?></span>
                    </li>
                    <li class="list-group-item px-0 py-1">
                        <span class="text-muted d-block">Origin</span>
                        <span><?php echo htmlspecialchars($intl['country_of_origin']); ?></span>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Decision Actions -->
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2">Decision</h6>
                <form method="POST" action="verify_action.php" class="d-grid gap-2">
                    <input type="hidden" name="profile_id" value="<?php echo $profile['id']; ?>">
                    <input type="hidden" name="action" value="final_decision">
                    <button type="submit" name="decision" value="Approved" class="btn btn-success btn-sm fw-bold">
                        <i class="fas fa-check me-1"></i> Approve
                    </button>
                    <button type="submit" name="decision" value="Rejected" class="btn btn-danger btn-sm fw-bold">
                        <i class="fas fa-times me-1"></i> Reject
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Panel: Document Previews (75%) -->
    <div class="col-md-9">
        <div class="doc-preview-container shadow-inner">
            <h5 class="mb-4 sticky-top bg-light p-2 border-bottom">Document Verification (<?php echo count($documents); ?>)</h5>

            <?php foreach ($documents as $doc): ?>
                <?php
                    $docUrl = "/uploads/documents/" . $doc['file_path'];
                    $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                    $isPdf = $ext === 'pdf';
                ?>
                <div class="card mb-5 shadow-sm doc-card" id="doc-<?php echo $doc['id']; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-uppercase text-primary me-2"><?php echo str_replace('_', ' ', $doc['doc_type']); ?></span>
                            <?php
                                $statusClass = match($doc['status']) {
                                    'Verified' => 'success',
                                    'Rejected' => 'danger',
                                    default => 'warning text-dark'
                                };
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $doc['status']; ?></span>
                        </div>
                        <div class="btn-group">
                            <a href="<?php echo $docUrl; ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Open in New Tab"><i class="fas fa-external-link-alt"></i></a>

                            <!-- Delete Button -->
                            <form method="POST" action="verify_action.php" class="d-inline" onsubmit="return confirm('Permanently delete this document?');">
                                <input type="hidden" name="action" value="delete_doc">
                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                <button class="btn btn-outline-danger btn-sm rounded-0" title="Delete"><i class="fas fa-trash-alt"></i></button>
                            </form>

                             <!-- Replace Button Trigger -->
                            <button class="btn btn-outline-primary btn-sm rounded-end" type="button" data-bs-toggle="collapse" data-bs-target="#replacePanel<?php echo $doc['id']; ?>" title="Replace">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Replace Panel -->
                    <div class="collapse bg-light border-bottom p-3" id="replacePanel<?php echo $doc['id']; ?>">
                         <form action="verify_action.php" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="action" value="replace_doc">
                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                            <input type="file" name="document" class="form-control form-control-sm" required>
                            <button type="submit" class="btn btn-primary btn-sm">Upload & Replace</button>
                        </form>
                    </div>

                    <div class="card-body p-0 bg-secondary bg-opacity-10 text-center">
                        <?php if ($isPdf): ?>
                            <iframe src="<?php echo $docUrl; ?>" class="pdf-frame"></iframe>
                        <?php else: ?>
                            <img src="<?php echo $docUrl; ?>" class="img-fluid doc-img my-3" onclick="window.open(this.src, '_blank')">
                        <?php endif; ?>
                    </div>

                    <div class="card-footer bg-white">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <?php if ($doc['status'] == 'Rejected' && !empty($doc['remarks'])): ?>
                                    <div class="alert alert-danger mb-0 py-1 small">
                                        <strong>Rejected:</strong> <?php echo htmlspecialchars($doc['remarks']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <form method="POST" action="verify_action.php" class="d-inline-flex gap-2">
                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">

                                    <?php if ($doc['status'] !== 'Verified'): ?>
                                    <button type="submit" name="action" value="verify_doc" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Verify
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($doc['status'] !== 'Rejected'): ?>
                                    <div class="input-group input-group-sm" style="width: 200px;">
                                        <input type="text" name="remarks" class="form-control" placeholder="Reject Reason">
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
