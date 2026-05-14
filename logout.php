<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

ob_start();
session_start();

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Determine the base path for redirecting to index.php
$current_path = $_SERVER['PHP_SELF'];
$project_root = strpos($current_path, '/logout.php') !== false ? substr($current_path, 0, strpos($current_path, '/logout.php') + 1) : '/';

// Redirect to login page at the project root
header("Location: " . $project_root . "index.php");
exit();
