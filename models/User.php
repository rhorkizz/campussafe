<?php
/**
 * User Model
 * Handles all database operations related to users
 */

class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get user by user ID
     * @param string $user_id User ID
     * @return array|null User data or null if not found
     */
    public function getUserById($user_id) {
        if ($this->db === null) {
            error_log("Database connection is null in User::getUserById");
            return null;
        }
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.role_name, d.department_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.user_id = :user_id AND u.status = 'active'
            ");
            $stmt->execute(['user_id' => $user_id]);
            $user = $stmt->fetch();
            if ($user) {
                // Map role_name to role for backward compatibility
                $user['role'] = $this->mapRoleNameToRole($user['role_name']);
                $user['name'] = $user['full_name']; // Map full_name to name
            }
            return $user;
        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Map role_name from roles table to role string used in code
     * @param string $role_name Role name from database
     * @return string Role string
     */
    private function mapRoleNameToRole($role_name) {
        $mapping = [
            'Student' => 'student',
            'Campus Officer' => 'officer',
            'Hostel Officer' => 'officer',
            'Admin' => 'admin'
        ];
        return $mapping[$role_name] ?? 'student';
    }

    /**
     * Get user by email
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function getUserByEmail($email) {
        // Email is derived from user_id (no email column in schema)
        // Students: <index_number>@upsamail.edu.gh
        // Lecturers/Officers: <firstname.lastname>@upsamail.edu.gh
        return null;
    }

    /**
     * Derive UPSA email from user_id and full_name
     * - Students:  10296473   → 10296473@upsamail.edu.gh
     * - Staff/Lecturers: user_id stored as name-slug → firstname.lastname@upsamail.edu.gh
     * The safest universal rule: if user_id is all digits → student email,
     * otherwise use full_name converted to firstname.lastname.
     *
     * @param string $user_id  The user's login ID
     * @param string $full_name The user's full name
     * @return string  Derived email address
     */
    public static function deriveEmail($user_id, $full_name) {
        $domain = 'upsamail.edu.gh';
        // Pure numeric student index number
        if (ctype_digit(trim($user_id))) {
            return trim($user_id) . '@' . $domain;
        }
        // Non-numeric: lecturer / officer – build lastname.firstname from full_name
        // Strip honorifics like Mr., Mrs., Dr., Prof.
        $cleaned = preg_replace('/^(Mr\.?|Mrs\.?|Ms\.?|Dr\.?|Prof\.?)\s+/i', '', trim($full_name));
        $parts   = preg_split('/\s+/', $cleaned);
        $first   = strtolower($parts[0] ?? 'user');
        $last    = strtolower(end($parts));
        if ($first === $last) {
            return $first . '@' . $domain;
        }
        // Format: lastname.firstname  (matches the hint shown on the forgot-password page)
        return $last . '.' . $first . '@' . $domain;
    }

    /**
     * Find a user whose derived email matches the given address.
     * @param string $email
     * @return array|null
     */
    public function getUserByDerivedEmail($email) {
        if ($this->db === null) return null;
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.role_name, d.department_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.status = 'active'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            foreach ($users as $user) {
                $derived = self::deriveEmail($user['user_id'], $user['full_name']);
                if (strtolower($derived) === strtolower(trim($email))) {
                    $user['role'] = $this->mapRoleNameToRole($user['role_name']);
                    $user['name'] = $user['full_name'];
                    $user['email'] = $derived;
                    return $user;
                }
            }
            return null;
        } catch (PDOException $e) {
            error_log('getUserByDerivedEmail error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a password-reset token for a user (expires in 1 hour)
     * @param string $user_id
     * @return string|false  The plain token on success, false on failure
     */
    public function createResetToken($user_id) {
        if ($this->db === null) return false;
        try {
            // Invalidate any existing unused tokens for this user
            $stmt = $this->db->prepare(
                "UPDATE password_reset_tokens SET used = 1 WHERE user_id = :uid AND used = 0"
            );
            $stmt->execute(['uid' => $user_id]);

            $token     = bin2hex(random_bytes(32)); // 64-char hex
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $stmt = $this->db->prepare(
                "INSERT INTO password_reset_tokens (user_id, token, expires_at)
                 VALUES (:uid, :token, :expires)"
            );
            $stmt->execute(['uid' => $user_id, 'token' => $token, 'expires' => $expiresAt]);
            return $token;
        } catch (PDOException $e) {
            error_log('createResetToken error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve a valid (unused, not-expired) reset token row
     * @param string $token
     * @return array|null
     */
    public function getValidResetToken($token) {
        if ($this->db === null) return null;
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM password_reset_tokens
                 WHERE token = :token AND used = 0 AND expires_at > NOW()"
            );
            $stmt->execute(['token' => $token]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log('getValidResetToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark a token as used after successful password reset
     * @param string $token
     * @return bool
     */
    public function markTokenUsed($token) {
        if ($this->db === null) return false;
        try {
            $stmt = $this->db->prepare(
                "UPDATE password_reset_tokens SET used = 1 WHERE token = :token"
            );
            return $stmt->execute(['token' => $token]);
        } catch (PDOException $e) {
            error_log('markTokenUsed error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the password for a user and clear must_change_password flag
     * @param string $user_id
     * @param string $newPassword  Plain-text password
     * @return bool
     */
    public function updatePassword($user_id, $newPassword) {
        if ($this->db === null) return false;
        try {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt   = $this->db->prepare(
                "UPDATE users SET password = :pw, must_change_password = 0 WHERE user_id = :uid"
            );
            $stmt->execute(['pw' => $hashed, 'uid' => $user_id]);
            // rowCount > 0 means a row was actually updated (not just execute() returning true)
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('updatePassword error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new user
     * @param array $userData user_id, full_name, role_id, department_id (optional), password
     * @return bool True on success, false on failure
     */
    public function createUser($userData) {
        if ($this->db === null) return false;
        try {
            $role_id = $userData['role_id'] ?? $this->mapRoleToRoleId($userData['role'] ?? 'student');
            $password = $userData['password'] ?? '';
            $info = password_get_info($password);
            $hashed = ($info['algo'] !== 0 && $info['algo'] !== null) ? $password : password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                INSERT INTO users (user_id, full_name, role_id, department_id, password, must_change_password, status) 
                VALUES (:user_id, :full_name, :role_id, :department_id, :password, 1, 'active')
                ON DUPLICATE KEY UPDATE 
                    full_name = VALUES(full_name),
                    role_id = VALUES(role_id),
                    department_id = VALUES(department_id),
                    password = VALUES(password),
                    must_change_password = 1,
                    status = 'active'
            ");
            return $stmt->execute([
                'user_id' => trim($userData['user_id']),
                'full_name' => trim($userData['full_name']),
                'role_id' => $role_id,
                'department_id' => !empty($userData['department_id']) ? $userData['department_id'] : null,
                'password' => $hashed
            ]);
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users (optionally filter by role_id)
     * @param int|null $role_id Filter by role_id, null = all
     * @param bool $includeInactive Include inactive users
     * @return array
     */
    public function getAllUsers($role_id = null, $includeInactive = false) {
        if ($this->db === null) return [];
        try {
            $sql = "SELECT u.*, r.role_name, d.department_name FROM users u
                    LEFT JOIN roles r ON u.role_id = r.role_id
                    LEFT JOIN departments d ON u.department_id = d.department_id
                    WHERE 1=1";
            $params = [];
            if ($role_id !== null) {
                $sql .= " AND u.role_id = :role_id";
                $params['role_id'] = $role_id;
            }
            if (!$includeInactive) {
                $sql .= " AND u.status = 'active'";
            }
            $sql .= " ORDER BY r.role_name, u.user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Deactivate (soft delete) a user
     * @param string $user_id
     * @return bool
     */
    public function deactivateUser($user_id) {
        if ($this->db === null) return false;
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = 'inactive' WHERE user_id = :user_id AND role_id != 4");
            return $stmt->execute(['user_id' => $user_id]);
        } catch (PDOException $e) {
            error_log("Deactivate user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user_id already exists
     */
    public function userExists($user_id) {
        if ($this->db === null) return false;
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE user_id = :user_id AND status = 'active'");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get all users by role
     * @param string $role User role
     * @return array Array of users
     */
    public function getUsersByRole($role) {
        if ($this->db === null) return [];
        try {
            // Map role to role_id
            $roleId = $this->mapRoleToRoleId($role);
            $stmt = $this->db->prepare("
                SELECT u.*, r.role_name, d.department_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.role_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.role_id = :role_id AND u.status = 'active'
            ");
            $stmt->execute(['role_id' => $roleId]);
            $users = $stmt->fetchAll();
            // Map fields for backward compatibility
            foreach ($users as &$user) {
                $user['role'] = $this->mapRoleNameToRole($user['role_name']);
                $user['name'] = $user['full_name'];
            }
            return $users;
        } catch (PDOException $e) {
            error_log("Get users by role error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Map role string to role_id
     * @param string $role Role string
     * @return int Role ID
     */
    private function mapRoleToRoleId($role) {
        $mapping = [
            'student' => 1,
            'officer' => 2,
            'campus_officer' => 2,
            'hostel_officer' => 3,
            'admin' => 4
        ];
        return $mapping[strtolower($role)] ?? 1;
    }

    /**
     * Update user information
     * @param string $user_id User ID
     * @param array $userData Updated user data
     * @return bool True on success, false on failure
     */
    public function updateUser($user_id, $userData) {
        try {
            $fields = [];
            $params = ['user_id' => $user_id];

            foreach ($userData as $key => $value) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }
}
