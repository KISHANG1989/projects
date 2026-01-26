<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notifications.php';

// Determine if we are on login page to avoid sidebar
$is_login_page = basename($_SERVER['PHP_SELF']) == 'login.php';

// Fetch Notifications if logged in
$notifications = [];
$unread_count = 0;
if (isLoggedIn()) {
    $notifications = getUnreadNotifications($_SESSION['user_id']);
    $unread_count = count($notifications);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indian University ERP</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/modern.css" rel="stylesheet">

    <style>
        .notification-dropdown {
            width: 320px;
            max-height: 400px;
            overflow-y: auto;
        }
        .notif-item {
            cursor: pointer;
            transition: background 0.2s;
        }
        .notif-item:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body class="<?php echo $is_login_page ? 'login-body' : ''; ?>">

<?php if (!$is_login_page): ?>
<div class="wrapper">
    <!-- Sidebar -->
    <?php if (isLoggedIn()): ?>
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-university me-2"></i> Univ ERP</h3>
            <small>NEP 2020 Compliant</small>
        </div>

        <ul class="list-unstyled components">
            <li>
                <a href="/" class="<?php echo $_SERVER['REQUEST_URI'] == '/' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large me-2"></i> Dashboard
                </a>
            </li>

            <?php if (hasRole('registrar') || hasRole('admin')): ?>
            <li>
                <a href="#registrarSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-file-alt me-2"></i> Registrar Office
                </a>
                <ul class="collapse list-unstyled" id="registrarSubmenu">
                    <li>
                        <a href="/modules/registrar/verification_list.php" class="ps-5">Verification Queue</a>
                    </li>
                    <li>
                        <a href="/modules/registrar/convocation/index.php" class="ps-5">Convocation</a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (hasRole('student')): ?>
            <li>
                <a href="/modules/registrar/registration.php">
                    <i class="fas fa-user-graduate me-2"></i> Admission Form
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole('faculty')): ?>
            <li>
                <a href="/modules/exam/marks_entry.php">
                    <i class="fas fa-marker me-2"></i> Marks Entry
                </a>
            </li>
            <?php endif; ?>

            <?php if (isLoggedIn() && !hasRole('student')): ?>
             <li>
                <a href="/modules/task_manager/index.php">
                    <i class="fas fa-tasks me-2"></i> Task Manager
                </a>
            </li>
            <?php endif; ?>

            <li>
                <a href="#">
                    <i class="fas fa-book me-2"></i> Academic (ABC)
                </a>
            </li>
            <?php if (hasRole('admin') || hasRole('registrar')): ?>
            <li>
                <a href="/modules/exam/index.php">
                    <i class="fas fa-edit me-2"></i> Examination
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole('student')): ?>
            <li>
                <a href="/modules/exam/admit_card.php">
                    <i class="fas fa-id-card me-2"></i> Admit Card
                </a>
            </li>
            <li>
                <a href="/modules/exam/results.php">
                    <i class="fas fa-poll-h me-2"></i> Results
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-custom mb-4">
            <div class="container-fluid">
                <?php if (isLoggedIn()): ?>
                <button type="button" id="sidebarCollapse" class="btn btn-light shadow-sm">
                    <i class="fas fa-bars"></i>
                </button>
                <?php endif; ?>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <?php if (isLoggedIn()): ?>
                            <!-- Notifications -->
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link text-dark position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell fa-lg"></i>
                                    <?php if($unread_count > 0): ?>
                                    <span class="position-absolute top-10 start-100 translate-middle badge rounded-pill bg-danger" id="notifCount">
                                        <?php echo $unread_count; ?>
                                        <span class="visually-hidden">unread messages</span>
                                    </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow notification-dropdown" aria-labelledby="notifDropdown">
                                    <li class="dropdown-header fw-bold">Notifications</li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <div id="notifList">
                                        <?php if(empty($notifications)): ?>
                                            <li class="p-3 text-center text-muted small">No new notifications</li>
                                        <?php else: ?>
                                            <?php foreach($notifications as $n): ?>
                                                <li class="notif-item p-3 border-bottom" onclick="markRead(<?php echo $n['id']; ?>, '<?php echo $n['link']; ?>')">
                                                    <div class="small text-dark"><?php echo htmlspecialchars($n['message']); ?></div>
                                                    <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <li class="text-center p-2"><a href="#" class="small text-decoration-none">View All</a></li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-dark" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="#">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="/logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link btn btn-primary text-white px-4" href="/login.php">Login</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <script>
        function markRead(id, link) {
            fetch('/includes/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_read&id=' + id
            }).then(() => {
                if(link && link !== '#') window.location.href = link;
            });
        }
        </script>

        <div class="container-fluid px-4">
<?php endif; ?>
