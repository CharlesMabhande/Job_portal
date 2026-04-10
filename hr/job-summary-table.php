<?php
/**
 * Printable / downloadable summary table of applicants for one job (HR, Management, SysAdmin).
 */
require_once __DIR__ . '/../config/config.php';
requireRole(['HR', 'Management', 'SysAdmin']);

require_once BASE_PATH . '/includes/job_summary_table.php';
require_once BASE_PATH . '/includes/export_qrcode.php';
require_once BASE_PATH . '/includes/document_signing.php';

$jobId = (int)($_GET['job_id'] ?? 0);
if ($jobId < 1) {
    redirect('/hr/applications.php', 'Invalid job.', 'error');
}

$db = getDBConnection();
$stmt = $db->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$job) {
    redirect('/hr/applications.php', 'Job not found.', 'error');
}

$status = (string)($job['status'] ?? '');
if (in_array($status, ['Draft', 'Cancelled'], true)) {
    redirect('/hr/applications.php', 'Summary table is only available for submitted jobs (not Draft or Cancelled).', 'error');
}

$stmt = $db->prepare("
    SELECT a.application_id,
           u.first_name, u.last_name,
           c.date_of_birth, c.gender,
           c.professional_qualifications, c.o_level_qualifications, c.a_level_qualifications, c.other_certifications,
           c.experience
    FROM applications a
    JOIN candidates c ON a.candidate_id = c.candidate_id
    JOIN users u ON c.user_id = u.user_id
    WHERE a.job_id = ?
    ORDER BY u.last_name ASC, u.first_name ASC
");
$stmt->execute([$jobId]);
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$appIds = [];
foreach ($applicants as $ar) {
    $aid = (int)($ar['application_id'] ?? 0);
    if ($aid > 0) {
        $appIds[] = $aid;
    }
}
sort($appIds);
$canonicalStr = signedExportCanonicalStringJobSummary($jobId, $appIds, (string)($job['updated_at'] ?? ''));
$displayPayload = [
    'job_title' => (string)($job['title'] ?? ''),
    'department' => (string)($job['department'] ?? ''),
    'applicant_count' => count($applicants),
    'export_label' => 'Applicant summary table',
];
$issuedBy = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$signing = signedExportRegister($db, 'job_summary_table', $jobId, $canonicalStr, $displayPayload, $issuedBy);
$verifyUrl = $signing !== null ? signedExportVerifyUrl($signing['token']) : '';
$qrUri = $verifyUrl !== '' ? exportQrCodeDataUri($verifyUrl) : '';
$html = jobSummaryTableRenderDocument($job, $applicants, $qrUri);
$download = isset($_GET['download']) && $_GET['download'] === '1';
$fname = jobSummaryTableSafeFilename((string)($job['title'] ?? 'job'), $jobId);

if ($download) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $fname) . '"');
    echo $html;
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
exit;
