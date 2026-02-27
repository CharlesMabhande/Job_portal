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
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx']);

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24); // 24 hours

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@university.edu');
define('SMTP_FROM_NAME', 'University Job Portal');

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
