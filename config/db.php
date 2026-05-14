<?php
/**
 * Database Configuration File
 * Handles PDO connection to MySQL database
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');  // MySQL default. WAMP MariaDB uses 3307 – use that if you use MariaDB in phpMyAdmin
define('DB_NAME', 'campus_incident_system');
define('DB_USER', 'root');
define('DB_PASS', '');  // Set your MySQL/MariaDB root password here. Leave '' only if root has no password. "Access denied (using password: NO)" = you need to set this.
define('DB_CHARSET', 'utf8mb4');

// Base URL for absolute redirects (no trailing slash).
// This is derived automatically from the server path so it works from any sub-directory.
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Walk up to find the CampusSafe project root (the folder containing index.php)
    $projectRoot = rtrim(str_replace(
        ['/views/admin', '/views/officer', '/views/student', '/views', '/handlers', '/controllers', '/models', '/helpers', '/config'],
        '',
        rtrim($scriptDir, '/')
    ), '/');
    define('BASE_URL', $projectRoot);
}

// When true: login is disabled; "Enter without database (demo)" lets you explore the app. Set to false when DB is ready.
define('BYPASS_LOGIN_DEMO', false);

/**
 * Get database connection using PDO
 * @return PDO|null Returns PDO connection or null on failure
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error (in production, log to file instead of displaying)
        $errorMsg = "Database connection failed: " . $e->getMessage();
        error_log($errorMsg);
        
        // Provide helpful error message for common issues
        if (strpos($e->getMessage(), "Unknown database") !== false) {
            error_log("ERROR: Database '" . DB_NAME . "' does not exist. Please create it first.");
        } elseif (strpos($e->getMessage(), "Access denied") !== false) {
            error_log("ERROR: Access denied. Check username and password in config/db.php");
        }
        
        return null;
    }
}
