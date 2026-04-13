<?php
/**
 * Helper Functions
 */

/**
 * Sanitize input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/** @return list<string> */
function candidateGenderOptions(): array {
    return ['Male', 'Female', 'Other'];
}

/**
 * Normalise posted gender to an allowed value, or null if missing/invalid.
 */
function normalizeCandidateGender(?string $value): ?string {
    $v = trim((string)$value);

    return in_array($v, candidateGenderOptions(), true) ? $v : null;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format date (legacy / custom format; default ISO-style for logs or APIs).
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) return '';
    $dt = new DateTime($date);
    return $dt->format($format);
}

/**
 * User-facing date only: dd/mm/yyyy (e.g. 07/04/2026).
 */
function formatDateDisplay($date) {
    if ($date === null || $date === '') {
        return '';
    }
    try {
        return (new DateTime($date))->format('d/m/Y');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * URL to stream the logged-in candidate's CV or qualifications (opens in browser by default; PDF inline).
 *
 * @param 'cv'|'certs' $which
 */
function candidateMyDocumentUrl(string $which, bool $download = false): string {
    $which = strtolower($which);
    if (!in_array($which, ['cv', 'certs'], true)) {
        return '#';
    }
    $q = ['doc' => $which];
    if ($download) {
        $q['download'] = '1';
    }

    return BASE_URL . '/candidate/my-document.php?' . http_build_query($q);
}

/**
 * User-facing date and time: dd/mm/yyyy HH:MM (24-hour).
 */
function formatDateTimeDisplay($date) {
    if ($date === null || $date === '') {
        return '';
    }
    try {
        return (new DateTime($date))->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Whole years since date of birth (as of today), or null if missing/invalid/future.
 */
function ageFromDateOfBirth(?string $dateYmd): ?int {
    if ($dateYmd === null || trim($dateYmd) === '') {
        return null;
    }
    try {
        $birth = new DateTime(substr((string)$dateYmd, 0, 10));
        $today = new DateTime('today');
        if ($birth > $today) {
            return null;
        }

        return (int)$birth->diff($today)->y;
    } catch (Exception $e) {
        return null;
    }
}

/** Max rows per qualification section on candidate profile forms. */
function candidateProfileQualificationSlotsMax(): int {
    return 12;
}

/** Max work experience rows on candidate profile. */
function candidateProfileWorkSlotsMax(): int {
    return 10;
}

/**
 * @return list<array<string,mixed>>
 */
function decodeCandidateProfileJsonList(?string $json): array {
    if ($json === null || trim((string)$json) === '') {
        return [];
    }
    $decoded = json_decode((string)$json, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Build JSON for qualification-style rows from parallel POST arrays (institution, title/subject, grade, year).
 *
 * @param array{0:string,1:string,2:string,3:string} $keys [inst, title, grade, year] POST field names (no [])
 * @param array<string,string> $extraPostByJsonKey JSON key => POST name for parallel arrays (e.g. month => ol_month)
 */
function encodeQualificationRowsFromPost(array $keys, int $max, array $extraPostByJsonKey = []): string {
    $inst = $_POST[$keys[0]] ?? [];
    $title = $_POST[$keys[1]] ?? [];
    $grade = $_POST[$keys[2]] ?? [];
    $year = $_POST[$keys[3]] ?? [];
    if (!is_array($inst)) {
        $inst = [];
    }
    if (!is_array($title)) {
        $title = [];
    }
    if (!is_array($grade)) {
        $grade = [];
    }
    if (!is_array($year)) {
        $year = [];
    }

    $extraArrays = [];
    foreach ($extraPostByJsonKey as $jsonKey => $postKey) {
        $arr = $_POST[$postKey] ?? [];
        $extraArrays[$jsonKey] = is_array($arr) ? $arr : [];
    }

    $plain = static function ($v): string {
        return trim(strip_tags((string)$v));
    };

    $rows = [];
    for ($i = 0; $i < $max; $i++) {
        $r = [
            'institution' => $plain($inst[$i] ?? ''),
            'title' => $plain($title[$i] ?? ''),
            'grade' => $plain($grade[$i] ?? ''),
            'year' => $plain($year[$i] ?? ''),
        ];
        foreach ($extraPostByJsonKey as $jsonKey => $_postKey) {
            $r[$jsonKey] = $plain($extraArrays[$jsonKey][$i] ?? '');
        }
        $allEmpty = true;
        foreach ($r as $v) {
            if ($v !== '') {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            continue;
        }
        $rows[] = $r;
    }

    return json_encode($rows, JSON_UNESCAPED_UNICODE);
}

/** HTML month dropdown for O/A-Level exam session (optional). */
function candidateExamMonthSelectHtml(string $inputName, string $selected = ''): string {
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $h = '<select name="' . escape($inputName) . '" class="form-select" title="Month of exam session (optional)">';
    $h .= '<option value="">Month (optional)</option>';
    $sel = trim($selected);
    foreach ($months as $m) {
        $h .= '<option value="' . escape($m) . '"' . ($sel === $m ? ' selected' : '') . '>' . escape($m) . '</option>';
    }
    $h .= '</select>';

    return $h;
}

/**
 * JSON for work experience from POST (employer, job title, start, end, current checkbox per index, description).
 */
function encodeWorkExperienceFromPost(int $max): string {
    $emp = $_POST['wx_employer'] ?? [];
    $job = $_POST['wx_job_title'] ?? [];
    $start = $_POST['wx_start'] ?? [];
    $end = $_POST['wx_end'] ?? [];
    $desc = $_POST['wx_description'] ?? [];
    $current = $_POST['wx_current'] ?? [];
    if (!is_array($emp)) {
        $emp = [];
    }
    if (!is_array($job)) {
        $job = [];
    }
    if (!is_array($start)) {
        $start = [];
    }
    if (!is_array($end)) {
        $end = [];
    }
    if (!is_array($desc)) {
        $desc = [];
    }
    if (!is_array($current)) {
        $current = [];
    }

    $plain = static function ($v): string {
        return trim(strip_tags((string)$v));
    };

    $rows = [];
    for ($i = 0; $i < $max; $i++) {
        $curVal = $current[$i] ?? '0';
        $r = [
            'employer' => $plain($emp[$i] ?? ''),
            'job_title' => $plain($job[$i] ?? ''),
            'start' => $plain($start[$i] ?? ''),
            'end' => $plain($end[$i] ?? ''),
            'current' => (string)$curVal === '1',
            'description' => $plain($desc[$i] ?? ''),
        ];
        if ($r['employer'] === '' && $r['job_title'] === '' && $r['start'] === '' && $r['end'] === '' && $r['description'] === '') {
            continue;
        }
        $rows[] = $r;
    }

    return json_encode($rows, JSON_UNESCAPED_UNICODE);
}

/**
 * After INSERT into applications, set a unique reference for tracking (e.g. LSU-2026-000015).
 */
function assignApplicationReference(PDO $db, int $applicationId): string {
    if ($applicationId < 1) {
        return '';
    }
    $stmt = $db->prepare('SELECT applied_at FROM applications WHERE application_id = ?');
    $stmt->execute([$applicationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $year = $row ? (new DateTime($row['applied_at']))->format('Y') : date('Y');
    $ref = 'LSU-' . $year . '-' . str_pad((string)$applicationId, 6, '0', STR_PAD_LEFT);
    $upd = $db->prepare('UPDATE applications SET application_ref = ? WHERE application_id = ?');
    $upd->execute([$ref, $applicationId]);
    return $ref;
}

/**
 * SQL fragment: job application deadline is still today or in the future (or no deadline set).
 * Pass table alias for joined queries, e.g. sqlJobApplicationOpen('j').
 */
function sqlJobApplicationOpen(string $alias = ''): string {
    $p = $alias !== '' ? $alias . '.' : '';
    return "({$p}application_deadline IS NULL OR {$p}application_deadline >= CURDATE())";
}

/**
 * True if the job row's application_deadline is before today (job should hide from public listings).
 */
function jobApplicationDeadlinePassed(array $jobRow): bool {
    $d = $jobRow['application_deadline'] ?? null;
    if ($d === null || $d === '') {
        return false;
    }
    $deadlineDate = substr((string)$d, 0, 10);
    return $deadlineDate < date('Y-m-d');
}

/**
 * Normalised vacancy scope: Internal (typically current staff) vs External (open to the public).
 */
function vacancyScope(?string $scope): string {
    $s = trim((string)($scope ?? ''));
    return ($s === 'Internal' || $s === 'External') ? $s : 'External';
}

/**
 * CSS class for Internal / External pill on listings and job detail.
 */
function vacancyScopeBadgeClass(?string $scope): string {
    return vacancyScope($scope) === 'Internal' ? 'badge-vacancy-internal' : 'badge-vacancy-external';
}

/**
 * Get user role name
 */
function getUserRoleName($roleId) {
    $roles = [
        1 => 'Candidate',
        2 => 'HR',
        3 => 'Management',
        4 => 'SysAdmin'
    ];
    return $roles[$roleId] ?? 'Unknown';
}

/**
 * Check if user has permission
 */
function hasPermission($permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $roleId = $_SESSION['role_id'] ?? 0;
    
    // SysAdmin has all permissions
    if ($roleId == 4) {
        return true;
    }
    
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT permissions FROM roles WHERE role_id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch();
    
    if ($role && $role['permissions']) {
        $permissions = json_decode($role['permissions'], true);
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    return false;
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: " . BASE_URL . $url);
    exit;
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Log audit trail
 */
function logAudit($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt->execute([
        $userId,
        $action,
        $tableName,
        $recordId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $ipAddress,
        $userAgent
    ]);
}

/**
 * Create notification
 */
function createNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_id, related_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $type, $title, $message, $relatedId, $relatedType]);
}

/**
 * Get user notifications
 */
function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
    $db = getDBConnection();
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    if ($unreadOnly) {
        $sql .= " AND is_read = 0";
    }
    $sql .= " ORDER BY created_at DESC LIMIT ?";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, (int)$userId, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Mark notification as read
 */
function markNotificationRead($notificationId) {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    $stmt->execute([$notificationId]);
}

/**
 * Maximum upload size in whole megabytes (for user-facing text; matches MAX_FILE_SIZE).
 */
function maxUploadSizeMb(): int {
    return (int) max(1, ceil((int) MAX_FILE_SIZE / (1024 * 1024)));
}

/**
 * Human-readable max file size label, e.g. "5 MB".
 */
function maxUploadSizeLabel(): string {
    return maxUploadSizeMb() . ' MB';
}

/**
 * User-facing message for PHP file upload error codes, or null for OK / no file.
 */
function fileUploadErrorMessage(int $errorCode): ?string {
    if ($errorCode === UPLOAD_ERR_OK || $errorCode === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $label = maxUploadSizeLabel();
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "That file is too large. Each file must be {$label} or smaller.";
        case UPLOAD_ERR_PARTIAL:
            return 'The upload did not finish. Please try again.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Server configuration error (missing temporary folder). Please contact support.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Could not save the file on the server. Please try again.';
        case UPLOAD_ERR_EXTENSION:
            return 'A server setting blocked this upload. Try a PDF file.';
        default:
            return 'File upload failed. Please try again.';
    }
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
    $allowedTypes = $allowedTypes ?? ALLOWED_FILE_TYPES;
    $maxSize = $maxSize ?? MAX_FILE_SIZE;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = fileUploadErrorMessage((int) $file['error']);
        return ['success' => false, 'message' => $msg ?? 'File upload error'];
    }
    
    if (($file['size'] ?? 0) > $maxSize) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is ' . maxUploadSizeLabel() . ' per file.'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes, true)) {
        $listed = implode(', ', array_map('strtoupper', $allowedTypes));
        return ['success' => false, 'message' => 'File type not allowed. Accepted: ' . $listed . '.'];
    }
    
    return ['success' => true];
}

/**
 * Upload file
 */
function uploadFile($file, $directory, $prefix = '', $allowedTypes = null, $maxSize = null) {
    $validation = validateFileUpload($file, $allowedTypes, $maxSize);
    if (!$validation['success']) {
        return $validation;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . time() . '_' . generateToken(8) . '.' . $ext;
    $filepath = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Public URL for candidate profile photo, or null if not set.
 */
function candidateProfilePhotoUrl(?string $relative): ?string {
    $relative = str_replace('\\', '/', trim((string)$relative));
    if ($relative === '' || preg_match('#\.\.|^/#', $relative)) {
        return null;
    }
    return BASE_URL . '/uploads/' . $relative;
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Remove a file under uploads/ from a stored relative path (cv/..., documents/...).
 * Silently ignores invalid paths or missing files.
 */
function deleteUploadRelativePath(?string $relative): void {
    if ($relative === null) {
        return;
    }
    $relative = str_replace('\\', '/', trim((string)$relative));
    if ($relative === '' || preg_match('#\.\.|^/#', $relative)) {
        return;
    }
    $uploadRoot = realpath(UPLOAD_DIR);
    if ($uploadRoot === false) {
        return;
    }
    $normalized = str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $full = realpath($uploadRoot . DIRECTORY_SEPARATOR . $normalized);
    if ($full !== false && strpos($full, $uploadRoot) === 0 && is_file($full)) {
        @unlink($full);
    }
}

/**
 * Example placeholder text for SysAdmin system_settings rows (shown when value is empty).
 */
function settingValuePlaceholder(string $settingKey): string {
    $map = [
        'site_name' => 'e.g. Lupane State University Job Portal',
        'site_email' => 'e.g. noreply@lsu.ac.zw',
        'max_file_size' => 'e.g. 5242880 (bytes; 5MB = 5242880)',
        'allowed_file_types' => 'e.g. pdf',
        'email_notifications_enabled' => 'e.g. 1 (on) or 0 (off)',
        'maintenance_mode' => 'e.g. 0 (normal) or 1 (maintenance)',
    ];
    return $map[$settingKey] ?? 'e.g. see description for expected format';
}
