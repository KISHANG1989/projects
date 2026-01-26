<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role = 'student'; // Force student role for self-registration

    $pdo = getDBConnection();

    // Check existing
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = "Username already exists.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, department) VALUES (?, ?, ?, 'Academic')");
        if ($stmt->execute([$username, $hash, $role])) {
            $success = "Registration successful! You can now login.";
        } else {
            $error = "Registration failed.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card login-card p-4" style="width: 400px;">
    <div class="card-body text-center">
        <h2 class="mb-4 fw-bold text-primary"><i class="fas fa-user-plus"></i> New Student</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <a href="login.php" class="d-block mt-2">Login Here</a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Choose Username</label>
            </div>
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
             <input type="hidden" name="role" value="student">
            <button type="submit" class="btn btn-primary w-100 py-2 fs-5 shadow-sm">Register</button>
        </form>
        <div class="mt-3">
            <a href="login.php" class="text-decoration-none">Already have an account? Sign In</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
