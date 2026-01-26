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

$extended = json_decode($profile['extended_data'] ?? '{}', true);
$intl = null;
if ($profile['nationality'] === 'International') {
    $stmt = $pdo->prepare("SELECT * FROM international_details WHERE student_profile_id = ?");
    $stmt->execute([$id]);
    $intl = $stmt->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4" style="max-width: 1000px;">
    <div class="text-center mb-4 d-print-none">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print Application</button>
        <button onclick="window.close()" class="btn btn-outline-secondary">Close</button>
    </div>

    <div class="card border-0 shadow-none">
        <div class="card-body p-5">
            <!-- Header -->
            <div class="d-flex justify-content-between border-bottom pb-4 mb-4">
                <div>
                    <h1 class="fw-bold text-uppercase">University Application Form</h1>
                    <p class="mb-0">Application No: <strong><?php echo htmlspecialchars($profile['application_no']); ?></strong></p>
                    <p class="mb-0">Session: <?php echo date('Y', strtotime($profile['created_at'])); ?>-<?php echo date('Y', strtotime($profile['created_at'])) + 1; ?></p>
                </div>
                <div class="text-end">
                     <!-- Photo Placeholder if we can't fetch it easily here, or query it -->
                     <?php
                        // Try to find photo
                        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE user_id = ? AND doc_type = 'photo'");
                        $stmt->execute([$profile['user_id']]);
                        $photo = $stmt->fetch();
                     ?>
                     <?php if($photo): ?>
                        <img src="/uploads/documents/<?php echo $photo['file_path']; ?>" style="width: 120px; height: 150px; object-fit: cover; border: 1px solid #ccc;">
                     <?php else: ?>
                        <div style="width: 120px; height: 150px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">
                            PHOTO
                        </div>
                     <?php endif; ?>
                </div>
            </div>

            <!-- Sections -->
            <h5 class="bg-light p-2 border-bottom fw-bold text-uppercase mb-3">1. Academic Details</h5>
            <div class="row mb-4">
                <div class="col-6 mb-2"><span class="fw-bold">Program Applied:</span> <?php echo htmlspecialchars($profile['course_applied']); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">Previous Marks:</span> <?php echo htmlspecialchars($profile['previous_marks']); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">ABC ID:</span> <?php echo htmlspecialchars($profile['abc_id']); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">Enrollment No:</span> <?php echo htmlspecialchars($profile['roll_number'] ?? 'Not Generated'); ?></div>
            </div>

            <h5 class="bg-light p-2 border-bottom fw-bold text-uppercase mb-3">2. Personal Details</h5>
            <div class="row mb-4">
                <div class="col-6 mb-2"><span class="fw-bold">Full Name:</span> <?php echo htmlspecialchars($profile['full_name']); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">Date of Birth:</span> <?php echo htmlspecialchars($profile['dob']); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">Nationality:</span> <?php echo htmlspecialchars($profile['nationality']); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">Category:</span> <?php echo htmlspecialchars($profile['category']); ?></div>
                <div class="col-12 mb-2"><span class="fw-bold">Address:</span> <?php echo nl2br(htmlspecialchars($profile['address'])); ?></div>
            </div>

            <h5 class="bg-light p-2 border-bottom fw-bold text-uppercase mb-3">3. Family Details</h5>
            <div class="row mb-4">
                <div class="col-6 mb-2"><span class="fw-bold">Father's Name:</span> <?php echo htmlspecialchars($extended['family']['father_name'] ?? '-'); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">Mother's Name:</span> <?php echo htmlspecialchars($extended['family']['mother_name'] ?? '-'); ?></div>
                <div class="col-6 mb-2"><span class="fw-bold">Guardian Contact:</span> <?php echo htmlspecialchars($extended['family']['guardian_contact'] ?? '-'); ?></div>
            </div>

            <?php if($intl): ?>
            <h5 class="bg-light p-2 border-bottom fw-bold text-uppercase mb-3">International Details</h5>
            <div class="row mb-4">
                 <div class="col-6 mb-2"><span class="fw-bold">Passport:</span> <?php echo htmlspecialchars($intl['passport_number']); ?></div>
                 <div class="col-6 mb-2"><span class="fw-bold">Visa:</span> <?php echo htmlspecialchars($intl['visa_details']); ?></div>
                 <div class="col-6 mb-2"><span class="fw-bold">Origin:</span> <?php echo htmlspecialchars($intl['country_of_origin']); ?></div>
            </div>
            <?php endif; ?>

            <h5 class="bg-light p-2 border-bottom fw-bold text-uppercase mb-3">4. Other Details</h5>
             <div class="row mb-4">
                <div class="col-12 mb-2"><span class="fw-bold">Scholarships/Awards:</span> <?php echo htmlspecialchars($extended['awards'] ?? '-'); ?></div>
                <div class="col-12 mb-2"><span class="fw-bold">Regulatory/NEP Details:</span> <?php echo htmlspecialchars($extended['nep_details'] ?? '-'); ?></div>
                 <div class="col-12 mb-2"><span class="fw-bold">Anti-Ragging Ref:</span> <?php echo htmlspecialchars($extended['regulatory']['anti_ragging'] ?? '-'); ?></div>
            </div>

            <div class="mt-5 pt-5 border-top">
                <div class="row">
                    <div class="col-6 text-center">
                         <?php
                        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE user_id = ? AND doc_type = 'signature'");
                        $stmt->execute([$profile['user_id']]);
                        $sig = $stmt->fetch();
                        ?>
                        <?php if($sig): ?>
                            <img src="/uploads/documents/<?php echo $sig['file_path']; ?>" style="max-height: 50px;">
                        <?php endif; ?>
                        <p class="mt-2 fw-bold">Student Signature</p>
                    </div>
                    <div class="col-6 text-end pt-4">
                        <p class="fw-bold">Registrar Signature</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
