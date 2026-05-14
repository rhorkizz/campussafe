<?php
/**
 * Helper Functions File
 * Contains utility functions used throughout the application
 */

// Ensure the browser always uses UTF-8 for correct punctuation/symbol display
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

/**
 * Sanitize input data
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if ($data === null) return '';
    return trim((string)$data);
}

/**
 * Redirect to a specific page.
 * Resolves app-relative paths (e.g. views/..., ../index.php) via app_url() so hosting at domain root or in a subfolder works.
 */
function redirect($url) {
    $url = trim((string) $url);
    if ($url === '') {
        header('Location: ' . app_url('index.php'));
        exit();
    }
    if (preg_match('#^https?://#i', $url)) {
        header('Location: ' . $url);
        exit();
    }
    if (strpos($url, '/') === 0) {
        header('Location: ' . $url);
        exit();
    }
    while (strpos($url, '../') === 0) {
        $url = substr($url, 3);
    }
    $url = ltrim($url, '/');
    header('Location: ' . app_url($url));
    exit();
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user has specific role
 * @param string $role Role to check
 * @return bool True if user has the role, false otherwise
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require login - redirects to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(app_url('index.php'));
    }

    if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
        if (strpos($_SERVER['PHP_SELF'], 'change_password.php') === false &&
            strpos($_SERVER['PHP_SELF'], 'logout.php') === false) {
            redirect(app_url('views/change_password.php'));
        }
    }
}

/**
 * Require specific role - redirects to dashboard if role doesn't match
 * @param string $role Required role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        // Redirect to appropriate dashboard based on user's actual role
        $userRole = $_SESSION['user_role'] ?? 'student';
        redirect(app_url(getDashboardPath($userRole)));
    }
}

/**
 * Get dashboard path based on user role
 * @param string $role User role
 * @return string Dashboard path
 */
function getDashboardPath($role) {
    switch ($role) {
        case 'admin':
            return 'views/admin/dashboard.php';
        case 'officer':
            return 'views/officer/dashboard.php';
        case 'student':
        default:
            return 'views/student/dashboard.php';
    }
}

/**
 * Absolute path from site root for links and assets (works in subfolders and at domain root).
 * @param string $path e.g. index.php, views/incident_details.php?id=1
 */
function app_url($path) {
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../config/db.php';
    }
    $full = (string) $path;
    $qpos = strpos($full, '?');
    $query = '';
    if ($qpos !== false) {
        $query = substr($full, $qpos);
        $full = substr($full, 0, $qpos);
    }
    $pathOnly = '/' . ltrim($full, '/');
    $base = BASE_URL;
    if ($base === '' || $base === '/') {
        return $pathOnly . $query;
    }
    return rtrim((string) $base, '/') . $pathOnly . $query;
}

/**
 * URL path prefix without trailing slash (empty string = app at domain root). For JS fetch/redirect.
 */
function app_base() {
    if (!defined('BASE_URL')) {
        require_once __DIR__ . '/../config/db.php';
    }
    return rtrim((string) BASE_URL, '/');
}

/**
 * Display flash message
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 * @return array|null Array with message and type, or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check action rate limit to prevent spam
 * @param string $action The action identifier
 * @param int $limit Maximum allowed instances
 * @param int $timeframe Timeframe in seconds
 * @return bool True if allowed, false if limit exceeded
 */
function checkRateLimit($action, $limit = 3, $timeframe = 300) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    if (!isset($_SESSION['rate_limits'][$action])) {
        $_SESSION['rate_limits'][$action] = [];
    }
    
    $now = time();
    $timestamps = $_SESSION['rate_limits'][$action];
    
    // Remove timestamps outside the timeframe
    $valid_timestamps = array_filter($timestamps, function($ts) use ($now, $timeframe) {
        return ($now - $ts) <= $timeframe;
    });
    
    if (count($valid_timestamps) >= $limit) {
        return false;
    }
    
    // Add current timestamp
    $valid_timestamps[] = $now;
    $_SESSION['rate_limits'][$action] = $valid_timestamps;
    
    return true;
}

/**
 * Generate a CSRF token and store it in the session
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token submitted in a form
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

