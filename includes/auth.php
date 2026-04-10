<?php
/**
 * Authentication Functions
 */

/**
 * Register new user
 */
function registerUser($email, $password, $firstName, $lastName, $phone = null, $roleId = 1) {
    $db = getDBConnection();
    
    // Check if email exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Create user
    $hashedPassword = hashPassword($password);
    $verificationToken = generateToken();
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO users (email, password, role_id, first_name, last_name, phone, verification_token)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$email, $hashedPassword, $roleId, $firstName, $lastName, $phone, $verificationToken]);
        $userId = $db->lastInsertId();
        
        // Create candidate profile if role is Candidate
        if ($roleId == 1) {
            $stmt = $db->prepare("INSERT INTO candidates (user_id) VALUES (?)");
            $stmt->execute([$userId]);
        }
        
        $db->commit();
        
        logAudit('user_registered', 'users', $userId);
        
        return ['success' => true, 'user_id' => $userId, 'verification_token' => $verificationToken];
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.email = ? AND u.is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['logged_in'] = true;
    
    logAudit('user_login', 'users', $user['user_id']);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Change password for the given user (self-service)
 */
function changeUserPassword($userId, $currentPassword, $newPassword) {
    $db = getDBConnection();

    // Fetch current user record
    $stmt = $db->prepare("SELECT user_id, password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }

    // Verify current password
    if (!verifyPassword($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }

    // Update to new password
    $newHash = hashPassword($newPassword);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$newHash, $userId]);

    logAudit('password_changed', 'users', $userId, null, ['changed_by' => 'self']);

    return ['success' => true];
}

/**
 * Admin reset password for any user
 * (authorization must be enforced by the caller, typically SysAdmin only)
 */
function adminResetUserPassword($targetUserId, $newPassword) {
    $db = getDBConnection();

    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->execute([$targetUserId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'message' => 'User not found'];
    }

    $newHash = hashPassword($newPassword);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$newHash, $targetUserId]);

    logAudit('password_reset_by_admin', 'users', $targetUserId, null, ['changed_by' => 'admin']);

    return ['success' => true];
}

/**
 * SysAdmin: permanently delete a candidate user account, profile, applications, uploads, and related rows.
 * Adjusts jobs.current_applications before cascade deletes. Cannot delete own account or non-candidates.
 *
 * @return array{success:bool,message?:string}
 */
