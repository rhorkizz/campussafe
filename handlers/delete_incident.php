<?php
/**
 * Delete Incident Handler
 * Allows admins to delete incidents
 */

session_start();
require_once __DIR__ . '/../helpers/functions.php';
requireLogin();
requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session token']);
    exit;
}

$incident_id = $_POST['incident_id'] ?? null;

if (!$incident_id || !is_numeric($incident_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident ID']);
    exit;
}

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../models/Incident.php';
    
    $db = getDBConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $incidentModel = new Incident($db);
    
    // Soft delete - mark as deleted instead of removing from database
    $stmt = $db->prepare("UPDATE incidents SET status = 'Deleted' WHERE incident_id = ?");
    $result = $stmt->execute([$incident_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Incident deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete incident']);
    }
    
} catch (Exception $e) {
    error_log("Delete incident error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
