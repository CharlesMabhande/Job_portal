<?php
/**
 * Floating navbar (Mutare-style, role-aware).
 */
$roleId = $_SESSION['role_id'] ?? null;
$isLoggedIn = isset($_SESSION['user_id']);
$userPhotoUrl = null;
if ($isLoggedIn && (int)$roleId === 1) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT profile_photo_path FROM candidates WHERE user_id = ?");
        $stmt->execute([(int)($_SESSION['user_id'] ?? 0)]);
        $userPhotoUrl = candidateProfilePhotoUrl((string)($stmt->fetchColumn() ?: ''));
    } catch (Throwable $e) {
        $userPhotoUrl = null;
    }
}

function navItem($href, $label, $icon = '') {
    $url = BASE_URL . $href;
    $iconHtml = $icon ? '<i class="' . $icon . ' me-1"></i>' : '';
    echo '<li class="nav-item"><a class="nav-link" href="' . escape($url) . '">' . $iconHtml . escape($label) . '</a></li>';
}
?>

<div class="jp-nav-wrap">
    <nav class="navbar navbar-expand-lg jp-navbar">
        <div class="container-fluid px-2 px-sm-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2 gap-lg-3" href="<?php echo BASE_URL; ?>/index.php">
                <img src="<?php echo SITE_LOGO_URL; ?>" class="jp-brand-logo" alt="<?php echo escape(SITE_LOGO_ALT); ?>">
                <span class="jp-brand-text">Lupane State University<br><small class="jp-brand-sub">Job Portal</small></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <?php navItem('/index.php', 'Jobs', 'fa-solid fa-magnifying-glass'); ?>

                    <?php if ($isLoggedIn && (int)$roleId === 1): ?>
                        <?php navItem('/candidate/dashboard.php', 'Dashboard', 'fa-solid fa-gauge-high'); ?>
                        <?php navItem('/candidate/applications.php', 'My Applications', 'fa-solid fa-file-lines'); ?>
                        <?php navItem('/candidate/profile.php', 'Profile', 'fa-solid fa-user'); ?>
                    <?php elseif ($isLoggedIn && (int)$roleId === 2): ?>
                        <?php navItem('/hr/dashboard.php', 'HR Dashboard', 'fa-solid fa-gauge-high'); ?>
                        <?php navItem('/hr/jobs.php', 'Manage Jobs', 'fa-solid fa-briefcase'); ?>
                        <?php navItem('/hr/applications.php', 'Applications', 'fa-solid fa-file-lines'); ?>
                        <?php navItem('/hr/interviews.php', 'Interviews', 'fa-solid fa-calendar-check'); ?>
                    <?php elseif ($isLoggedIn && (int)$roleId === 3): ?>
                        <?php navItem('/management/dashboard.php', 'Management', 'fa-solid fa-gauge-high'); ?>
                        <?php navItem('/management/jobs.php', 'Approvals', 'fa-solid fa-circle-check'); ?>
                        <?php navItem('/management/published-jobs.php', 'Job summaries', 'fa-solid fa-table'); ?>
                        <?php navItem('/hr/applications.php', 'Applications', 'fa-solid fa-file-lines'); ?>
                    <?php elseif ($isLoggedIn && (int)$roleId === 4): ?>
                        <?php navItem('/admin/dashboard.php', 'SysAdmin', 'fa-solid fa-gear'); ?>
                        <?php navItem('/admin/users.php', 'Users', 'fa-solid fa-users'); ?>
                        <?php navItem('/admin/settings.php', 'Settings', 'fa-solid fa-sliders'); ?>
                        <?php navItem('/admin/logs.php', 'Audit Logs', 'fa-solid fa-scroll'); ?>
                    <?php else: ?>
                        <?php navItem('/user-guide.php', 'User Guide'); ?>
                        <?php navItem('/login.php', 'Track Application'); ?>
                        <?php navItem('/index.php#contact-us', 'Contact Us'); ?>
                    <?php endif; ?>
                </ul>

                <div class="d-flex gap-2 align-items-center flex-wrap jp-navbar-user">
                    <?php if ($isLoggedIn): ?>
                        <span class="user-badge">
                            <?php if ($userPhotoUrl): ?>
                                <img src="<?php echo escape($userPhotoUrl); ?>" alt="Profile photo" style="width:24px;height:24px;border-radius:50%;object-fit:cover;margin-right:6px;">
                            <?php else: ?>
                                <i class="fa-solid fa-circle-user me-1"></i>
                            <?php endif; ?>
                            <?php echo escape(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?>
                            <span class="opacity-75">(<?php echo escape($_SESSION['role_name'] ?? ''); ?>)</span>
                        </span>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/change_password.php">
                            <i class="fa-solid fa-key me-1"></i> Password
                        </a>
                        <a class="btn btn-sm btn-outline-danger" href="<?php echo BASE_URL; ?>/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                        </a>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/login.php">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                        </a>
                        <a class="btn btn-sm btn-primary text-white" href="<?php echo BASE_URL; ?>/user-guide.php?next=register">
                            <i class="fa-solid fa-user-plus me-1"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</div>
