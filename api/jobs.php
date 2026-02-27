<?php
/**
 * Jobs API Endpoints
 */

require_once '../config/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDBConnection();

switch ($action) {
    case 'list':
        $page = (int)($_GET['page'] ?? 1);
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        $search = $_GET['search'] ?? '';
        $jobType = $_GET['job_type'] ?? '';
        $status = $_GET['status'] ?? 'Active';
        
        $where = ["j.status = ?"];
        $params = [$status];
        
        if (!empty($search)) {
            $where[] = "(j.title LIKE ? OR j.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($jobType)) {
            $where[] = "j.job_type = ?";
            $params[] = $jobType;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM jobs j WHERE {$whereClause}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get jobs
        $sql = "
            SELECT j.*, 
                   u.first_name as posted_by_name,
                   u.last_name as posted_by_surname
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.user_id
            WHERE {$whereClause}
            ORDER BY j.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        // Bind all params; LIMIT/OFFSET must be bound as ints with native prepares.
        $bindIndex = 1;
        for ($i = 0; $i < count($params); $i++) {
            $val = $params[$i];
            $isLastTwo = ($i >= count($params) - 2);
            if ($isLastTwo) {
                $stmt->bindValue($bindIndex, (int)$val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($bindIndex, $val);
            }
            $bindIndex++;
        }
        $stmt->execute();
        $jobs = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'jobs' => $jobs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total
            ]
        ]);
        break;
        
    case 'get':
        $jobId = (int)($_GET['job_id'] ?? 0);
        
        $stmt = $db->prepare("
            SELECT j.*, 
                   u.first_name as posted_by_name,
                   u.last_name as posted_by_surname
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.user_id
            WHERE j.job_id = ?
        ");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if ($job) {
            echo json_encode(['success' => true, 'job' => $job]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Job not found']);
        }
        break;
        
    case 'create':
        requireLogin();
        requireRole(['HR', 'SysAdmin']);
        requireCSRFToken();
        
        $data = [
            'title' => sanitize($_POST['title'] ?? ''),
            'department' => sanitize($_POST['department'] ?? ''),
            'description' => $_POST['description'] ?? '',
            'requirements' => $_POST['requirements'] ?? '',
            'qualifications' => $_POST['qualifications'] ?? '',
            'location' => sanitize($_POST['location'] ?? ''),
            'job_type' => sanitize($_POST['job_type'] ?? 'Full-time'),
            'salary_min' => $_POST['salary_min'] ?? null,
            'salary_max' => $_POST['salary_max'] ?? null,
            'application_deadline' => $_POST['application_deadline'] ?? null,
            'max_applications' => (int)($_POST['max_applications'] ?? 0),
            'status' => 'Draft'
        ];
        
        $errors = validateInput($data, [
            'title' => 'required',
            'description' => 'required'
        ]);
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $data['posted_by'] = $_SESSION['user_id'];
        
        $sql = "INSERT INTO jobs (" . implode(', ', array_keys($data)) . ") VALUES (?" . str_repeat(', ?', count($data) - 1) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));
        
        $jobId = $db->lastInsertId();
        logAudit('job_created', 'jobs', $jobId, null, $data);
        
        echo json_encode(['success' => true, 'job_id' => $jobId]);
        break;
        
    case 'update':
        requireLogin();
        requireRole(['HR', 'SysAdmin']);
        requireCSRFToken();
        
        $jobId = (int)($_POST['job_id'] ?? 0);
        
        // Get old values
        $stmt = $db->prepare("SELECT * FROM jobs WHERE job_id = ?");
        $stmt->execute([$jobId]);
        $oldValues = $stmt->fetch();
        
        if (!$oldValues) {
            echo json_encode(['success' => false, 'message' => 'Job not found']);
            exit;
        }
        
        $data = [
            'title' => sanitize($_POST['title'] ?? $oldValues['title']),
            'department' => sanitize($_POST['department'] ?? $oldValues['department']),
            'description' => $_POST['description'] ?? $oldValues['description'],
            'requirements' => $_POST['requirements'] ?? $oldValues['requirements'],
            'qualifications' => $_POST['qualifications'] ?? $oldValues['qualifications'],
            'location' => sanitize($_POST['location'] ?? $oldValues['location']),
            'job_type' => sanitize($_POST['job_type'] ?? $oldValues['job_type']),
            'salary_min' => $_POST['salary_min'] ?? $oldValues['salary_min'],
            'salary_max' => $_POST['salary_max'] ?? $oldValues['salary_max'],
            'application_deadline' => $_POST['application_deadline'] ?? $oldValues['application_deadline'],
            'max_applications' => (int)($_POST['max_applications'] ?? $oldValues['max_applications']),
            'status' => sanitize($_POST['status'] ?? $oldValues['status'])
        ];
        
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $jobId;
        
        $sql = "UPDATE jobs SET " . implode(', ', $fields) . " WHERE job_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        logAudit('job_updated', 'jobs', $jobId, $oldValues, $data);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'delete':
        requireLogin();
        requireRole(['HR', 'SysAdmin']);
        requireCSRFToken();
        
        $jobId = (int)($_POST['job_id'] ?? 0);
        
        $stmt = $db->prepare("DELETE FROM jobs WHERE job_id = ?");
        $stmt->execute([$jobId]);
        
        logAudit('job_deleted', 'jobs', $jobId);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'approve':
        requireLogin();
        requireRole(['Management', 'SysAdmin']);
        requireCSRFToken();
        
        $jobId = (int)($_POST['job_id'] ?? 0);
        
        $stmt = $db->prepare("
            UPDATE jobs 
            SET status = 'Active', 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE job_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $jobId]);
        
        logAudit('job_approved', 'jobs', $jobId);
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
