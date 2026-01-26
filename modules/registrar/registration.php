<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
require_once __DIR__ . '/../../includes/header.php';
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
                        <label class="form-label small text-muted">Passport Copy</label>
                        <input type="file" name="passport_copy" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Visa Copy</label>
                        <input type="file" name="visa_copy" class="form-control">
                    </div>
                </div>
            </div>

            <h5 class="text-primary fw-bold mb-3"><i class="fas fa-file-upload me-2"></i>Documents</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Photo <span class="text-danger">*</span></label>
                    <input type="file" name="photo" class="form-control" required>
                </div>
                 <div class="col-md-4">
                    <label class="form-label">ID Proof (Aadhar/PAN) <span class="text-danger">*</span></label>
                    <input type="file" name="id_proof" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Previous Marksheet <span class="text-danger">*</span></label>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
