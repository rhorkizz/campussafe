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
 * Redirect to a specific page
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
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
        redirect(BASE_URL . '/index.php');
    }
    
    // Check if user is forced to change password
    if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
        // Don't redirect if we're already on the change_password.php or logout.php page
        if (strpos($_SERVER['PHP_SELF'], 'change_password.php') === false && 
            strpos($_SERVER['PHP_SELF'], 'logout.php') === false) {
            
            // Determine the path to change_password.php based on current location
            // If in views/role/dashboard.php, need to go up 2 levels then into views/
            // If in views/admin/dashboard.php, self is /views/admin/dashboard.php
            $depth = substr_count($_SERVER['PHP_SELF'], '/');
            // This is a bit brittle, but consistent with existing redirect usage.
            // Let's use a simpler check: if we are in views/ something, we need views/change_password.php
            // but the include structure is complex.
            
            // Based on getDashboardPath returning 'views/...', 
            // the root index.php redirects to 'views/...'
            // So from index, it's 'views/change_password.php'
            
            // Standard approach: if we aren't in 'views/', go to 'views/change_password.php'
            // If we ARE in 'views/subdirectory', go to '../change_password.php'
            // If we ARE in 'views/' (root of views), go to 'change_password.php'
            
            $path_to_views = '';
            if (strpos($_SERVER['PHP_SELF'], '/views/admin/') !== false || 
                strpos($_SERVER['PHP_SELF'], '/views/officer/') !== false ||
                strpos($_SERVER['PHP_SELF'], '/views/student/') !== false) {
                $path_to_views = '../change_password.php';
            } elseif (strpos($_SERVER['PHP_SELF'], '/views/') !== false) {
                $path_to_views = 'change_password.php';
            } else {
                $path_to_views = 'views/change_password.php';
            }
            
            redirect($path_to_views);
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
        redirect(BASE_URL . '/' . getDashboardPath($userRole));
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

