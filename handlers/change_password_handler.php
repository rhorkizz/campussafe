<?php
/**
 * Change Password Handler
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/change_password.php');
}

if (!isset($_SESSION['user_id'])) {
    redirect('../index.php');
}

$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($new_password) || empty($confirm_password)) {
    setFlashMessage("Please fill in all fields.", "error");
    redirect('../views/change_password.php');
}

if ($new_password !== $confirm_password) {
    setFlashMessage("Passwords do not match.", "error");
    redirect('../views/change_password.php');
}

if (strlen($new_password) < 6) {
    setFlashMessage("Password must be at least 6 characters long.", "error");
    redirect('../views/change_password.php');
}

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception("Database connection failed.");
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $user_id = $_SESSION['user_id'];

    $stmt = $db->prepare("UPDATE users SET password = :password, must_change_password = 0 WHERE user_id = :user_id");
    $result = $stmt->execute([
        'password' => $hashed_password,
        'user_id' => $user_id
    ]);

    if ($result) {
        // Update session or logic if needed
        $_SESSION['must_change_password'] = 0;
        setFlashMessage("Password updated successfully. You can now access your dashboard.", "success");
        
        // Redirect based on role
        $role = $_SESSION['user_role'] ?? 'student';
        redirect('../' . getDashboardPath($role));
    } else {
        throw new Exception("Failed to update password.");
    }

} catch (Exception $e) {
    setFlashMessage("Error: " . $e->getMessage(), "error");
    redirect('../views/change_password.php');
}
