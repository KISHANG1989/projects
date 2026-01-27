<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2>Examination Branch</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Examination</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Subject Management</h5>
                <p class="card-text">Add or edit subjects and credits.</p>
                <a href="manage_subjects.php" class="btn btn-primary">Manage Subjects</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Exam Scheduling</h5>
                <p class="card-text">Create exams and manage timetables.</p>
                <a href="manage_exams.php" class="btn btn-primary">Manage Exams</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Results Processing</h5>
                <p class="card-text">Enter marks and generate grade sheets.</p>
                <a href="#" class="btn btn-primary">Process Results</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Hall Tickets</h5>
                <p class="card-text">Generate and print student hall tickets.</p>
                <a href="#" class="btn btn-primary">Generate Tickets</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
