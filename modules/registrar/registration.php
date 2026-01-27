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

    // Check for Rejected/Pending Documents for "Flasher"
    $hasRejected = false;
    $hasPending = false;
    foreach ($documents as $doc) {
        if ($doc['status'] === 'Rejected') $hasRejected = true;
        if ($doc['status'] === 'Pending') $hasPending = true;
    }

    $extended = json_decode($profile['extended_data'] ?? '{}', true);
    $permissions = json_decode($profile['edit_permissions'] ?? '[]', true);
    ?>

    <!-- Flasher Modal -->
    <?php if ($hasRejected): ?>
    <div class="modal fade show" id="alertModal" tabindex="-1" aria-modal="true" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Action Required</h5>
                </div>
                <div class="modal-body">
                    <p class="fw-bold">Some of your documents have been rejected.</p>
                    <p>Please review the remarks and upload valid documents immediately to proceed with your admission.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('alertModal').style.display='none'">I Understand</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold">My Application Dashboard</h2>
                <p class="text-muted">Application No: <span class="fw-bold text-primary"><?php echo htmlspecialchars($profile['application_no']); ?></span></p>
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
                        <small class="d-block opacity-75">Admission Status</small>
                        <span class="fw-bold fs-5"><?php echo htmlspecialchars($profile['enrollment_status']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Profile Overview -->
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">Profile Details</div>
                    <div class="card-body">
                         <div class="text-center mb-3">
                            <i class="fas fa-user-circle fa-4x text-secondary"></i>
                        </div>
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
                                <span class="text-muted">Father's Name</span>
                                <span><?php echo htmlspecialchars($extended['family']['father_name'] ?? '-'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Roll Number</span>
                                <span class="font-monospace text-primary"><?php echo htmlspecialchars($profile['roll_number'] ?? 'Pending'); ?></span>
                            </li>
                        </ul>

                        <?php if(!empty($profile['edit_permissions'])): ?>
                            <div class="alert alert-info mt-3 small">
                                <i class="fas fa-edit me-1"></i> Edit permission granted by Registrar.
                            </div>
                            <a href="#" class="btn btn-outline-primary btn-sm w-100 mt-2">Edit Form</a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm w-100 mt-3" disabled><i class="fas fa-lock me-1"></i> Form Locked</button>
                        <?php endif; ?>

                        <a href="view_profile_printable.php" target="_blank" class="btn btn-outline-dark btn-sm w-100 mt-2"><i class="fas fa-print me-1"></i> Print Application</a>

                        <?php if($profile['enrollment_status'] === 'Approved'): ?>
                            <a href="admission_letter.php" target="_blank" class="btn btn-success btn-sm w-100 mt-2 fw-bold"><i class="fas fa-certificate me-1"></i> Admission Letter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Documents -->
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
                                            <a href="/uploads/documents/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-decoration-none fw-bold">
                                                <i class="fas fa-file-pdf me-1 text-danger"></i> <?php echo str_replace('_', ' ', $doc['doc_type']); ?>
                                            </a>
                                            <?php if ($doc['status'] == 'Rejected' && !empty($doc['remarks'])): ?>
                                                <small class="text-danger d-block mt-1"><i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars($doc['remarks']); ?></small>
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
                                            <?php
                                                $canEditUploads = in_array('uploads', $permissions);
                                                $isLocked = (bool)$profile['is_form_locked'];

                                                $canReplace = false;
                                                if ($doc['status'] === 'Rejected') {
                                                    $canReplace = true;
                                                } elseif ($doc['status'] === 'Verified') {
                                                    $canReplace = false;
                                                } else { // Pending or others
                                                    if (!$isLocked || $canEditUploads) {
                                                        $canReplace = true;
                                                    }
                                                }
                                            ?>
                                            <?php if ($canReplace): ?>
                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#replaceDoc<?php echo $doc['id']; ?>">
                                                    <i class="fas fa-sync-alt me-1"></i> Replace
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted" title="Document Locked"><i class="fas fa-lock"></i> Locked</span>
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
    // MULTI-STEP REGISTRATION FORM
?>

<div class="container py-4">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Student Admission Registration</h2>
        <p class="text-muted">Complete all sections to submit your application.</p>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-white p-0">
            <!-- Progress Bar/Tabs -->
            <ul class="nav nav-pills nav-fill p-3" id="regTabs">
                <li class="nav-item"><a class="nav-link active fw-bold" id="step1-tab">1. Basic & Academic</a></li>
                <li class="nav-item"><a class="nav-link fw-bold" id="step2-tab">2. Profile</a></li>
                <li class="nav-item"><a class="nav-link fw-bold" id="step3-tab">3. Family</a></li>
                <li class="nav-item"><a class="nav-link fw-bold" id="step4-tab">4. NEP & Compliance</a></li>
                <li class="nav-item"><a class="nav-link fw-bold" id="step5-tab">5. Uploads</a></li>
            </ul>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar" id="progressBar" style="width: 20%;"></div>
            </div>
        </div>

        <div class="card-body p-5">
            <form method="POST" action="registration_handler.php" enctype="multipart/form-data" id="regForm">

                <!-- Step 1: Basic & Academic -->
                <div class="step-section" id="step1">
                    <h5 class="mb-4 text-primary"><i class="fas fa-university me-2"></i>Academic Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Admission Year</label>
                            <input type="text" class="form-control" value="<?php echo date('Y'); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admission Mode <span class="text-danger">*</span></label>
                            <select name="admission_mode" class="form-select" onchange="toggleAdmissionMode(this.value)" required>
                                <option value="Regular">Regular Admission</option>
                                <option value="Lateral Entry">Lateral Entry (Direct 2nd Year)</option>
                                <option value="Transfer">Transfer / Migration</option>
                            </select>
                        </div>
                         <div class="col-md-6">
                            <label class="form-label">School / Department</label>
                            <select class="form-select">
                                <option>School of Engineering</option>
                                <option>School of Management</option>
                                <option>School of Sciences</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Program Applied For <span class="text-danger">*</span></label>
                            <select name="course_applied" class="form-select" required>
                                <option value="">Select Program</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="BSc">B.Sc</option>
                                <option value="BBA">BBA</option>
                                <option value="MBA">MBA</option>
                                <option value="M.Tech">M.Tech</option>
                                <option value="PhD">PhD</option>
                            </select>
                        </div>

                        <!-- Dynamic Qualification Section -->
                        <div class="col-12 mt-3">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="text-primary fw-bold mb-3" id="qual_title">Class 12th / Equivalent Details</h6>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" id="marks_label">Previous Marks (%) <span class="text-danger">*</span></label>
                                            <input type="text" name="previous_marks" class="form-control" placeholder="e.g. 85%" required>
                                        </div>
                                        <div class="col-md-6" id="diploma_field" style="display:none;">
                                            <label class="form-label">Diploma/University Registration No.</label>
                                            <input type="text" name="diploma_reg_no" class="form-control" placeholder="If applicable">
                                        </div>
                                    </div>

                                    <div id="lateral_info" class="alert alert-warning mt-3 mb-0 small" style="display:none;">
                                        <i class="fas fa-info-circle me-1"></i> You have selected <strong>Lateral Entry</strong>. Please ensure you upload your <strong>Diploma Certificate</strong> in the uploads section instead of Class 12th Marksheet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-primary px-4" onclick="nextStep(2)">Next <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Step 2: Personal Profile -->
                <div class="step-section d-none" id="step2">
                    <h5 class="mb-4 text-primary"><i class="fas fa-user me-2"></i>Personal Details</h5>
                    <div class="row g-3">
                         <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nationality <span class="text-danger">*</span></label>
                            <select name="nationality" class="form-select" onchange="toggleInternational(this.value)">
                                <option value="Indian">Indian</option>
                                <option value="International">International</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="General">General</option>
                                <option value="OBC">OBC</option>
                                <option value="SC/ST">SC/ST</option>
                            </select>
                        </div>
                         <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>

                    <div id="intlFields" class="mt-4 p-3 bg-light rounded d-none">
                        <h6 class="text-info fw-bold">International Details</h6>
                        <div class="row g-3">
                             <div class="col-md-4">
                                <input type="text" name="country_of_origin" class="form-control" placeholder="Country of Origin">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="passport_number" class="form-control" placeholder="Passport No.">
                            </div>
                             <div class="col-md-4">
                                <input type="text" name="visa_details" class="form-control" placeholder="Visa Details">
                            </div>
                             <div class="col-md-6">
                                <label class="small text-muted">Passport Copy</label>
                                <input type="file" name="passport_copy" class="form-control">
                            </div>
                             <div class="col-md-6">
                                <label class="small text-muted">Visa Copy</label>
                                <input type="file" name="visa_copy" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 me-2" onclick="nextStep(1)">Previous</button>
                        <button type="button" class="btn btn-primary px-4" onclick="nextStep(3)">Next <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Step 3: Family -->
                <div class="step-section d-none" id="step3">
                    <h5 class="mb-4 text-primary"><i class="fas fa-users me-2"></i>Family Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Father's Name</label>
                            <input type="text" name="father_name" class="form-control">
                        </div>
                         <div class="col-md-6">
                            <label class="form-label">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control">
                        </div>
                         <div class="col-md-6">
                            <label class="form-label">Guardian Contact</label>
                            <input type="text" name="guardian_contact" class="form-control" placeholder="Phone Number">
                        </div>
                    </div>
                     <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 me-2" onclick="nextStep(2)">Previous</button>
                        <button type="button" class="btn btn-primary px-4" onclick="nextStep(4)">Next <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Step 4: NEP & Compliance -->
                <div class="step-section d-none" id="step4">
                     <h5 class="mb-4 text-primary"><i class="fas fa-book-open me-2"></i>NEP & Compliance</h5>
                     <div class="row g-3">
                         <div class="col-md-6">
                             <label class="form-label">ABC ID (Academic Bank of Credits)</label>
                             <input type="text" name="abc_id" class="form-control" placeholder="12 Digit ID">
                         </div>
                         <div class="col-md-6">
                             <label class="form-label">Scholarship / Awards Details</label>
                             <input type="text" name="awards" class="form-control" placeholder="Optional">
                         </div>
                         <div class="col-12">
                             <label class="form-label">Anti-Ragging Undertaking Reference No.</label>
                             <input type="text" name="anti_ragging" class="form-control">
                         </div>
                         <div class="col-12">
                             <label class="form-label">Other Regulatory Details</label>
                             <textarea name="nep_details" class="form-control" rows="2"></textarea>
                         </div>
                     </div>
                     <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary px-4 me-2" onclick="nextStep(3)">Previous</button>
                        <button type="button" class="btn btn-primary px-4" onclick="nextStep(5)">Next <i class="fas fa-arrow-right ms-2"></i></button>
                    </div>
                </div>

                <!-- Step 5: Uploads & Submit -->
                <div class="step-section d-none" id="step5">
                    <h5 class="mb-4 text-primary"><i class="fas fa-upload me-2"></i>Document Uploads</h5>
                    <div class="alert alert-info small">Max file size: 200KB. Formats: JPG, PNG, PDF.</div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Student Photo <span class="text-danger">*</span></label>
                            <input type="file" name="photo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Signature <span class="text-danger">*</span></label>
                            <input type="file" name="signature" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID Proof (Aadhar/PAN) <span class="text-danger">*</span></label>
                            <input type="file" name="id_proof" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Previous Marksheet <span class="text-danger">*</span></label>
                            <input type="file" name="previous_marksheet" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" required id="declaration">
                        <label class="form-check-label" for="declaration">
                            I hereby declare that all the information submitted is correct and I agree to the University rules and regulations.
                        </label>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary px-4 me-2" onclick="nextStep(4)">Previous</button>
                        <button type="submit" class="btn btn-success btn-lg px-5">Submit Application</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function nextStep(step) {
        // Hide all steps
        document.querySelectorAll('.step-section').forEach(el => el.classList.add('d-none'));
        // Show target step
        document.getElementById('step' + step).classList.remove('d-none');

        // Update Tabs
        document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
        document.getElementById('step' + step + '-tab').classList.add('active');

        // Update Progress
        const progress = step * 20;
        document.getElementById('progressBar').style.width = progress + '%';
    }

    function toggleInternational(val) {
        if (val === 'International') {
            document.getElementById('intlFields').classList.remove('d-none');
        } else {
            document.getElementById('intlFields').classList.add('d-none');
        }
    }

    function toggleAdmissionMode(mode) {
        const title = document.getElementById('qual_title');
        const lateralInfo = document.getElementById('lateral_info');
        const diplomaField = document.getElementById('diploma_field');
        const marksLabel = document.getElementById('marks_label');

        if (mode === 'Lateral Entry') {
            title.innerText = 'Diploma / Polytechnic Details';
            lateralInfo.style.display = 'block';
            diplomaField.style.display = 'block';
            marksLabel.innerText = 'Diploma Agg. Percentage';
        } else {
            title.innerText = 'Class 12th / Equivalent Details';
            lateralInfo.style.display = 'none';
            diplomaField.style.display = 'none';
            marksLabel.innerText = 'Previous Marks (%)';
        }
    }
</script>

<?php
} // End Else
require_once __DIR__ . '/../../includes/footer.php';
?>
