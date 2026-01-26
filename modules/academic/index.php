<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2>Academic Department <span class="badge bg-success">NEP 2020 Compliant</span></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Academic</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12 mb-4">
        <div class="alert alert-info">
            <strong>National Education Policy (NEP) 2020 Implementation:</strong>
            This module supports the Academic Bank of Credits (ABC), Multiple Entry/Exit options, and Multidisciplinary Education.
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100 border-info">
            <div class="card-header bg-info text-white">Academic Bank of Credits (ABC)</div>
            <div class="card-body">
                <p>Manage student credit accumulation, transfer, and redemption.</p>
                <button class="btn btn-outline-info">View Credit Bank</button>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-success">
            <div class="card-header bg-success text-white">Curriculum Framework</div>
            <div class="card-body">
                <p>Design multi-disciplinary courses and skill enhancement electives.</p>
                <button class="btn btn-outline-success">Manage Curriculum</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
