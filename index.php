<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

require_once __DIR__ . '/includes/header.php';

$modules = [
    ['name' => 'Registrar', 'url' => '/modules/registrar/', 'icon' => 'file-alt', 'color' => 'primary'],
    ['name' => 'Exam', 'url' => '/modules/exam/', 'icon' => 'pen-alt', 'color' => 'success'],
    ['name' => 'Academic (NEP)', 'url' => '/modules/academic/', 'icon' => 'book-open', 'color' => 'info'],
    ['name' => 'Security', 'url' => '#', 'icon' => 'shield-alt', 'color' => 'secondary'],
    ['name' => 'Hostel', 'url' => '#', 'icon' => 'bed', 'color' => 'warning'],
    ['name' => 'Mess', 'url' => '#', 'icon' => 'utensils', 'color' => 'danger'],
    ['name' => 'Schools', 'url' => '#', 'icon' => 'university', 'color' => 'dark'],
    ['name' => 'E-Governance', 'url' => '#', 'icon' => 'globe', 'color' => 'primary'],
    ['name' => 'DSW', 'url' => '#', 'icon' => 'users', 'color' => 'info'],
    ['name' => 'DSA', 'url' => '#', 'icon' => 'running', 'color' => 'success'],
    ['name' => 'Accounts', 'url' => '#', 'icon' => 'rupee-sign', 'color' => 'warning'],
    ['name' => 'IQAC', 'url' => '#', 'icon' => 'chart-line', 'color' => 'secondary'],
    ['name' => 'HR', 'url' => '#', 'icon' => 'user-tie', 'color' => 'primary'],
    ['name' => 'Transport', 'url' => '#', 'icon' => 'bus', 'color' => 'dark'],
];

?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold text-dark">Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>
    <div class="col-md-4 text-end">
        <span class="badge bg-primary p-2 shadow-sm">NEP 2020 Compliant</span>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
    <?php foreach ($modules as $module): ?>
    <div class="col">
        <div class="card h-100 module-card border-0" onclick="window.location.href='<?php echo $module['url']; ?>'">
            <div class="card-body text-center p-4">
                <div class="icon-box mb-3 text-<?php echo $module['color']; ?>">
                    <i class="fas fa-<?php echo $module['icon']; ?> fa-3x"></i>
                </div>
                <h5 class="card-title fw-bold text-dark"><?php echo $module['name']; ?></h5>
                <p class="text-muted small">Manage Operations</p>
                <a href="<?php echo $module['url']; ?>" class="btn btn-outline-<?php echo $module['color']; ?> btn-sm rounded-pill px-3">Access</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
