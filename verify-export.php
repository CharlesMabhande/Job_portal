<?php
/**
 * Public verification page for signed exports (no login required).
 */
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/document_signing.php';

$token = (string)($_GET['t'] ?? '');
$db = getDBConnection();
$result = signedExportVerifyLookup($db, $token);
$ok = $result['ok'];
$reason = $result['reason'];
$row = $result['row'] ?? null;
$meta = [];
if (is_array($row) && !empty($row['payload_json'])) {
    $decoded = json_decode((string)$row['payload_json'], true);
    if (is_array($decoded)) {
        $meta = $decoded;
    }
}
$issuedAt = is_array($row) ? formatDateTimeDisplay((string)($row['issued_at'] ?? '')) : '';
$issuer = '';
if ($ok && is_array($row) && !empty($row['issued_by_user_id'])) {
    $st = $db->prepare('SELECT first_name, last_name FROM users WHERE user_id = ?');
    $st->execute([(int)$row['issued_by_user_id']]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $issuer = trim((string)($u['first_name'] ?? '') . ' ' . (string)($u['last_name'] ?? ''));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $ok ? 'Verified export' : 'Verification failed'; ?> — LSU Job Portal</title>
    <style>
        body { font-family: system-ui, Segoe UI, Roboto, sans-serif; margin: 0; padding: 24px; background: #f4f4f5; color: #1a1a1a; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 24px 28px; box-shadow: 0 8px 30px rgba(0,0,0,.08); }
        h1 { font-size: 1.35rem; margin: 0 0 12px; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: .85rem; font-weight: 600; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-bad { background: #fee2e2; color: #991b1b; }
        dl { margin: 16px 0 0; font-size: .95rem; }
        dt { color: #666; font-size: .8rem; text-transform: uppercase; margin-top: 12px; }
        dd { margin: 4px 0 0; }
        code { word-break: break-all; font-size: .8rem; background: #f3f3f4; padding: 2px 6px; border-radius: 4px; }
        .foot { margin-top: 24px; font-size: .8rem; color: #666; }
        a { color: #c61f26; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($ok): ?>
            <span class="badge badge-ok">Authentic — issued by this portal</span>
            <h1>Signed export verified</h1>
            <p style="margin:8px 0 0;color:#444;">This verification code matches a registered export from the <strong>Lupane State University Job Portal</strong>. The cryptographic seal on the document has not been altered on the server.</p>
            <dl>
                <dt>Export type</dt>
                <dd><?php echo escape((string)($row['export_type'] ?? '')); ?></dd>
                <?php if (!empty($meta['export_label'])): ?>
                    <dt>Label</dt>
                    <dd><?php echo escape((string)$meta['export_label']); ?></dd>
                <?php endif; ?>
                <?php if (!empty($meta['job_title'])): ?>
                    <dt>Job title</dt>
                    <dd><?php echo escape((string)$meta['job_title']); ?></dd>
                <?php endif; ?>
                <?php if (isset($meta['department']) && $meta['department'] !== ''): ?>
                    <dt>Department</dt>
                    <dd><?php echo escape((string)$meta['department']); ?></dd>
                <?php endif; ?>
                <?php if (isset($meta['applicant_count'])): ?>
                    <dt>Applicants listed at generation</dt>
                    <dd><?php echo (int)$meta['applicant_count']; ?></dd>
                <?php endif; ?>
                <dt>Issued at (server)</dt>
                <dd><?php echo escape($issuedAt !== '' ? $issuedAt : '—'); ?></dd>
                <?php if ($issuer !== ''): ?>
                    <dt>Issued by (staff user)</dt>
                    <dd><?php echo escape($issuer); ?></dd>
                <?php endif; ?>
                <dt>Document fingerprint (SHA-256)</dt>
                <dd><code><?php echo escape((string)($row['canonical_sha256'] ?? '')); ?></code></dd>
                <dt>Job record ID</dt>
                <dd><?php echo (int)($row['job_id'] ?? 0) ?: '—'; ?></dd>
            </dl>
            <p class="foot">If the downloaded file was edited after download, its contents may no longer match this fingerprint. This page confirms the portal issued an export with this fingerprint at the time shown.</p>
        <?php else: ?>
            <span class="badge badge-bad">Not verified</span>
            <h1>Verification failed</h1>
            <p style="color:#444;"><?php echo escape($reason); ?></p>
            <p class="foot">If you believe this is an error, contact ICT support with the full verification link you used.</p>
        <?php endif; ?>
        <p class="foot"><a href="<?php echo escape(BASE_URL); ?>/index.php">Return to Job Portal</a></p>
    </div>
</body>
</html>
