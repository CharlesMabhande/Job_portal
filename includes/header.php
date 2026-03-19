<?php
/**
 * Shared HTML header — Mutare-style theme.
 * Set $fullWidth = true before including to skip the container wrapper.
 */
if (!defined('BASE_PATH')) {
    http_response_code(500);
    die('Config not loaded.');
}

$currentUser = getCurrentUser();
$flash = getFlashMessage();
$csrf = generateCSRFToken();
$fullWidth = $fullWidth ?? false;
$appCssVersion = @filemtime(BASE_PATH . '/assets/css/app.css') ?: time();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape($pageTitle ?? 'University Job Portal'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="<?php echo BASE_URL; ?>/assets/css/app.css?v=<?php echo (int)$appCssVersion; ?>" rel="stylesheet">
</head>
<body>

<?php require_once BASE_PATH . '/includes/navbar.php'; ?>

<?php if (!$fullWidth): ?>
<main class="container py-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo escape($flash['type'] === 'error' ? 'danger' : $flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo escape($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php else: ?>
    <?php if ($flash): ?>
        <div class="container py-2">
            <div class="alert alert-<?php echo escape($flash['type'] === 'error' ? 'danger' : $flash['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo escape($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
