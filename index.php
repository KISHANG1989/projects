<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

require_once __DIR__ . '/includes/header.php';

$modules = [
    ['name' => 'Registrar', 'url' => '/modules/registrar/', 'icon' => 'files', 'color' => 'primary'],
    ['name' => 'Exam', 'url' => '/modules/exam/', 'icon' => 'pen', 'color' => 'success'],
    ['name' => 'Academic (NEP)', 'url' => '/modules/academic/', 'icon' => 'book', 'color' => 'info'],
    ['name' => 'Security', 'url' => '#', 'icon' => 'shield', 'color' => 'secondary'],
    ['name' => 'Hostel', 'url' => '#', 'icon' => 'home', 'color' => 'warning'],
    ['name' => 'Mess', 'url' => '#', 'icon' => 'utensils', 'color' => 'danger'],
    ['name' => 'Schools', 'url' => '#', 'icon' => 'school', 'color' => 'dark'],
    ['name' => 'E-Governance', 'url' => '#', 'icon' => 'globe', 'color' => 'primary'],
    ['name' => 'DSW', 'url' => '#', 'icon' => 'user-friends', 'color' => 'info'],
    ['name' => 'DSA', 'url' => '#', 'icon' => 'running', 'color' => 'success'],
    ['name' => 'Accounts', 'url' => '#', 'icon' => 'rupee-sign', 'color' => 'warning'],
    ['name' => 'IQAC', 'url' => '#', 'icon' => 'chart-line', 'color' => 'secondary'],
    ['name' => 'HR', 'url' => '#', 'icon' => 'users', 'color' => 'primary'],
    ['name' => 'Transport', 'url' => '#', 'icon' => 'bus', 'color' => 'dark'],
];

?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="display-6">Dashboard</h2>
        <p class="lead">Welcome to the University ERP System.</p>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
    <?php foreach ($modules as $module): ?>
    <div class="col">
        <div class="card h-100 module-card border-<?php echo $module['color']; ?> shadow-sm" onclick="window.location.href='<?php echo $module['url']; ?>'">
            <div class="card-body text-center">
                <h5 class="card-title text-<?php echo $module['color']; ?>"><?php echo $module['name']; ?></h5>
                <p class="card-text">Manage <?php echo $module['name']; ?> Department</p>
                <a href="<?php echo $module['url']; ?>" class="btn btn-outline-<?php echo $module['color']; ?> btn-sm">Access</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
