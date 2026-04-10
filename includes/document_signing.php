<?php
/**
 * Register and verify cryptographically signed exports (summary tables, etc.).
 */

if (!defined('BASE_PATH')) {
    exit;
}

/**
 * Prefer environment EXPORT_SIGNING_SECRET or constant; otherwise derive from install path (rotate via env for production).
 */
function exportSigningSecret(): string {
    $env = getenv('EXPORT_SIGNING_SECRET');
    if (is_string($env) && $env !== '') {
        return $env;
    }
    if (defined('EXPORT_SIGNING_SECRET')) {
        $c = constant('EXPORT_SIGNING_SECRET');
        if (is_string($c) && $c !== '') {
            return $c;
        }
    }

    return hash('sha256', 'jobportal|export|' . BASE_PATH);
}

/**
 * Canonical string hashed for job summary exports (binds token to job + application set + job revision).
 */
function signedExportCanonicalStringJobSummary(int $jobId, array $sortedApplicationIds, string $jobUpdatedAt): string {
    $payload = [
        'v' => 1,
        'type' => 'job_summary_table',
        'job_id' => $jobId,
        'application_ids' => array_values($sortedApplicationIds),
        'job_updated_at' => $jobUpdatedAt,
    ];

    return json_encode($payload, JSON_UNESCAPED_UNICODE);
}

/**
 * @param array<string,mixed> $displayPayload Shown on the verification page (job title, counts, …)
 * @return array{token: string, canonical_sha256: string, signature_hmac: string}|null
 */
function signedExportRegister(
    PDO $db,
    string $exportType,
    ?int $jobId,
    string $canonicalJsonString,
    array $displayPayload,
    ?int $issuedByUserId
): ?array {
    $canonicalSha = hash('sha256', $canonicalJsonString);
    $token = bin2hex(random_bytes(16));
    $sig = hash_hmac('sha256', $token . '|' . $canonicalSha, exportSigningSecret());
    $pj = json_encode($displayPayload, JSON_UNESCAPED_UNICODE);
    try {
        $st = $db->prepare('
            INSERT INTO signed_exports (token, export_type, job_id, canonical_sha256, signature_hmac, payload_json, issued_at, issued_by_user_id)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ');
        $st->execute([$token, $exportType, $jobId, $canonicalSha, $sig, $pj, $issuedByUserId]);
    } catch (Throwable $e) {
        error_log('signedExportRegister: ' . $e->getMessage());

        return null;
    }

    return ['token' => $token, 'canonical_sha256' => $canonicalSha, 'signature_hmac' => $sig];
}

function signedExportVerifyUrl(string $token): string {
    return rtrim(BASE_URL, '/') . '/verify-export.php?' . http_build_query(['t' => $token]);
}

/**
 * @return array{ok: bool, reason: string, row?: array<string,mixed>}
 */
function signedExportVerifyLookup(PDO $db, string $token): array {
    $token = strtolower(preg_replace('/[^a-f0-9]/', '', $token));
    if (strlen($token) !== 32) {
        return ['ok' => false, 'reason' => 'Invalid verification code format.'];
    }
    $st = $db->prepare('SELECT * FROM signed_exports WHERE token = ? LIMIT 1');
    $st->execute([$token]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['ok' => false, 'reason' => 'This verification code was not found. It may be incorrect, revoked, or never issued by this portal.'];
    }
    $expected = hash_hmac('sha256', $row['token'] . '|' . $row['canonical_sha256'], exportSigningSecret());
    if (!hash_equals((string)$row['signature_hmac'], $expected)) {
        return ['ok' => false, 'reason' => 'Record integrity check failed. Do not rely on this document.'];
    }

    return ['ok' => true, 'reason' => '', 'row' => $row];
}
