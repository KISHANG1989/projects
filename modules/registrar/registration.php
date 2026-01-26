<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Check if profile exists
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if ($profile) {
    // DASHBOARD MODE
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $documents = $stmt->fetchAll();
    ?>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold">My Application Dashboard</h2>
                <p class="text-muted">Manage your application and documents.</p>
            </div>
            <div class="col-md-4 text-end">
                <?php
                    $statusClass = match($profile['enrollment_status']) {
                        'Approved' => 'bg-success',
                        'Rejected' => 'bg-danger',
                        default => 'bg-warning text-dark'
                    };
                ?>
                <div class="card <?php echo $statusClass; ?> text-white shadow-sm">
                    <div class="card-body py-2 text-center">
                        <small class="d-block opacity-75">Status</small>
                        <span class="fw-bold fs-5"><?php echo htmlspecialchars($profile['enrollment_status']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> Document updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Profile Details</h5>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Name</span>
                                <span class="fw-bold"><?php echo htmlspecialchars($profile['full_name']); ?></span>
                            </li>
                             <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Program</span>
                                <span><?php echo htmlspecialchars($profile['course_applied']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Roll Number</span>
                                <span class="font-monospace text-primary"><?php echo htmlspecialchars($profile['roll_number'] ?? 'Pending'); ?></span>
                            </li>
                             <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Submission Date</span>
                                <span><?php echo htmlspecialchars($profile['created_at'] ?? 'N/A'); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold">My Documents</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Document</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-capitalize fw-bold d-block text-secondary"><?php echo str_replace('_', ' ', $doc['doc_type']); ?></span>
                                            <?php if ($doc['status'] == 'Rejected' && !empty($doc['remarks'])): ?>
                                                <small class="text-danger"><i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars($doc['remarks']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                                $badgeClass = match($doc['status']) {
                                                    'Verified' => 'bg-success',
                                                    'Rejected' => 'bg-danger',
                                                    default => 'bg-warning text-dark'
                                                };
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> rounded-pill"><?php echo htmlspecialchars($doc['status']); ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($doc['status'] === 'Verified'): ?>
                                                <span class="text-muted" title="Document Locked"><i class="fas fa-lock"></i> Locked</span>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#replaceDoc<?php echo $doc['id']; ?>">
                                                    <i class="fas fa-sync-alt me-1"></i> Replace
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr class="collapse bg-light" id="replaceDoc<?php echo $doc['id']; ?>">
                                        <td colspan="3" class="p-3">
                                            <form action="registration_handler.php" method="POST" enctype="multipart/form-data" class="row g-2 align-items-center">
                                                <input type="hidden" name="action" value="update_doc">
                                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                <div class="col-auto">
                                                    <label class="small text-muted">Upload new file (Max 200KB):</label>
                                                </div>
                                                <div class="col">
                                                    <input type="file" name="document" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-auto">
                                                    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    // REGISTRATION FORM MODE (Existing Code)
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold">Student Admission Form</h2>
        <p class="text-muted">Fill out the details below to apply for your program.</p>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> Application submitted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-lg">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <ul class="nav nav-pills nav-fill p-1 bg-light rounded" id="studentTypeTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded fw-bold" id="indian-tab" data-bs-toggle="tab" data-bs-target="#indian" type="button" role="tab" onclick="setNationality('Indian')">
                    <i class="fas fa-flag-checkered me-2"></i> Indian Student
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded fw-bold" id="international-tab" data-bs-toggle="tab" data-bs-target="#international" type="button" role="tab" onclick="setNationality('International')">
                    <i class="fas fa-globe me-2"></i> International Student
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <form method="POST" action="registration_handler.php" enctype="multipart/form-data">
            <input type="hidden" name="nationality" id="nationalityField" value="Indian">

            <h5 class="text-primary fw-bold mb-3"><i class="fas fa-user me-2"></i>Personal Details</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" name="full_name" class="form-control" id="fullName" placeholder="John Doe" required>
                        <label for="fullName">Full Name</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="date" name="dob" class="form-control" id="dob" required>
                        <label for="dob">Date of Birth</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea name="address" class="form-control" id="address" style="height: 100px" placeholder="Address" required></textarea>
                        <label for="address">Permanent Address</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select name="category" class="form-select" id="category">
                            <option value="General">General</option>
                            <option value="OBC">OBC</option>
                            <option value="SC/ST">SC/ST</option>
                            <option value="Other">Other</option>
                        </select>
                        <label for="category">Category</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select name="course_applied" class="form-select" id="course" required>
                            <option value="">Select Program</option>
                            <option value="B.Tech">B.Tech</option>
                            <option value="BSc">B.Sc</option>
                            <option value="BBA">BBA</option>
                            <option value="MBA">MBA</option>
                            <option value="M.Tech">M.Tech</option>
                            <option value="PhD">PhD</option>
                        </select>
                        <label for="course">Program Applied For</label>
                    </div>
                </div>
            </div>

            <h5 class="text-primary fw-bold mb-3"><i class="fas fa-graduation-cap me-2"></i>Academic Details</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" name="previous_marks" class="form-control" id="prevMarks" placeholder="e.g., 85%" required>
                        <label for="prevMarks">Previous Academic Performance (CGPA / %)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" name="abc_id" class="form-control" id="abcId" placeholder="12-digit ABC ID">
                        <label for="abcId">NEP ABC ID</label>
                    </div>
                </div>
            </div>

            <!-- International Fields -->
            <div id="internationalFields" style="display:none;" class="mb-4 p-3 bg-light rounded border">
                <h5 class="text-info fw-bold mb-3"><i class="fas fa-plane me-2"></i>International Details</h5>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="country_of_origin" class="form-control" id="country" placeholder="Country">
                            <label for="country">Country of Origin</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="passport_number" class="form-control" id="passport" placeholder="Passport">
                            <label for="passport">Passport Number</label>
                        </div>
                    </div>
                     <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" name="visa_details" class="form-control" id="visa" placeholder="Visa">
                            <label for="visa">Visa Details</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Passport Copy (Max 200KB)</label>
                        <input type="file" name="passport_copy" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Visa Copy (Max 200KB)</label>
                        <input type="file" name="visa_copy" class="form-control">
                    </div>
                </div>
            </div>

            <h5 class="text-primary fw-bold mb-3"><i class="fas fa-file-upload me-2"></i>Documents</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Photo (Max 200KB) <span class="text-danger">*</span></label>
                    <input type="file" name="photo" class="form-control" required>
                </div>
                 <div class="col-md-4">
                    <label class="form-label">ID Proof (Aadhar/PAN) (Max 200KB) <span class="text-danger">*</span></label>
                    <input type="file" name="id_proof" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Previous Marksheet (Max 200KB) <span class="text-danger">*</span></label>
                    <input type="file" name="previous_marksheet" class="form-control" required>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<script>
    function setNationality(type) {
        document.getElementById('nationalityField').value = type;
        const intlFields = document.getElementById('internationalFields');
        if (type === 'International') {
            intlFields.style.display = 'block';
        } else {
            intlFields.style.display = 'none';
        }
    }
</script>

<?php
} // End Else
require_once __DIR__ . '/../../includes/footer.php';
?>
