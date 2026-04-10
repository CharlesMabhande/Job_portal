<?php
/**
 * Main Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Base paths
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/Job_portal');

/**
 * Optional secret for signed export HMAC (summary tables, etc.). Prefer setting the
 * environment variable EXPORT_SIGNING_SECRET to a long random value (e.g. openssl rand -hex 32).
 * If unset, a deterministic fallback is derived from this install path (rotate via env for production).
 */
if (!defined('EXPORT_SIGNING_SECRET')) {
    define('EXPORT_SIGNING_SECRET', '');
}

/** Public URL to the official Lupane State University logo (place file at assets/img/lupane-logo.png). */
define('SITE_LOGO_URL', BASE_URL . '/assets/img/lupane-logo.png');
define('SITE_LOGO_ALT', 'Lupane State University Logo - Building Communities through Knowledge.');

// Directories
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('CV_DIR', UPLOAD_DIR . 'cv/');
define('DOCS_DIR', UPLOAD_DIR . 'documents/');

// Create upload directories if they don't exist
if (!file_exists(CV_DIR)) {
    mkdir(CV_DIR, 0755, true);
}
if (!file_exists(DOCS_DIR)) {
    mkdir(DOCS_DIR, 0755, true);
}

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
/** CV and qualifications uploads on candidate profile: PDF only. */
define('ALLOWED_FILE_TYPES', ['pdf']);

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24); // 24 hours

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
/** Shown in the footer, email templates, and as the default From address (configure SMTP to match your mail server). */
define('SITE_CONTACT_EMAIL', 'erecruitment@lsu.ac.zw');
define('SMTP_FROM_EMAIL', SITE_CONTACT_EMAIL);
define('SMTP_FROM_NAME', 'Lupane State University e-Recruitment');

/** Official LSU campus contacts (footer / displays). */
define('SITE_CONTACT_ADDRESS', "P.O Box 170 Lupane,\nAlong Bulawayo-Victoria Falls Highway, Zimbabwe");
define('SITE_CONTACT_PHONE', '+263 (081) 2856488, 2856558');
define('SITE_CONTACT_FAX', '+263 (081) 2856393');

/**
 * Official Lupane State University social profiles (footer links).
 * Leave a URL empty ("") to hide that icon. Add SITE_SOCIAL_X when the university publishes an official handle.
 */
define('SITE_SOCIAL_FACEBOOK', 'https://www.facebook.com/lupanestate');
define('SITE_SOCIAL_INSTAGRAM', 'https://www.instagram.com/lupanestateuniversity/');
define('SITE_SOCIAL_LINKEDIN', 'https://www.linkedin.com/company/lupane-state-university/');
define('SITE_SOCIAL_X', '');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Include database config
require_once BASE_PATH . '/config/database.php';

// Include helper functions
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/security.php';
// Email helpers (safe wrapper; will no-op if PHPMailer isn't installed yet)
require_once BASE_PATH . '/includes/email.php';
