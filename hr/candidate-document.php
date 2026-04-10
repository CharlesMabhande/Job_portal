<?php
/**
 * Stream CV or combined certificates from a candidate profile (not per-application override).
 * HR / Management / SysAdmin; same access rule as candidate profile view.
 */
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'Management', 'SysAdmin']);

$candidateId = (int)($_GET['candidate_id'] ?? 0);
$doc = strtolower(trim((string)($_GET['doc'] ?? '')));
$forceDownload = isset($_GET['download']) && (string)$_GET['download'] === '1';

if (!in_array($doc, ['cv', 'certs'], true) || $candidateId < 1) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Invalid request.';
    exit;
}

if (!staffCanViewCandidateProfile($candidateId)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Access denied.';
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare('SELECT cv_path, certificates_path FROM candidates WHERE candidate_id = ?');
$stmt->execute([$candidateId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Candidate not found.';
    exit;
}

$relative = '';
if ($doc === 'cv') {
    $relative = (string)($row['cv_path'] ?? '');
} else {
    $relative = (string)($row['certificates_path'] ?? '');
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
