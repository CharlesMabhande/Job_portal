<?php
/**
 * Authentication API Endpoints
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        requireCSRFToken();
        
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        
        $errors = validateInput([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName
        ], [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'first_name' => 'required',
            'last_name' => 'required'
        ]);
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $result = registerUser($email, $password, $firstName, $lastName, $phone);
        
        if ($result['success']) {
            sendWelcomeEmail($email, $firstName);
        }
        
        echo json_encode($result);
        break;
        
    case 'login':
        requireCSRFToken();
        
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Determine redirect based on role
            $roleId = $result['user']['role_id'];
            $redirect = match($roleId) {
                1 => '/candidate/dashboard.php',
                2 => '/hr/dashboard.php',
                3 => '/management/dashboard.php',
                4 => '/admin/dashboard.php',
                default => '/dashboard.php'
            };
            $result['redirect'] = $redirect;
        }
        
        echo json_encode($result);
        break;
        
    case 'logout':
        logoutUser();
        echo json_encode(['success' => true, 'redirect' => '/index.php']);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
