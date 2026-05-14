<?php
/**
 * Incident Model
 * Handles all database operations related to incidents
 */

class Incident {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Create new incident
     * @param array $incidentData Incident data
     * @return bool|int Incident ID on success, false on failure
     */
    public function createIncident($incidentData) {
        if ($this->db === null) {
            return false;
        }
        try {
            // Routing Logic:
            // 1. Check Location Type first
            $location_type = $incidentData['location_type'] ?? '';
            $assigned_role_id = null;

            if ($location_type === 'Hostel') {
                $assigned_role_id = 3; // Hostel Officer (Role ID 3)
            } else {
                $assigned_role_id = 2; // Campus Officer (Role ID 2) for all other locations
            }

            // Fallback to Category Routing only if needed (though the above covers everything now)
            // If we wanted to mix them, we could, but user asked for location to override.
            // So we strictly use location.

            $stmt = $this->db->prepare("
                INSERT INTO incidents (title, description, category_id, location, is_anonymous, reported_by, assigned_role_id, status, attachment_path, priority) 
                VALUES (:title, :description, :category_id, :location, :is_anonymous, :reported_by, :assigned_role_id, :status, :attachment_path, :priority)
            ");
            
            $result = $stmt->execute([
                'title'           => $incidentData['title'] ?? mb_substr($incidentData['description'], 0, 255),
                'description'     => $incidentData['description'],
                'category_id'     => ($incidentData['category_id'] === 'other') ? null : $incidentData['category_id'],
                'location'        => $incidentData['location'] ?? 'Campus',
                'is_anonymous'    => $incidentData['is_anonymous'] ?? 0,
                'reported_by'     => $incidentData['student_id'],
                'assigned_role_id'=> $assigned_role_id,
                'status'          => 'Pending',
                'attachment_path' => $incidentData['attachment_path'] ?? null,
                'priority'        => $incidentData['priority'] ?? 'medium'
            ]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create incident error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get incident by ID
     * @param int $incident_id Incident ID
     * @return array|null Incident data or null if not found
     */
    public function getIncidentById($incident_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT i.incident_id as id, i.*, 
                       c.category_name,
                       u.full_name as student_name,
                       d.department_name
                FROM incidents i
                LEFT JOIN incident_categories c ON i.category_id = c.category_id
                LEFT JOIN users u ON i.reported_by = u.user_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE i.incident_id = :incident_id
            ");
            $stmt->execute(['incident_id' => $incident_id]);
            $incident = $stmt->fetch();
            
            if ($incident) {
                // Apply anonymity mask
                $incident['student_name'] = $incident['is_anonymous'] ? 'Anonymous' : ($incident['student_name'] ?? 'Anonymous');
            }
            
            return $incident;
        } catch (PDOException $e) {
            error_log("Get incident by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get incidents by student ID
     * @param string $student_id Student ID
     * @return array Array of incidents
     */
    public function getIncidentsByStudent($student_id) {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare("
                SELECT i.*, 
                       c.category_name,
                       r.role_name as assigned_role_name
                FROM incidents i
                LEFT JOIN incident_categories c ON i.category_id = c.category_id
                LEFT JOIN roles r ON i.assigned_role_id = r.role_id
                WHERE i.reported_by = :student_id AND i.status != 'Deleted'
                ORDER BY i.created_at DESC
            ");
            $stmt->execute(['student_id' => $student_id]);
            $incidents = $stmt->fetchAll();
            // Map fields for backward compatibility
            foreach ($incidents as &$incident) {
                $incident['id'] = $incident['incident_id'];
                $incident['category_name'] = $incident['category_name'] ?? 'N/A';
                $incident['status'] = strtolower(str_replace(' ', '_', $incident['status']));
            }
            return $incidents;
        } catch (PDOException $e) {
            error_log("Get incidents by student error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get incidents by officer ID
     * @param string $officer_id Officer ID
     * @return array Array of incidents
     */
    public function getIncidentsByOfficer($officer_id) {
        if ($this->db === null) {
            return [];
        }
        try {
            // Get officer's role_id first
            $stmt = $this->db->prepare("SELECT role_id FROM users WHERE user_id = :officer_id");
            $stmt->execute(['officer_id' => $officer_id]);
            $officer = $stmt->fetch();
            
            if (!$officer) {
                return [];
            }
            
            // Get incidents assigned to this officer's role or directly to the officer
            $stmt = $this->db->prepare("
                SELECT i.*, 
                       c.category_name,
                       u.full_name as student_name
                FROM incidents i
                LEFT JOIN incident_categories c ON i.category_id = c.category_id
                LEFT JOIN users u ON i.reported_by = u.user_id
                WHERE (i.assigned_role_id = :role_id OR i.assigned_user_id = :officer_id)
                  AND i.status != 'Deleted'
                ORDER BY i.created_at DESC
            ");
            $stmt->execute([
                'role_id' => $officer['role_id'],
                'officer_id' => $officer_id
            ]);
            $incidents = $stmt->fetchAll();
            // Map fields for backward compatibility
            foreach ($incidents as &$incident) {
                $incident['id'] = $incident['incident_id'];
                $incident['category_name'] = $incident['category_name'] ?? 'N/A';
                $incident['student_name'] = $incident['is_anonymous'] ? 'Anonymous' : ($incident['student_name'] ?? 'Anonymous');
                $incident['status'] = strtolower(str_replace(' ', '_', $incident['status']));
            }
            return $incidents;
        } catch (PDOException $e) {
            error_log("Get incidents by officer error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all incidents
     * @return array Array of all incidents
     */
    public function getAllIncidents() {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare("
                SELECT i.*, 
                       c.category_name,
                       u.full_name as student_name,
                       o.full_name as officer_name,
                       r.role_name as assigned_role_name
                FROM incidents i
                LEFT JOIN incident_categories c ON i.category_id = c.category_id
                LEFT JOIN users u ON i.reported_by = u.user_id
                LEFT JOIN users o ON i.assigned_user_id = o.user_id
                LEFT JOIN roles r ON i.assigned_role_id = r.role_id
                WHERE i.status != 'Deleted'
                ORDER BY i.created_at DESC
            ");
            $stmt->execute();
            $incidents = $stmt->fetchAll();
            // Map fields for backward compatibility
            foreach ($incidents as &$incident) {
                $incident['id'] = $incident['incident_id'];
                $incident['category_name'] = $incident['category_name'] ?? 'N/A';
                $incident['student_name'] = $incident['is_anonymous'] ? 'Anonymous' : ($incident['student_name'] ?? 'Anonymous');
                $incident['officer_name'] = $incident['officer_name'] ?? ($incident['assigned_role_name'] ?? 'Unassigned');
                $incident['status'] = strtolower(str_replace(' ', '_', $incident['status']));
            }
            return $incidents;
        } catch (PDOException $e) {
            error_log("Get all incidents error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update incident status
     * @param int $incident_id Incident ID
     * @param string $status New status
     * @param string $officer_id Officer ID
     * @return bool True on success, false on failure
     */
    public function updateStatus($incident_id, $status, $officer_id = null) {
        if ($this->db === null) {
            return false;
        }
        try {
            // Convert status to match ENUM format
            $statusEnum = ucwords(str_replace('_', ' ', $status));
            if (!in_array($statusEnum, ['Pending', 'In Progress', 'Resolved', 'Deleted'])) {
                $statusEnum = 'Pending';
            }
            
            $stmt = $this->db->prepare("
                UPDATE incidents 
                SET status = :status,
                    assigned_user_id = COALESCE(:officer_id, assigned_user_id)
                WHERE incident_id = :incident_id
            ");
            
            return $stmt->execute([
                'incident_id' => $incident_id,
                'status' => $statusEnum,
                'officer_id' => $officer_id
            ]);
        } catch (PDOException $e) {
            error_log("Update incident status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign officer to incident
     * @param int $incident_id Incident ID
     * @param string $officer_id Officer ID
     * @return bool True on success, false on failure
     */
    public function assignOfficer($incident_id, $officer_id) {
        if ($this->db === null) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("
                UPDATE incidents 
                SET assigned_user_id = :officer_id
                WHERE incident_id = :incident_id
            ");
            
            return $stmt->execute([
                'incident_id' => $incident_id,
                'officer_id' => $officer_id
            ]);
        } catch (PDOException $e) {
            error_log("Assign officer error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a comment to an incident
     * @param int $incident_id Incident ID
     * @param string $user_id User ID
     * @param string $comment Comment text
     * @return bool True on success, false on failure
     */
    public function addComment($incident_id, $user_id, $comment) {
        if ($this->db === null) return false;
        try {
            $stmt = $this->db->prepare("INSERT INTO incident_comments (incident_id, user_id, comment) VALUES (:incident_id, :user_id, :comment)");
            return $stmt->execute([
                'incident_id' => (int)$incident_id,
                'user_id' => $user_id,
                'comment' => trim($comment)
            ]);
        } catch (PDOException $e) {
            error_log("Add comment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all comments for an incident
     * @param int $incident_id Incident ID
     * @return array List of comments
     */
    public function getComments($incident_id) {
        if ($this->db === null) return [];
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.full_name, r.role_name
                FROM incident_comments c
                JOIN users u ON c.user_id = u.user_id
                JOIN roles r ON u.role_id = r.role_id
                WHERE c.incident_id = :incident_id
                ORDER BY c.created_at ASC
            ");
            $stmt->execute(['incident_id' => (int)$incident_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get comments error: " . $e->getMessage());
            return [];
        }
    }
}
