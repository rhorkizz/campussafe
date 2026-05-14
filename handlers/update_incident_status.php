<?php
/**
 * Update Incident Status Handler
 * Called via AJAX or direct link from Officer Dashboard
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../controllers/OfficerController.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}

// Check role
if ($_SESSION['user_role'] !== 'officer' && $_SESSION['user_role'] !== 'admin') {
    die('Access denied');
}

// Get parameters
$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Validate parameters
if ($incident_id <= 0 || empty($status)) {
    // If called via AJAX, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    // Otherwise redirect back
    setFlashMessage('Invalid parameters', 'error');
    redirect('../views/officer/dashboard.php');
}

// Update status
$controller = new OfficerController();
$result = $controller->updateIncidentStatus($incident_id, $status);

// Return response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
    redirect('../views/officer/dashboard.php');
}
