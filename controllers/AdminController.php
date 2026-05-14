<?php
/**
 * Admin Controller
 * Handles all admin-related operations
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {
    private $db;
    private $userModel;

    public function __construct() {
        $this->db = getDBConnection();
        if ($this->db === null) {
            if (defined('BYPASS_LOGIN_DEMO') && BYPASS_LOGIN_DEMO) {
                $this->db = null;
                $this->userModel = null;
                return;
            }
            throw new Exception("Database connection failed. Please ensure the database 'campus_incident_system' exists and MySQL is running.");
        }
        $this->userModel = new User($this->db);
    }

    /**
     * Get admin dashboard data
     * @return array Dashboard data
     */
    public function getDashboardData() {
        requireLogin();
        if (!hasRole('admin')) {
            redirect('../../index.php');
        }

        if ($this->db === null) {
            return [
                'incidents' => [],
                'stats' => ['total_incidents' => 0, 'pending_incidents' => 0, 'resolved_incidents' => 0, 'in_progress_incidents' => 0],
                'admin_name' => $_SESSION['user_name'] ?? 'Demo User'
            ];
        }

        try {
            require_once __DIR__ . '/../models/Incident.php';
            require_once __DIR__ . '/../models/User.php';
            $incidentModel = new Incident($this->db);

            // Get all incidents for admin view
            $incidents = $incidentModel->getAllIncidents();
            
            // Get statistics (handle both old and new status formats)
            $stats = [
                'total_incidents' => count($incidents),
                'pending_incidents' => count(array_filter($incidents, function($i) { 
                    $status = strtolower($i['status'] ?? '');
                    return $status === 'pending' || $status === 'Pending';
                })),
                'in_progress_incidents' => count(array_filter($incidents, function($i) { 
                    $status = strtolower($i['status'] ?? '');
                    return $status === 'in_progress' || $status === 'In Progress';
                })),
                'resolved_incidents' => count(array_filter($incidents, function($i) { 
                    $status = strtolower($i['status'] ?? '');
                    return $status === 'resolved' || $status === 'Resolved';
                })),
                // Aggregated data for charts
                'by_category' => array_count_values(array_map(function($i) {
                    return $i['category_name'] ?? 'Uncategorized';
                }, $incidents)),
                'by_status' => array_count_values(array_map(function($i) {
                    return ucfirst(str_replace('_', ' ', $i['status'] ?? 'Unknown'));
                }, $incidents))
            ];

            return [
                'incidents' => $incidents,
                'stats' => $stats,
                'admin_name' => $_SESSION['user_name'] ?? ''
            ];
        } catch (Exception $e) {
            error_log("Get dashboard data error: " . $e->getMessage());
            return [
                'incidents' => [],
                'stats' => ['total_incidents' => 0, 'pending_incidents' => 0, 'resolved_incidents' => 0, 'in_progress_incidents' => 0],
                'admin_name' => $_SESSION['user_name'] ?? ''
            ];
        }
    }

    /**
     * Assign incident to officer
     * @param int $incident_id Incident ID
     * @param int $officer_id Officer ID
     * @return array Result with success status and message
     */
    public function assignIncident($incident_id, $officer_id) {
        requireLogin();
        if (!hasRole('admin')) {
            return ['success' => false, 'message' => 'Access denied'];
        }
        if ($this->db === null) {
            return ['success' => false, 'message' => 'Database not available (demo mode).'];
        }

        try {
            require_once __DIR__ . '/../models/Incident.php';
            $incidentModel = new Incident($this->db);

            $result = $incidentModel->assignOfficer($incident_id, $officer_id);

            if ($result) {
                return ['success' => true, 'message' => 'Incident assigned successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to assign incident'];
            }
        } catch (Exception $e) {
            error_log("Assign incident error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    /**
     * Delete an incident (Soft Delete)
     * @param int $incident_id Incident ID
     * @return array Result with success status and message
     */
    public function deleteIncident($incident_id) {
        requireLogin();
        if (!hasRole('admin')) {
            return ['success' => false, 'message' => 'Access denied'];
        }
        if ($this->db === null) {
            return ['success' => false, 'message' => 'Database not available (demo mode).'];
        }

        try {
            require_once __DIR__ . '/../models/Incident.php';
            $incidentModel = new Incident($this->db);

            $result = $incidentModel->updateStatus($incident_id, 'Deleted', $_SESSION['user_id']);

            if ($result) {
                return ['success' => true, 'message' => 'Incident deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete incident'];
            }
        } catch (Exception $e) {
            error_log("Delete incident error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    /**
     * Get data for user management page
     */
    public function getUsersPageData($roleFilter = null) {
        requireLogin();
        if (!hasRole('admin')) redirect('../../index.php');
        if ($this->db === null) {
            return ['users' => [], 'departments' => [], 'roles' => [], 'admin_name' => $_SESSION['user_name'] ?? 'Demo User'];
        }
        require_once __DIR__ . '/../models/Department.php';
        $deptModel = new Department($this->db);
        $roleId = $roleFilter !== null && $roleFilter !== '' ? (int)$roleFilter : null;
        return [
            'users' => $this->userModel->getAllUsers($roleId),
            'departments' => $deptModel->getAllDepartments(),
            'roles' => [
                ['role_id' => 1, 'role_name' => 'Student'],
                ['role_id' => 2, 'role_name' => 'Campus Officer'],
                ['role_id' => 3, 'role_name' => 'Hostel Officer']
            ],
            'admin_name' => $_SESSION['user_name'] ?? ''
        ];
    }

    /**
     * Add a new user (student or officer)
     */
    public function addUser() {
        requireLogin();
        if (!hasRole('admin')) return ['success' => false, 'message' => 'Access denied'];
        if ($this->db === null) return ['success' => false, 'message' => 'Database not available (demo mode).'];
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid or expired session token. Please reload the page and try again.'];
        }

        $user_id = sanitizeInput($_POST['user_id'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role_id = (int)($_POST['role_id'] ?? 1);
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $password = $_POST['password'] ?? '';
        if (empty($user_id) || empty($full_name) || empty($password)) {
            return ['success' => false, 'message' => 'User ID, full name and password are required.'];
        }
        if (strlen($user_id) > 20) return ['success' => false, 'message' => 'User ID must be 20 characters or less.'];
        if ($this->userModel->userExists($user_id)) {
            return ['success' => false, 'message' => 'User ID already exists.'];
        }
        if ($role_id === 2 || $role_id === 3) {
            if (empty($department_id)) return ['success' => false, 'message' => 'Department is required for officers.'];
        }
        $ok = $this->userModel->createUser([
            'user_id' => $user_id,
            'full_name' => $full_name,
            'role_id' => $role_id,
            'department_id' => $department_id,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        return $ok ? ['success' => true, 'message' => 'User added successfully.'] : ['success' => false, 'message' => 'Failed to add user.'];
    }

    /**
     * Deactivate (remove) a user
     */
    public function deactivateUser($user_id) {
        requireLogin();
        if (!hasRole('admin')) return ['success' => false, 'message' => 'Access denied'];
        if ($this->db === null) return ['success' => false, 'message' => 'Database not available (demo mode).'];
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid or expired session token. Please reload the page and try again.'];
        }

        $user_id = trim($user_id);
        if (empty($user_id)) return ['success' => false, 'message' => 'Invalid user.'];
        if ($_SESSION['user_id'] === $user_id) return ['success' => false, 'message' => 'You cannot remove yourself.'];
        $ok = $this->userModel->deactivateUser($user_id);
        return $ok ? ['success' => true, 'message' => 'User removed.'] : ['success' => false, 'message' => 'Failed to remove user or user is protected.'];
    }

    /**
     * Bulk import users from CSV
     * CSV format: user_id,full_name,role,department_id,password
     * role: student, campus_officer, hostel_officer
     */
    public function bulkImportUsers() {
        requireLogin();
        if (!hasRole('admin')) return ['success' => false, 'message' => 'Access denied'];
        if ($this->db === null) return ['success' => false, 'message' => 'Database not available (demo mode).'];
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid or expired session token. Please reload the page and try again.'];
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Please upload a valid CSV file.'];
        }
        $lines = file($_FILES['csv_file']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return ['success' => false, 'message' => 'CSV file is empty.'];
        $added = 0;
        $skipped = 0;
        $errors = [];
        foreach ($lines as $i => $line) {
            $row = str_getcsv($line);
            if (count($row) < 3) { $skipped++; continue; }
            if ($i === 0 && strtolower(trim($row[0])) === 'user_id') continue; // skip header
            $user_id = trim($row[0]);
            $full_name = trim($row[1]);
            $role = strtolower(trim($row[2]));
            $department_id = isset($row[3]) && $row[3] !== '' ? (int)$row[3] : null;
            $password = isset($row[4]) && $row[4] !== '' ? trim($row[4]) : 'password123';
            if (empty($user_id) || empty($full_name)) { $skipped++; continue; }
            if ($this->userModel->userExists($user_id)) { $skipped++; $errors[] = "Row " . ($i+1) . ": $user_id already exists."; continue; }
            $roleMap = ['student' => 1, 'campus_officer' => 2, 'hostel_officer' => 3];
            $role_id = $roleMap[$role] ?? 1;
            if ($role_id === 2 || $role_id === 3) $department_id = $department_id ?: 1;
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($this->userModel->createUser(['user_id' => $user_id, 'full_name' => $full_name, 'role_id' => $role_id, 'department_id' => $department_id, 'password' => $hashedPassword])) {
                $added++;
            } else {
                $skipped++;
            }
        }
        $msg = "$added user(s) added.";
        if ($skipped) $msg .= " $skipped skipped.";
        if (!empty($errors)) $msg .= " " . implode(' ', array_slice($errors, 0, 5));
        return ['success' => true, 'message' => $msg];
    }
}
