<?php
/**
 * Database Configuration File
 * Handles PDO connection to MySQL database
 *
 * On shared hosting, set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (and optionally BASE_URL)
 * in the host panel or .htaccess SetEnv — avoid committing production secrets.
 */

function _dbEnv(string $key, string $default): string {
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : $default;
}

define('DB_HOST', _dbEnv('DB_HOST', 'localhost'));
define('DB_PORT', _dbEnv('DB_PORT', '3306'));  // WAMP MariaDB often uses 3307 locally
define('DB_NAME', _dbEnv('DB_NAME', 'campus_incident_system'));
define('DB_USER', _dbEnv('DB_USER', 'root'));
define('DB_PASS', _dbEnv('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

if (!defined('BASE_URL')) {
    $baseEnv = getenv('BASE_URL');
    if ($baseEnv !== false && $baseEnv !== '') {
        define('BASE_URL', rtrim($baseEnv, '/'));
    } else {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $projectRoot = rtrim(str_replace(
            ['/views/admin', '/views/officer', '/views/student', '/views', '/handlers', '/controllers', '/models', '/helpers', '/config'],
            '',
            rtrim($scriptDir, '/')
        ), '/');
        define('BASE_URL', $projectRoot);
    }
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
