<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2>Student Admission Form</h2>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Application submitted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="studentTypeTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="indian-tab" data-bs-toggle="tab" data-bs-target="#indian" type="button" role="tab" onclick="setNationality('Indian')">Indian Student</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="international-tab" data-bs-toggle="tab" data-bs-target="#international" type="button" role="tab" onclick="setNationality('International')">International Student</button>
            </li>
        </ul>

        <form method="POST" action="registration_handler.php" enctype="multipart/form-data">
            <input type="hidden" name="nationality" id="nationalityField" value="Indian">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" required></textarea>
            </div>

            <div class="row mb-3">
                 <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="General">General</option>
                        <option value="OBC">OBC</option>
                        <option value="SC/ST">SC/ST</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <!-- International Fields -->
            <div id="internationalFields" style="display:none;">
                <h5 class="mt-4">International Details</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Country of Origin</label>
                        <input type="text" name="country_of_origin" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Passport Number</label>
                        <input type="text" name="passport_number" class="form-control">
                    </div>
                     <div class="col-md-4">
                        <label class="form-label">Visa Details</label>
                        <input type="text" name="visa_details" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Passport Copy</label>
                        <input type="file" name="passport_copy" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Visa Copy</label>
                        <input type="file" name="visa_copy" class="form-control">
                    </div>
                </div>
            </div>

            <h5 class="mt-4">Documents</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" class="form-control" required>
                </div>
                 <div class="col-md-6">
                    <label class="form-label">ID Proof (Aadhar/PAN/Other)</label>
                    <input type="file" name="id_proof" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit Application</button>
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
