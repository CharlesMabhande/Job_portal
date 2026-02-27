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
 * Format date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) return '';
    $dt = new DateTime($date);
    return $dt->format($format);
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
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
    $allowedTypes = $allowedTypes ?? ALLOWED_FILE_TYPES;
    $maxSize = $maxSize ?? MAX_FILE_SIZE;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    return ['success' => true];
}

/**
 * Upload file
 */
function uploadFile($file, $directory, $prefix = '') {
    $validation = validateFileUpload($file);
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
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}
