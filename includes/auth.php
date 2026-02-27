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
 * Update candidate profile
 */
function updateCandidateProfile($userId, $data) {
    $db = getDBConnection();
    
    $fields = [];
    $values = [];
    
    $allowedFields = ['date_of_birth', 'address', 'city', 'state', 'country', 'postal_code', 
                      'cover_letter_template', 'skills', 'education', 'experience'];
    
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
