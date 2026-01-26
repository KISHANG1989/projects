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
    <div class="col-12">
        <a href="verification_list.php" class="btn btn-secondary mb-3">&larr; Back to Queue</a>
        <h2>Application Details: <?php echo htmlspecialchars($profile['full_name']); ?></h2>

        <div class="card mb-4">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Program Applied:</strong> <?php echo htmlspecialchars($profile['course_applied'] ?? 'N/A'); ?></p>
                        <p><strong>DOB:</strong> <?php echo htmlspecialchars($profile['dob']); ?></p>
                        <p><strong>Nationality:</strong> <?php echo htmlspecialchars($profile['nationality']); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($profile['address'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Previous Marks:</strong> <?php echo htmlspecialchars($profile['previous_marks'] ?? 'N/A'); ?></p>
                        <p><strong>ABC ID:</strong> <?php echo htmlspecialchars($profile['abc_id'] ?? 'N/A'); ?></p>
                        <p><strong>Enrollment Status:</strong>
                            <span class="badge bg-<?php echo $profile['enrollment_status'] == 'Approved' ? 'success' : ($profile['enrollment_status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                <?php echo htmlspecialchars($profile['enrollment_status']); ?>
                            </span>
                        </p>
                         <?php if (!empty($profile['roll_number'])): ?>
                            <p><strong>Roll Number:</strong> <span class="badge bg-primary text-wrap"><?php echo htmlspecialchars($profile['roll_number']); ?></span></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($intl): ?>
                    <hr>
                    <h5>International Details</h5>
                    <p><strong>Passport:</strong> <?php echo htmlspecialchars($intl['passport_number']); ?></p>
                    <p><strong>Visa:</strong> <?php echo htmlspecialchars($intl['visa_details']); ?></p>
                    <p><strong>Origin:</strong> <?php echo htmlspecialchars($intl['country_of_origin']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h3>Documents</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Status</th>
                    <th>View</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['doc_type']))); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $doc['status'] == 'Verified' ? 'success' : ($doc['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                            <?php echo htmlspecialchars($doc['status']); ?>
                        </span>
                    </td>
                    <td><a href="/uploads/documents/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">Open File</a></td>
                    <td>
                        <?php if ($doc['status'] == 'Pending'): ?>
                        <form method="POST" action="verify_action.php" style="display:inline;">
                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                            <input type="hidden" name="action" value="verify_doc">
                            <button type="submit" class="btn btn-sm btn-success">Verify</button>
                        </form>
                        <form method="POST" action="verify_action.php" style="display:inline;">
                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                            <input type="hidden" name="action" value="reject_doc">
                            <div class="input-group input-group-sm mt-1">
                                <input type="text" name="remarks" class="form-control" placeholder="Reason for rejection">
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <?php if ($doc['status'] == 'Rejected' && !empty($doc['remarks'])): ?>
                                <small class="text-danger d-block">Reason: <?php echo htmlspecialchars($doc['remarks']); ?></small>
                            <?php endif; ?>
                            Action Taken
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-4">
            <h4>Final Decision</h4>
            <form method="POST" action="verify_action.php">
                <input type="hidden" name="profile_id" value="<?php echo $profile['id']; ?>">
                <input type="hidden" name="action" value="final_decision">
                <button type="submit" name="decision" value="Approved" class="btn btn-success btn-lg">Approve Admission</button>
                <button type="submit" name="decision" value="Rejected" class="btn btn-danger btn-lg">Reject Admission</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
