<?php
/**
 * Applications API Endpoints
 */

require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDBConnection();

switch ($action) {
    case 'apply':
        requireCSRFToken();
        requireRole(['Candidate']);
        
        $jobId = (int)($_POST['job_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        
        // Get candidate_id
        $stmt = $db->prepare("SELECT candidate_id FROM candidates WHERE user_id = ?");
        $stmt->execute([$userId]);
        $candidate = $stmt->fetch();
        
        if (!$candidate) {
            echo json_encode(['success' => false, 'message' => 'Candidate profile not found']);
            exit;
        }
        
        $candidateId = $candidate['candidate_id'];
        
        // Check if already applied
        $stmt = $db->prepare("SELECT application_id FROM applications WHERE job_id = ? AND candidate_id = ?");
        $stmt->execute([$jobId, $candidateId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
            exit;
        }
        
        // Check job status
        $stmt = $db->prepare("SELECT status, max_applications, current_applications FROM jobs WHERE job_id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if (!$job || $job['status'] !== 'Active') {
            echo json_encode(['success' => false, 'message' => 'Job is not available']);
            exit;
        }
        
        if ($job['max_applications'] > 0 && $job['current_applications'] >= $job['max_applications']) {
            echo json_encode(['success' => false, 'message' => 'Maximum applications reached']);
            exit;
        }
        
        // Validate required fields
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        if ($coverLetter === '') {
            echo json_encode(['success' => false, 'message' => 'Cover letter is required']);
            exit;
        }

        // Handle CV upload (required)
        if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'CV upload is required']);
            exit;
        }
        $cvUpload = uploadFile($_FILES['cv'], CV_DIR, 'cv');
        if (!$cvUpload['success']) {
            echo json_encode(['success' => false, 'message' => $cvUpload['message'] ?? 'CV upload failed']);
            exit;
        }
        $cvPath = 'cv/' . $cvUpload['filename'];

        // Qualifications / certificates document (required)
        if (!isset($_FILES['certificates']) || $_FILES['certificates']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Qualifications document upload is required']);
            exit;
        }
        $certUpload = uploadFile($_FILES['certificates'], DOCS_DIR, 'certs');
        if (!$certUpload['success']) {
            echo json_encode(['success' => false, 'message' => $certUpload['message'] ?? 'Certificates upload failed']);
            exit;
        }
        $certificatesPath = 'documents/' . $certUpload['filename'];
        
        // Create application
        $stmt = $db->prepare("
            INSERT INTO applications (job_id, candidate_id, cover_letter, cv_path, certificates_path, status)
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->execute([$jobId, $candidateId, $coverLetter, $cvPath, $certificatesPath]);
        $applicationId = $db->lastInsertId();
        
        // Update job application count
        $stmt = $db->prepare("UPDATE jobs SET current_applications = current_applications + 1 WHERE job_id = ?");
        $stmt->execute([$jobId]);
        
        // Get job title for notification
        $stmt = $db->prepare("SELECT title FROM jobs WHERE job_id = ?");
        $stmt->execute([$jobId]);
        $jobTitle = $stmt->fetch()['title'];
        
        // Create notification
        createNotification($userId, 'application_submitted', 'Application Submitted', 
            "Your application for {$jobTitle} has been submitted successfully", $applicationId, 'application');
        
        // Send email
        sendApplicationConfirmation($_SESSION['email'], $_SESSION['first_name'], $jobTitle);
        
        logAudit('application_created', 'applications', $applicationId);
        
        echo json_encode(['success' => true, 'application_id' => $applicationId]);
        break;
        
    case 'list':
        $roleId = $_SESSION['role_id'];
        $page = (int)($_GET['page'] ?? 1);
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        if ($roleId == 1) { // Candidate
            $userId = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT candidate_id FROM candidates WHERE user_id = ?");
            $stmt->execute([$userId]);
            $candidate = $stmt->fetch();
            $candidateId = $candidate['candidate_id'];
            
            $sql = "
                SELECT a.*, j.title as job_title, j.department, j.location
                FROM applications a
                JOIN jobs j ON a.job_id = j.job_id
                WHERE a.candidate_id = ?
                ORDER BY a.applied_at DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, (int)$candidateId, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
        } else { // HR, Management, SysAdmin
            $sql = "
                SELECT a.*, 
                       j.title as job_title, 
                       j.department,
                       u.first_name as candidate_first_name,
                       u.last_name as candidate_last_name,
                       u.email as candidate_email
                FROM applications a
                JOIN jobs j ON a.job_id = j.job_id
                JOIN candidates c ON a.candidate_id = c.candidate_id
                JOIN users u ON c.user_id = u.user_id
                ORDER BY a.applied_at DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $applications = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'applications' => $applications]);
        break;
        
    case 'update_status':
        requireRole(['HR', 'SysAdmin']);
        requireCSRFToken();
        
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $notes = $_POST['notes'] ?? '';
        $rejectionReason = $_POST['rejection_reason'] ?? '';
        
        // Get application details
        $stmt = $db->prepare("
            SELECT a.*, j.title as job_title, c.user_id as candidate_user_id, u.email, u.first_name
            FROM applications a
            JOIN jobs j ON a.job_id = j.job_id
            JOIN candidates c ON a.candidate_id = c.candidate_id
            JOIN users u ON c.user_id = u.user_id
            WHERE a.application_id = ?
        ");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch();
        
        if (!$application) {
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            exit;
        }
        
        // Update application
        $stmt = $db->prepare("
            UPDATE applications 
            SET status = ?, 
                review_notes = ?, 
                rejection_reason = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE application_id = ?
        ");
        $stmt->execute([$status, $notes, $rejectionReason, $_SESSION['user_id'], $applicationId]);
        
        // Create notification
        createNotification($application['candidate_user_id'], 'application_status_changed', 
            'Application Status Updated', 
            "Your application for {$application['job_title']} has been updated to: {$status}",
            $applicationId, 'application');
        
        // Send email
        sendStatusUpdateEmail($application['email'], $application['first_name'], 
            $application['job_title'], $status);
        
        logAudit('application_status_updated', 'applications', $applicationId);
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
