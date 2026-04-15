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
        $gender = normalizeCandidateGender($_POST['gender'] ?? '');
        $nationalIdNumber = sanitize($_POST['national_id_number'] ?? '');
        $dateOfBirth = sanitize($_POST['date_of_birth'] ?? '');
        
        $errors = validateInput([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'national_id_number' => $nationalIdNumber,
            'date_of_birth' => $dateOfBirth
        ], [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'first_name' => 'required',
            'last_name' => 'required',
            'national_id_number' => 'required',
            'date_of_birth' => 'required'
        ]);
        if ($gender === null) {
            $errors['gender'] = 'Please select a valid gender.';
        }
        if ($dateOfBirth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
            $errors['date_of_birth'] = 'Please enter a valid Date of Birth.';
        }
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $result = registerUser($email, $password, $firstName, $lastName, $phone, 1, $gender, $nationalIdNumber, $dateOfBirth);
        
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
