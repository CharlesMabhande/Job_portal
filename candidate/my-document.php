<?php
/**
 * Stream the logged-in candidate's CV or qualifications file for viewing in the browser (inline).
 * Use ?download=1 to force download. Replaces direct /uploads/ links so Content-Disposition: inline applies.
 */
require_once __DIR__ . '/../config/config.php';
requireRole(['Candidate']);

$userId = (int)$_SESSION['user_id'];
$doc = strtolower(trim((string)($_GET['doc'] ?? '')));
$forceDownload = isset($_GET['download']) && (string)$_GET['download'] === '1';

if (!in_array($doc, ['cv', 'certs'], true)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Invalid request.';
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare('SELECT cv_path, certificates_path FROM candidates WHERE user_id = ?');
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Profile not found.';
    exit;
}

$relative = $doc === 'cv' ? (string)($row['cv_path'] ?? '') : (string)($row['certificates_path'] ?? '');
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
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

$basename = basename($fullPath);
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
