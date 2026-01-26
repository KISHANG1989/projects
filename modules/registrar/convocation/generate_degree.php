<?php
session_start();
require_once '../../../config/db.php';

// Check Login (Public verification might differ, but for now restricted)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$pdo = getDBConnection();
$degree_id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT sd.*, sp.full_name, sp.roll_number, ce.event_date
    FROM student_degrees sd
    JOIN users u ON sd.student_id = u.id
    JOIN student_profiles sp ON u.id = sp.user_id
    JOIN convocation_events ce ON sd.convocation_id = ce.id
    WHERE sd.id = ?
");
$stmt->execute([$degree_id]);
$degree = $stmt->fetch();

if (!$degree) {
    die("Degree not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Degree Certificate - <?= htmlspecialchars($degree['degree_serial_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Great+Vibes&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Merriweather', serif;
        }
        .degree-container {
            width: 297mm; /* A4 Landscape */
            height: 210mm;
            background: #fff;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            position: relative;
            box-sizing: border-box;
        }
        .border-pattern {
            border: 20px double #4a148c;
            height: 100%;
            padding: 40px;
            box-sizing: border-box;
            position: relative;
            text-align: center;
        }
        .border-pattern::before {
            content: '';
            position: absolute;
            top: 5px; left: 5px; right: 5px; bottom: 5px;
            border: 2px solid #daa520;
            pointer-events: none;
        }
        .univ-name {
            font-family: 'Cinzel', serif;
            font-size: 36px;
            color: #4a148c;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 700;
        }
        .univ-sub {
            font-size: 14px;
            color: #555;
            margin-bottom: 40px;
        }
        .serial-no {
            position: absolute;
            top: 20px;
            right: 20px;
            font-family: monospace;
            font-size: 12px;
        }
        .main-text {
            font-size: 18px;
            line-height: 1.8;
            margin: 30px 0;
            color: #333;
        }
        .student-name {
            font-family: 'Great Vibes', cursive;
            font-size: 48px;
            color: #000;
            display: block;
            margin: 10px 0;
        }
        .degree-name {
            font-weight: 700;
            font-size: 24px;
            color: #4a148c;
        }
        .division {
            font-weight: 700;
        }
        .seal-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 50px;
        }
        .seal {
            width: 100px;
            height: 100px;
            background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/c4/Academic_seal_placeholder.svg/1024px-Academic_seal_placeholder.svg.png') no-repeat center;
            background-size: contain;
            opacity: 0.8;
        }
        .signature {
            text-align: center;
            border-top: 1px solid #333;
            width: 200px;
            padding-top: 10px;
            font-weight: bold;
        }
        .signature img {
            height: 40px;
            display: block;
            margin: 0 auto 5px;
            opacity: 0.7;
        }
        @media print {
            body { background: none; }
            .degree-container { box-shadow: none; width: 100%; height: 100%; }
        }
    </style>
</head>
<body>

    <div class="degree-container">
        <div class="border-pattern">
            <div class="serial-no">S.No: <?= htmlspecialchars($degree['degree_serial_no']) ?></div>

            <div class="univ-name">Indian University ERP</div>
            <div class="univ-sub">Established by Act of Parliament, NEP 2020 Compliant</div>

            <div class="main-text">
                This is to certify that
                <span class="student-name"><?= htmlspecialchars($degree['full_name']) ?></span>
                (Roll No. <?= htmlspecialchars($degree['roll_number']) ?>)
                <br>
                having been examined in <strong><?= date('F Y', strtotime($degree['event_date'])) ?></strong> and found qualified for the degree of
                <br><br>
                <span class="degree-name"><?= htmlspecialchars($degree['program_name']) ?></span>
                <br><br>
                has been admitted to the said degree in <strong><?= htmlspecialchars($degree['division']) ?></strong>
                <br>
                with a CGPA of <strong><?= htmlspecialchars($degree['cgpa']) ?></strong>.
            </div>

            <div class="seal-section">
                <div class="signature">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Signature_placeholder.svg/1200px-Signature_placeholder.svg.png" alt="Sig">
                    Registrar
                </div>
                <div class="seal"></div>
                <div class="signature">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Signature_placeholder.svg/1200px-Signature_placeholder.svg.png" alt="Sig">
                    Vice Chancellor
                </div>
            </div>

            <div style="margin-top: 40px; font-size: 12px; color: #777;">
                Date of Issue: <?= date('d F Y', strtotime($degree['issue_date'])) ?> | Place: New Delhi, India
            </div>
        </div>
    </div>

    <script>
        // Auto print prompt
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
