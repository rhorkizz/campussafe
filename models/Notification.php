<?php
/**
 * Notification Model
 * Handles database operations for user notifications
 */

class Notification {
    private $db;
    private $table = 'notifications';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Create a new notification
     * @param string $user_id Recipient User ID
     * @param int $incident_id Related Incident ID
     * @param string $message Notification message
     * @return bool Success status
     */
    public function create($user_id, $incident_id, $message) {
        try {
            $stmt = $this->db->prepare("INSERT INTO $this->table (user_id, incident_id, message) VALUES (?, ?, ?)");
            return $stmt->execute([$user_id, $incident_id, $message]);
        } catch (PDOException $e) {
            error_log("Create notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notifications for a user
     * @param string $user_id User ID
     * @return array List of notifications
     */
    public function getUnreadByUser($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get unread notifications error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read
     * @param int $id Notification ID
     * @return bool Success status
     */
    public function markAsRead($id) {
        try {
            $stmt = $this->db->prepare("UPDATE $this->table SET is_read = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Mark notification as read error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     * @param string $user_id User ID
     * @return bool Success status
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->db->prepare("UPDATE $this->table SET is_read = 1 WHERE user_id = ?");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            return false;
        }
    }
}
