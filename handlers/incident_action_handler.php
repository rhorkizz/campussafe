<?php
/**
 * Incident Action Handler
 * Processes status updates and comments from the incident details page
 */

session_start();
require_once __DIR__ . '/../helpers/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

// 1. Verify CSRF Token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage("Invalid or expired session token. Please try again.", "error");
    redirect($_SERVER['HTTP_REFERER'] ?? '../index.php');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Incident.php';

$db = getDBConnection();
if (!$db) {
    setFlashMessage("Database connection failed.", "error");
    redirect($_SERVER['HTTP_REFERER'] ?? '../index.php');
}

$incidentModel = new Incident($db);
$action = $_POST['action'] ?? '';
$incident_id = $_POST['incident_id'] ?? null;

if (!$incident_id) {
    setFlashMessage("Invalid incident ID.", "error");
    redirect('../index.php');
}

switch ($action) {
    case 'update_status':
        // Only Admins and Officers can update status
        if (!hasRole('admin') && !hasRole('officer')) {
            setFlashMessage("Access denied.", "error");
            break;
        }

        $new_status = $_POST['status'] ?? '';
        $new_category = $_POST['category_id'] ?? null;

        // Perform status update
        $ok = $incidentModel->updateStatus($incident_id, $new_status, $_SESSION['user_id']);
        
        // Optional: Update category if changed
        if ($new_category) {
            $stmt = $db->prepare("UPDATE incidents SET category_id = ? WHERE incident_id = ?");
            $stmt->execute([$cat_id = (int)$new_category, $inc_id = (int)$incident_id]);
        }

        if ($ok) {
            setFlashMessage("Incident updated successfully.", "success");
        } else {
            setFlashMessage("Failed to update incident.", "error");
        }
        break;

    case 'add_comment':
        $comment_text = $_POST['comment'] ?? '';
        if (empty(trim($comment_text))) {
            setFlashMessage("Comment cannot be empty.", "warning");
            break;
        }

        $ok = $incidentModel->addComment($incident_id, $_SESSION['user_id'], $comment_text);
        if ($ok) {
            setFlashMessage("Comment posted.", "success");
        } else {
            setFlashMessage("Failed to post comment.", "error");
        }
        break;

    default:
        setFlashMessage("Invalid action requested.", "error");
        break;
}

// Redirect back to the details page
redirect("../views/incident_details.php?id=" . urlencode($incident_id));
