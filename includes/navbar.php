<?php
/**
 * Shared navbar (role-aware).
 */
$roleId = $_SESSION['role_id'] ?? null;
$isLoggedIn = isset($_SESSION['user_id']);

function navItem($href, $label) {
    $url = BASE_URL . $href;
    echo '<li class="nav-item"><a class="nav-link" href="' . escape($url) . '">' . escape($label) . '</a></li>';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?php echo BASE_URL; ?>/index.php">University Job Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php navItem('/index.php', 'Jobs'); ?>

                <?php if ($isLoggedIn && (int)$roleId === 1): ?>
                    <?php navItem('/candidate/dashboard.php', 'Dashboard'); ?>
                    <?php navItem('/candidate/applications.php', 'My Applications'); ?>
                    <?php navItem('/candidate/profile.php', 'Profile'); ?>
                <?php elseif ($isLoggedIn && (int)$roleId === 2): ?>
                    <?php navItem('/hr/dashboard.php', 'HR Dashboard'); ?>
                    <?php navItem('/hr/jobs.php', 'Jobs'); ?>
                    <?php navItem('/hr/applications.php', 'Applications'); ?>
                    <?php navItem('/hr/interviews.php', 'Interviews'); ?>
                <?php elseif ($isLoggedIn && (int)$roleId === 3): ?>
                    <?php navItem('/management/dashboard.php', 'Management'); ?>
                    <?php navItem('/management/jobs.php', 'Approvals'); ?>
                <?php elseif ($isLoggedIn && (int)$roleId === 4): ?>
                    <?php navItem('/admin/dashboard.php', 'SysAdmin'); ?>
                    <?php navItem('/admin/users.php', 'Users'); ?>
                    <?php navItem('/admin/settings.php', 'Settings'); ?>
                    <?php navItem('/admin/logs.php', 'Audit Logs'); ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-2">
                            <?php echo escape(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?>
                            (<?php echo escape($_SESSION['role_name'] ?? ''); ?>)
                        </span>
                    </li>
                    <li class="nav-item me-2">
                        <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_URL; ?>/change_password.php">Change Password</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_URL; ?>/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-2">
                        <a class="btn btn-sm btn-outline-light" href="<?php echo BASE_URL; ?>/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-light" href="<?php echo BASE_URL; ?>/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

