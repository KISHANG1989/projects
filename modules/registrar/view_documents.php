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

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0">
            <span class="text-muted fw-normal">Verification:</span>
            <?php echo htmlspecialchars($profile['full_name']); ?>
        </h4>
        <small class="text-muted">
            Program: <?php echo htmlspecialchars($profile['course_applied']); ?> |
            ID: #<?php echo htmlspecialchars($profile['id']); ?>
        </small>
    </div>
    <div>
        <a href="view_student.php?id=<?php echo $id; ?>" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Profile
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="doc-preview-container shadow-inner">
            <h5 class="mb-4 sticky-top bg-light p-2 border-bottom">Uploaded Documents (<?php echo count($documents); ?>)</h5>

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
