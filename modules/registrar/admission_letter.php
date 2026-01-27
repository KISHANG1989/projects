<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
$pdo = getDBConnection();

// ID from GET or Current User
if (hasRole('student')) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
} else {
    // Registrar viewing
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE id = ?");
    $stmt->execute([$id]);
    $profile = $stmt->fetch();
}

if (!$profile || $profile['enrollment_status'] !== 'Approved') {
    die("Admission Letter not available.");
}

$extended = json_decode($profile['extended_data'] ?? '{}', true);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admission Letter - <?php echo htmlspecialchars($profile['full_name']); ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 40px; color: #000; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; }
        .sub-header { font-size: 14px; margin-top: 5px; }
        .ref { display: flex; justify-content: space-between; margin-bottom: 30px; font-weight: bold; }
        .subject { text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 30px; }
        .content { line-height: 1.6; text-align: justify; margin-bottom: 40px; }
        .footer { display: flex; justify-content: space-between; margin-top: 80px; }
        .sign { text-align: center; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <div class="header">
        <div class="logo">INDIAN UNIVERSITY ERP</div>
        <div class="sub-header">Accredited by NAAC | NEP 2020 Compliant Institute</div>
        <div class="sub-header">New Delhi, India - 110001</div>
    </div>

    <div class="ref">
        <div>Ref: <?php echo htmlspecialchars($profile['application_no']); ?>/ADM/<?php echo date('Y'); ?></div>
        <div>Date: <?php echo date('d F Y'); ?></div>
    </div>

    <div class="subject">
        PROVISIONAL ADMISSION LETTER (Session <?php echo date('Y') . '-' . (date('Y')+1); ?>)
    </div>

    <div class="content">
        <p>Dear <strong><?php echo htmlspecialchars($profile['full_name']); ?></strong>,</p>

        <p>We are pleased to inform you that your application for admission to the <strong><?php echo htmlspecialchars($profile['course_applied']); ?></strong> program has been approved by the Registrar's Office.</p>

        <p>Your admission details are as follows:</p>

        <ul>
            <li><strong>Enrollment / Roll Number:</strong> <?php echo htmlspecialchars($profile['roll_number']); ?></li>
            <li><strong>Program:</strong> <?php echo htmlspecialchars($profile['course_applied']); ?></li>
            <li><strong>Department/School:</strong> <?php echo htmlspecialchars($extended['school'] ?? 'Main Campus'); ?></li>
            <li><strong>Category:</strong> <?php echo htmlspecialchars($profile['category']); ?></li>
            <li><strong>ABC ID:</strong> <?php echo htmlspecialchars($profile['abc_id']); ?></li>
        </ul>

        <p>This admission is provisional and subject to the verification of your original documents at the time of physical reporting. Please bring this letter along with your original marksheets, ID proof, and fee receipt.</p>

        <p>Welcome to Indian University ERP. We wish you a successful academic journey.</p>
    </div>

    <div class="footer">
        <div class="sign">
            ____________________<br>
            Student Signature
        </div>
        <div class="sign">
            <img src="/assets/stamp_dummy.png" style="height: 50px; opacity: 0.5;"><br>
            ____________________<br>
            Registrar<br>
            Indian University ERP
        </div>
    </div>

</body>
</html>
