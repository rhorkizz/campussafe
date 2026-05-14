<?php
/**
 * Officer Controller
 * Handles all officer-related operations
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/EmailService.php';
require_once __DIR__ . '/../models/User.php';

class OfficerController {
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
     * Get officer dashboard data
     * @return array Dashboard data
     */
    public function getDashboardData() {
        requireLogin();
        if (!hasRole('officer')) {
            redirect(app_url('index.php'));
        }

        if ($this->db === null) {
            return [
                'incidents' => [],
                'stats' => ['total_incidents' => 0, 'pending_incidents' => 0, 'resolved_incidents' => 0, 'in_progress_incidents' => 0],
                'officer_name' => $_SESSION['user_name'] ?? 'Demo User'
            ];
        }

        try {
            require_once __DIR__ . '/../models/Incident.php';
            $incidentModel = new Incident($this->db);
            $officer_id = $_SESSION['user_id'];

            // Get incidents assigned to this officer
            $incidents = $incidentModel->getIncidentsByOfficer($officer_id);

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
                'officer_name' => $_SESSION['user_name'] ?? ''
            ];
        } catch (Exception $e) {
            error_log("Get dashboard data error: " . $e->getMessage());
            return [
                'incidents' => [],
                'stats' => ['total_incidents' => 0, 'pending_incidents' => 0, 'resolved_incidents' => 0, 'in_progress_incidents' => 0],
                'officer_name' => $_SESSION['user_name'] ?? ''
            ];
        }
    }

    /**
     * Update incident status
     * @param int $incident_id Incident ID
     * @param string $status New status
     * @return array Result with success status and message
     */
    public function updateIncidentStatus($incident_id, $status) {
        requireLogin();
        if (!hasRole('officer')) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid or expired session token. Please reload the page and try again.'];
        }
        if ($this->db === null) {
            return ['success' => false, 'message' => 'Database not available (demo mode).'];
        }

        try {
            require_once __DIR__ . '/../models/Incident.php';
            $incidentModel = new Incident($this->db);

            $result = $incidentModel->updateStatus($incident_id, $status, $_SESSION['user_id']);

            if ($result) {
                // Fetch the student ID to send them a realistic school email
                $incident = $incidentModel->getIncidentById($incident_id);
                if ($incident && !empty($incident['reported_by'])) {
                    $studentEmail = $incident['reported_by'] . '@upsamail.edu.gh';
                    EmailService::notifyStudent($incident_id, $status, $studentEmail);
                }
                
                return ['success' => true, 'message' => 'Incident status updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update incident status'];
            }
        } catch (Exception $e) {
            error_log("Update incident status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    // Add more officer methods as needed
}
