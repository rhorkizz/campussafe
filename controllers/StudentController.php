<?php
/**
 * Student Controller
 * Handles all student-related operations
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/EmailService.php';
require_once __DIR__ . '/../models/User.php';

class StudentController {
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
     * Handle user login
     * Checks user credentials and redirects to appropriate dashboard
     */
    public function login() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid or expired session token. Please reload the page and try again.'];
        }

        // Get and sanitize input
        $user_id = sanitizeInput($_POST['user_id'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        if (empty($user_id) || empty($password)) {
            return ['success' => false, 'message' => 'Please fill in all fields'];
        }

        try {
            // Get user from database using prepared statement
            $user = $this->userModel->getUserById($user_id);

            // Verify user exists and password is correct
            // Note: In your schema, passwords might be plain text (like dates) for students
            // For production, all passwords should be hashed
            $passwordMatch = false;
            $storedHash = isset($user['password']) ? trim((string) $user['password']) : '';
            if ($user && $storedHash !== '' && password_verify($password, $storedHash)) {
                $passwordMatch = true;
            }
            
            if ($user && $passwordMatch) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'] ?? '';
                $_SESSION['must_change_password'] = $user['must_change_password'];

                // Redirect based on role
                if ($user['must_change_password'] == 1) {
                    redirect('views/change_password.php');
                }

                if ($user['role'] === 'student') {
                    redirect('views/student/dashboard.php');
                } elseif ($user['role'] === 'officer') {
                    redirect('views/officer/dashboard.php');
                } elseif ($user['role'] === 'admin') {
                    redirect('views/admin/dashboard.php');
                }
            } else {
                return ['success' => false, 'message' => 'Invalid user ID or password'];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    /**
     * Submit incident report
     * Handles form submission for incident reporting
     */
    public function submitIncident() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        requireLogin();
        if (!hasRole('student')) {
            redirect(BASE_URL . '/views/student/dashboard.php');
        }
        if ($this->db === null) {
            return ['success' => false, 'message' => 'Database not available (demo mode).'];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid or expired session token. Please reload the page and try again.'];
        }

        // Rate Limit check: Max 3 incidents per 5 minutes (300 seconds)
        if (!checkRateLimit('submit_incident', 3, 300)) {
            return ['success' => false, 'message' => 'You are submitting incidents too quickly. Please wait a few minutes before trying again.'];
        }

        // Get and sanitize input
        $title = sanitizeInput($_POST['title'] ?? '');
        $category_id = sanitizeInput($_POST['category_id'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $location_type = sanitizeInput($_POST['location_type'] ?? '');
        $location = sanitizeInput($_POST['specific_location'] ?? '');
        $is_anonymous = ($_POST['is_anonymous'] ?? '0') === '1' ? 1 : 0;

        // Validate input
        if (empty($title)) {
            return ['success' => false, 'message' => 'Please provide an incident title'];
        }

        if (empty($category_id)) {
            return ['success' => false, 'message' => 'Please select a category'];
        }

        if (empty($description)) {
            return ['success' => false, 'message' => 'Please provide a description'];
        }

        if (empty($location_type)) {
            return ['success' => false, 'message' => 'Please select a location area'];
        }

        if (empty($location)) {
            return ['success' => false, 'message' => 'Please provide a specific location'];
        }

        // Get priority (default to 'medium' if not provided)
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        if (!in_array($priority, ['low', 'medium', 'high', 'critical'])) {
            $priority = 'medium';
        }

        $attachment_path = null;
        $allowed_types = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $_FILES['attachment']['tmp_name']);
            finfo_close($fi);
            if (!in_array($mime, $allowed_types, true)) {
                return ['success' => false, 'message' => 'Invalid image type. Use JPG, PNG, GIF or WebP only.'];
            }
            if ($_FILES['attachment']['size'] > $max_size) {
                return ['success' => false, 'message' => 'Image too large. Maximum size is 5 MB.'];
            }
            $upload_dir = __DIR__ . '/../uploads/incidents';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = 'incident_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
            $dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                $attachment_path = 'uploads/incidents/' . $filename;
            }
        } elseif (!empty($_FILES['attachment']['error']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'Error uploading image. Please try again.'];
        }

        try {
            require_once __DIR__ . '/../models/Incident.php';
            $incidentModel = new Incident($this->db);

            $student_id = $_SESSION['user_id'];

            $incidentData = [
                'title' => $title,
                'student_id' => $student_id,
                'category_id' => $category_id,
                'description' => $description,
                'is_anonymous' => $is_anonymous,
                'location' => $location,
                'location_type' => $location_type,
                'priority' => $priority,
                'attachment_path' => $attachment_path
            ];

            $result = $incidentModel->createIncident($incidentData);

            if ($result) {
                // $result is the new incident ID
                
                // Find all officers assigned to this incident's role and email them dynamically
                $incident = $incidentModel->getIncidentById($result);
                if ($incident) {
                    $role_id = $incident['assigned_role_id'];
                    $stmt = $this->db->prepare("SELECT user_id FROM users WHERE role_id = ?");
                    $stmt->execute([$role_id]);
                    $officers = $stmt->fetchAll();
                    
                    foreach ($officers as $officer) {
                        $officerEmail = $officer['user_id'] . '@upsamail.edu.gh';
                        EmailService::notifyOfficer($result, $title, $priority, $officerEmail);
                    }
                }

                setFlashMessage('Incident reported successfully!', 'success');
                redirect(BASE_URL . '/views/student/dashboard.php');
            } else {
                return ['success' => false, 'message' => 'Failed to submit incident. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("Submit incident error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get student dashboard data
     * @return array Dashboard data
     */
    public function getDashboardData() {
        requireLogin();
        if (!hasRole('student')) {
            redirect('../../index.php');
        }

        if ($this->db === null) {
            return ['incidents' => [], 'student_name' => $_SESSION['user_name'] ?? 'Demo User'];
        }

        try {
            require_once __DIR__ . '/../models/Incident.php';
            $incidentModel = new Incident($this->db);
            $student_id = $_SESSION['user_id'];

            // Get student's incidents
            $incidents = $incidentModel->getIncidentsByStudent($student_id);

            // Calculate stats for topbar
            $stats = [
                'total_incidents' => count($incidents),
                'pending_incidents' => count(array_filter($incidents, function($i) { 
                    $status = strtolower($i['status'] ?? '');
                    return $status === 'pending' || $status === 'submitted';
                }))
            ];

            return [
                'incidents' => $incidents,
                'stats' => $stats,
                'student_name' => $_SESSION['user_name'] ?? ''
            ];
        } catch (Exception $e) {
            error_log("Get dashboard data error: " . $e->getMessage());
            return ['incidents' => [], 'student_name' => $_SESSION['user_name'] ?? ''];
        }
    }
}
