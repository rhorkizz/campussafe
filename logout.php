<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

ob_start();
session_start();

// Clear all session variables
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/functions.php';

header('Location: ' . app_url('index.php'));
exit();
