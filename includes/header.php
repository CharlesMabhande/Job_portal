<?php
/**
 * Shared HTML header (Bootstrap 5) + navbar include.
 * Usage: require_once __DIR__ . '/../config/config.php'; require_once BASE_PATH.'/includes/header.php';
 */

if (!defined('BASE_PATH')) {
    http_response_code(500);
    die('Config not loaded.');
}

$currentUser = getCurrentUser();
$flash = getFlashMessage();
$csrf = generateCSRFToken();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape($pageTitle ?? 'University Job Portal'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php require_once BASE_PATH . '/includes/navbar.php'; ?>

<main class="container py-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo escape($flash['type'] === 'error' ? 'danger' : $flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo escape($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

