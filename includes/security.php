<?php
/**
 * Security Functions
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = generateToken();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Require CSRF token
 */
function requireCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Require login
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/login.php', 'Please login to continue', 'error');
    }
}

/**
 * Require role
 */
function requireRole($allowedRoles) {
    requireLogin();
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    $userRole = $_SESSION['role_id'] ?? 0;
    
    // SysAdmin can access everything
    if ($userRole == 4) {
        return;
    }
    
    $roleMap = [
        'Candidate' => 1,
        'HR' => 2,
        'Management' => 3,
        'SysAdmin' => 4
    ];
    
    $allowedRoleIds = [];
    foreach ($allowedRoles as $role) {
        if (isset($roleMap[$role])) {
            $allowedRoleIds[] = $roleMap[$role];
        }
    }
    
    if (!in_array($userRole, $allowedRoleIds)) {
        redirect('/dashboard.php', 'Access denied', 'error');
    }
}

/**
 * Sanitize output
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate input
 */
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        $ruleArray = explode('|', $rule);
        
        foreach ($ruleArray as $singleRule) {
            if ($singleRule === 'required' && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                break;
            }
            
            if (strpos($singleRule, 'min:') === 0 && strlen($value) < (int)substr($singleRule, 4)) {
                $errors[$field] = ucfirst($field) . ' must be at least ' . substr($singleRule, 4) . ' characters';
            }
            
            if (strpos($singleRule, 'max:') === 0 && strlen($value) > (int)substr($singleRule, 4)) {
                $errors[$field] = ucfirst($field) . ' must not exceed ' . substr($singleRule, 4) . ' characters';
            }
            
            if ($singleRule === 'email' && !isValidEmail($value)) {
                $errors[$field] = 'Invalid email address';
            }
        }
    }
    
    return $errors;
}
