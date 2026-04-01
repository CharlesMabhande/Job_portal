<?php
/**
 * Stream candidate documents for HR/SysAdmin with inline viewing (PDF) or download.
 * Resolves path from the application record (fallback to candidate profile).
 */
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'SysAdmin']);

$applicationId = (int)($_GET['application_id'] ?? 0);
$doc = strtolower(trim((string)($_GET['doc'] ?? '')));
$forceDownload = isset($_GET['download']) && (string)$_GET['download'] === '1';

if (!in_array($doc, ['cv', 'certs'], true) || $applicationId < 1) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Invalid request.';
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("
    SELECT a.cv_path AS app_cv_path,
           a.certificates_path AS app_certificates_path,
           c.cv_path AS candidate_cv_path,
           c.certificates_path AS candidate_certificates_path
    FROM applications a
    JOIN candidates c ON a.candidate_id = c.candidate_id
    WHERE a.application_id = ?
");
$stmt->execute([$applicationId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Application not found.';
    exit;
}

$relative = '';
if ($doc === 'cv') {
    $relative = !empty($row['app_cv_path']) ? $row['app_cv_path'] : (string)($row['candidate_cv_path'] ?? '');
} else {
    $relative = !empty($row['app_certificates_path']) ? $row['app_certificates_path'] : (string)($row['candidate_certificates_path'] ?? '');
}

$relative = str_replace('\\', '/', trim($relative));
if ($relative === '' || preg_match('#\.\.|^/#', $relative)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Document not found.';
    exit;
}

$uploadRoot = realpath(UPLOAD_DIR);
if ($uploadRoot === false) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Upload directory not available.';
    exit;
}

$normalized = str_replace('/', DIRECTORY_SEPARATOR, $relative);
$fullPath = realpath($uploadRoot . DIRECTORY_SEPARATOR . $normalized);

if ($fullPath === false || strpos($fullPath, $uploadRoot) !== 0 || !is_file($fullPath) || !is_readable($fullPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'File not found.';
    exit;
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

$basename = basename($fullPath);
// ASCII-safe filename for Content-Disposition
$safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $basename);
if ($safeName === '' || $safeName === '.' || $safeName === '..') {
    $safeName = 'document.' . ($ext ?: 'bin');
}

$disposition = $forceDownload ? 'attachment' : 'inline';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($fullPath));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');

readfile($fullPath);
exit;