function adminDeleteCandidateUser(int $targetUserId, int $actorUserId): array {
    if ($targetUserId < 1) {
        return ['success' => false, 'message' => 'Invalid user.'];
    }
    if ($targetUserId === $actorUserId) {
        return ['success' => false, 'message' => 'You cannot delete your own account.'];
    }

    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT u.user_id, u.email, u.first_name, u.last_name, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$targetUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    if (($user['role_name'] ?? '') !== 'Candidate') {
        return ['success' => false, 'message' => 'Only candidate accounts can be deleted with this action.'];
    }

    $stmt = $db->prepare('SELECT candidate_id, cv_path, certificates_path FROM candidates WHERE user_id = ?');
    $stmt->execute([$targetUserId]);
    $cand = $stmt->fetch(PDO::FETCH_ASSOC);
    $candidateId = $cand ? (int)$cand['candidate_id'] : 0;

    $jobDecrements = [];
    $pathsToDelete = [];
    if ($cand) {
        foreach (['cv_path', 'certificates_path'] as $col) {
            $p = trim((string)($cand[$col] ?? ''));
            if ($p !== '') {
                $pathsToDelete[$p] = true;
            }
        }
    }
    if ($candidateId > 0) {
        $stmt = $db->prepare('SELECT job_id, COUNT(*) AS c FROM applications WHERE candidate_id = ? GROUP BY job_id');
        $stmt->execute([$candidateId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $jobDecrements[(int)$row['job_id']] = (int)$row['c'];
        }
        $stmt = $db->prepare('SELECT cv_path, certificates_path FROM applications WHERE candidate_id = ?');
        $stmt->execute([$candidateId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach (['cv_path', 'certificates_path'] as $col) {
                $p = trim((string)($row[$col] ?? ''));
                if ($p !== '') {
                    $pathsToDelete[$p] = true;
                }
            }
        }
    }

    $snapshot = [
        'email' => $user['email'],
        'name' => trim((string)$user['first_name'] . ' ' . (string)$user['last_name']),
        'candidate_id' => $candidateId,
    ];

    try {
        $db->beginTransaction();
        foreach ($jobDecrements as $jobId => $cnt) {
            if ($jobId < 1 || $cnt < 1) {
                continue;
            }
            $u = $db->prepare('UPDATE jobs SET current_applications = GREATEST(0, current_applications - ?) WHERE job_id = ?');
            $u->execute([(int)$cnt, $jobId]);
        }
        foreach (array_keys($pathsToDelete) as $rel) {
            deleteUploadRelativePath($rel);
        }
        $db->prepare('DELETE FROM users WHERE user_id = ?')->execute([$targetUserId]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('adminDeleteCandidateUser: ' . $e->getMessage());

        return ['success' => false, 'message' => 'Could not delete the account. Please try again or check the server log.'];
    }

    logAudit('candidate_account_deleted', 'users', $targetUserId, $snapshot, null);

    return ['success' => true, 'message' => 'Candidate account, applications, and profile data were permanently removed.'];
}

/**
 * Logout user
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logAudit('user_logout', 'users', $_SESSION['user_id']);
    }
    
    session_unset();
    session_destroy();
    session_start();
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get candidate profile
 */
function getCandidateProfile($userId) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT c.*, u.email, u.first_name, u.last_name, u.phone
        FROM candidates c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Candidate + user fields by candidate_id (for staff read-only profile).
 *
 * @return array<string,mixed>|null
 */
function getCandidateProfileByCandidateId(int $candidateId): ?array {
    if ($candidateId < 1) {
        return null;
    }
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT c.*, u.user_id, u.email, u.first_name, u.last_name, u.phone
        FROM candidates c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.candidate_id = ?
    ");
    $stmt->execute([$candidateId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * HR/Management may view profiles of candidates who have at least one application.
 * SysAdmin may view any candidate profile.
 */
function staffCanViewCandidateProfile(int $candidateId): bool {
    $role = (string)($_SESSION['role_name'] ?? '');
    if ($role === 'SysAdmin') {
        return true;
    }
    if (!in_array($role, ['HR', 'Management'], true)) {
        return false;
    }
    if ($candidateId < 1) {
        return false;
    }
    $db = getDBConnection();
    $stmt = $db->prepare('SELECT 1 FROM applications WHERE candidate_id = ? LIMIT 1');
    $stmt->execute([$candidateId]);

    return (bool) $stmt->fetchColumn();
}

/** Max number of references a candidate may store on their profile. */
function candidateReferencesMax(): int {
    return 5;
}

/**
 * @return list<array<string,mixed>>
 */
function getCandidateReferences(int $candidateId): array {
    $db = getDBConnection();
    $stmt = $db->prepare('
        SELECT reference_id, full_name, job_title, organisation, email, phone, sort_order
        FROM candidate_references
        WHERE candidate_id = ?
        ORDER BY sort_order ASC, reference_id ASC
    ');
    $stmt->execute([$candidateId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $rows ?: [];
}

/**
 * Replace all references for a candidate. Rows with empty full_name are skipped.
 * @param list<array{full_name?:string,job_title?:string,organisation?:string,email?:string,phone?:string}> $rows
 * @return string|null Error message or null on success
 */
function saveCandidateReferences(int $candidateId, array $rows): ?string {
    $max = candidateReferencesMax();
    $db = getDBConnection();
    $strLen = static function (string $s): int {
        return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
    };
    $toInsert = [];
    foreach ($rows as $r) {
        if (count($toInsert) >= $max) {
            break;
        }
        $name = trim((string)($r['full_name'] ?? ''));
        if ($name === '') {
            continue;
        }
        if ($strLen($name) > 200) {
            return 'Each reference name must be at most 200 characters.';
        }
        $title = trim((string)($r['job_title'] ?? ''));
        $org = trim((string)($r['organisation'] ?? ''));
        $email = trim((string)($r['email'] ?? ''));
        $phone = trim((string)($r['phone'] ?? ''));
        if ($strLen($title) > 200) {
            return 'Job title / relationship is too long for one of your references.';
        }
        if ($strLen($org) > 255) {
            return 'Organisation name is too long for one of your references.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address for each reference that includes an email.';
        }
        if ($strLen($email) > 255 || $strLen($phone) > 50) {
            return 'Email or phone is too long for one of your references.';
        }
        $toInsert[] = [$name, $title !== '' ? $title : null, $org !== '' ? $org : null, $email !== '' ? $email : null, $phone !== '' ? $phone : null];
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM candidate_references WHERE candidate_id = ?')->execute([$candidateId]);
        $ins = $db->prepare('
            INSERT INTO candidate_references (candidate_id, full_name, job_title, organisation, email, phone, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $order = 0;
        foreach ($toInsert as $t) {
            $ins->execute([$candidateId, $t[0], $t[1], $t[2], $t[3], $t[4], $order]);
            $order++;
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        return 'Could not save references. If the problem persists, contact support.';
    }

    return null;
}

/**
 * Update candidate profile
 */
function updateCandidateProfile($userId, $data) {
    $db = getDBConnection();
    
    $fields = [];
    $values = [];
    
    $allowedFields = ['date_of_birth', 'gender', 'address', 'city', 'state', 'country', 'postal_code',
                      'cover_letter_template', 'skills', 'education', 'experience',
                      'professional_qualifications', 'o_level_qualifications', 'a_level_qualifications', 'other_certifications'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        return ['success' => false, 'message' => 'No fields to update'];
    }
    
    $values[] = $userId;
    
    $sql = "UPDATE candidates SET " . implode(', ', $fields) . " WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
    
    logAudit('profile_updated', 'candidates', $userId);
    
    return ['success' => true];
}
