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

<div class="row">
    <div class="col-md-4 mb-4">
        <a href="verification_list.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Back to Queue</a>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center">
                <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-user fa-3x text-secondary"></i>
                </div>
                <h4 class="fw-bold"><?php echo htmlspecialchars($profile['full_name']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($profile['course_applied'] ?? 'N/A'); ?></p>

                <span class="badge bg-<?php echo $profile['enrollment_status'] == 'Approved' ? 'success' : ($profile['enrollment_status'] == 'Rejected' ? 'danger' : 'warning'); ?> fs-6 px-3 py-2">
                    <?php echo htmlspecialchars($profile['enrollment_status']); ?>
                </span>

                <?php if (!empty($profile['roll_number'])): ?>
                    <div class="mt-3 p-2 bg-light rounded border">
                        <small class="text-muted d-block">Roll Number</small>
                        <span class="fw-bold text-primary font-monospace"><?php echo htmlspecialchars($profile['roll_number']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Personal Details</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">DOB</span>
                        <span><?php echo htmlspecialchars($profile['dob']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Nationality</span>
                        <span><?php echo htmlspecialchars($profile['nationality']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Category</span>
                        <span><?php echo htmlspecialchars($profile['category']); ?></span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1">Address</span>
                        <span><?php echo nl2br(htmlspecialchars($profile['address'])); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-bold">Academic Details</div>
            <div class="card-body">
                 <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Prev. Marks</span>
                        <span><?php echo htmlspecialchars($profile['previous_marks'] ?? 'N/A'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">ABC ID</span>
                        <span><?php echo htmlspecialchars($profile['abc_id'] ?? 'N/A'); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php if ($intl): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white fw-bold"><i class="fas fa-globe me-2"></i>International Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Passport Number</small>
                        <p class="fw-bold"><?php echo htmlspecialchars($intl['passport_number']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Visa Details</small>
                        <p class="fw-bold"><?php echo htmlspecialchars($intl['visa_details']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Country of Origin</small>
                        <p class="fw-bold"><?php echo htmlspecialchars($intl['country_of_origin']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>Document Verification</span>
                <span class="badge bg-secondary"><?php echo count($documents); ?> Files</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Document Type</th>
                                <th>Status</th>
                                <th>View</th>
                                <th class="pe-4 text-end">Verification Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="ps-4 text-capitalize fw-bold text-secondary"><?php echo htmlspecialchars(str_replace('_', ' ', $doc['doc_type'])); ?></td>
                                <td>
                                    <?php
                                        $statusClass = match($doc['status']) {
                                            'Verified' => 'badge-verified',
                                            'Rejected' => 'badge-rejected',
                                            default => 'badge-pending'
                                        };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?> rounded-pill">
                                        <?php echo htmlspecialchars($doc['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/uploads/documents/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-external-link-alt"></i> Open
                                    </a>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($doc['status'] == 'Pending'): ?>
                                    <div class="d-flex justify-content-end gap-2">
                                        <form method="POST" action="verify_action.php">
                                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                            <input type="hidden" name="action" value="verify_doc">
                                            <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#rejectReason<?php echo $doc['id']; ?>" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="collapse mt-2" id="rejectReason<?php echo $doc['id']; ?>">
                                        <form method="POST" action="verify_action.php" class="input-group input-group-sm">
                                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                            <input type="hidden" name="action" value="reject_doc">
                                            <input type="text" name="remarks" class="form-control" placeholder="Reason..." required>
                                            <button type="submit" class="btn btn-danger">Confirm</button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                        <?php if ($doc['status'] == 'Rejected' && !empty($doc['remarks'])): ?>
                                            <small class="text-danger d-block"><i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars($doc['remarks']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fas fa-check-double"></i> Processed</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Final Decision</h5>
                <p class="text-muted mb-4">Once all documents are verified, approve the student to generate an enrollment number.</p>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
