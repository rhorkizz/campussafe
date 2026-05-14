<?php
/**
 * Department Model
 * Handles all database operations related to departments
 */

class Department {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all departments
     * @return array Array of departments
     */
    public function getAllDepartments() {
        if ($this->db === null) {
            return [];
        }
        try {
            $stmt = $this->db->prepare("SELECT * FROM departments ORDER BY department_name ASC");
            $stmt->execute();
            $departments = $stmt->fetchAll();
            // Map field names for backward compatibility
            foreach ($departments as &$dept) {
                $dept['id'] = $dept['department_id'];
                $dept['name'] = $dept['department_name'];
            }
            return $departments;
        } catch (PDOException $e) {
            error_log("Get all departments error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get department by ID
     * @param int $department_id Department ID
     * @return array|null Department data or null if not found
     */
    public function getDepartmentById($department_id) {
        if ($this->db === null) {
            return null;
        }
        try {
            $stmt = $this->db->prepare("SELECT * FROM departments WHERE department_id = :department_id");
            $stmt->execute(['department_id' => $department_id]);
            $dept = $stmt->fetch();
            if ($dept) {
                $dept['id'] = $dept['department_id'];
                $dept['name'] = $dept['department_name'];
            }
            return $dept;
        } catch (PDOException $e) {
            error_log("Get department by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new department
     * @param array $departmentData Department data
     * @return bool|int Department ID on success, false on failure
     */
    public function createDepartment($departmentData) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO departments (name, description, created_at) 
                VALUES (:name, :description, :created_at)
            ");
            
            $result = $stmt->execute([
                'name' => $departmentData['name'],
                'description' => $departmentData['description'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create department error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update department
     * @param int $department_id Department ID
     * @param array $departmentData Updated department data
     * @return bool True on success, false on failure
     */
    public function updateDepartment($department_id, $departmentData) {
        try {
            $fields = [];
            $params = ['department_id' => $department_id];

            foreach ($departmentData as $key => $value) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }

            $sql = "UPDATE departments SET " . implode(', ', $fields) . " WHERE id = :department_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update department error: " . $e->getMessage());
            return false;
        }
    }
}
