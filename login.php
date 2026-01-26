<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (login($username, $password)) {
        redirect('/');
    } else {
        $error = "Invalid username or password";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card login-card p-4" style="width: 400px;">
    <div class="card-body text-center">
        <h2 class="mb-4 fw-bold text-primary"><i class="fas fa-university"></i> Univ ERP</h2>
        <p class="text-muted mb-4">Sign in to access your portal</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fs-5 shadow-sm">Sign In</button>
        </form>
    </div>
    <div class="card-footer bg-white border-0 text-center text-muted mt-3">
        <small>System developed for NEP 2020 Compliance</small>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
