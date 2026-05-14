<?php
/**
 * Database Setup Script
 * Creates the database and runs the schema
 * 
 * Usage: Open this file in your browser or run: php setup_database.php
 */

require_once __DIR__ . '/config/db.php';

// Database configuration
$host = DB_HOST;
$port = defined('DB_PORT') ? DB_PORT : '3306';
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>CampusSafe - Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #004085;
            background: #cce5ff;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #4a90e2;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>CampusSafe Database Setup</h1>";

try {
    // Step 1: Test MySQL connection (without database)
    echo "<div class='step'><strong>Step 1:</strong> Testing MySQL connection...</div>";
    
    $dsn_no_db = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn_no_db, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<div class='success'>✓ Successfully connected to MySQL server</div>";
    
    // Step 2: Create database if it doesn't exist
    echo "<div class='step'><strong>Step 2:</strong> Creating database '<code>$dbname</code>'...</div>";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✓ Database '<code>$dbname</code>' is ready</div>";
    
    // Step 3: Select the database
    $pdo->exec("USE `$dbname`");
    echo "<div class='success'>✓ Connected to database '<code>$dbname</code>'</div>";
    
    // Step 4: Read and execute schema file
    echo "<div class='step'><strong>Step 3:</strong> Running database schema...</div>";
    
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Remove the CREATE DATABASE and USE statements since we already handled that
    $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
    $schema = preg_replace('/USE.*?;/i', '', $schema);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $executed = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Ignore errors for IF NOT EXISTS statements
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "<div class='error'>Warning: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
    }
    
    echo "<div class='success'>✓ Executed $executed SQL statements</div>";
    
    // Ensure incidents.attachment_path exists (for existing DBs)
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM incidents LIKE 'attachment_path'")->fetchAll();
        if (count($cols) === 0) {
            $pdo->exec("ALTER TABLE incidents ADD COLUMN attachment_path VARCHAR(255) DEFAULT NULL AFTER status");
            echo "<div class='success'>✓ Added <code>attachment_path</code> column to <code>incidents</code></div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Warning: Could not add attachment_path: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Step 5: Verify tables were created
    echo "<div class='step'><strong>Step 4:</strong> Verifying database structure...</div>";
    
    $tables = ['roles', 'departments', 'users', 'incident_categories', 'incident_routing', 'incidents', 'incident_comments'];
    $foundTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $foundTables[] = $table;
        }
    }
    
    echo "<div class='success'>✓ Found " . count($foundTables) . " tables: " . implode(', ', $foundTables) . "</div>";
    
    // Step 6: Check for sample data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM incidents");
    $incidentCount = $stmt->fetch()['count'];
    
    echo "<div class='step'><strong>Step 5:</strong> Checking sample data...</div>";
    echo "<div class='success'>✓ Found $userCount users and $incidentCount incidents in database</div>";
    
    // Final success message
    $serverName = ($port === '3307') ? 'MariaDB' : 'MySQL';
    echo "<div class='info' style='margin-top: 30px;'>
        <h2>✓ Database Setup Complete!</h2>
        <p>The database has been successfully created and populated with initial data.</p>
        <p><strong>Don't see it in phpMyAdmin?</strong> WAMP has <strong>MySQL (3306)</strong> and <strong>MariaDB (3307)</strong> as separate servers. CampusSafe uses port <strong>$port</strong> (<strong>$serverName</strong>). In phpMyAdmin, pick the <strong>$serverName</strong> server from the dropdown—the database appears only there. <a href='list_databases.php'>List databases</a></p>
        <p><strong>Next steps:</strong></p>
        <ul>
            <li>Make sure WAMP MySQL/MariaDB is running (green icon in system tray)</li>
            <li>You can now <a href='index.php'>go to the login page</a></li>
            <li>Test accounts are available (see schema.sql for details)</li>
        </ul>
        <p><strong>Default test accounts:</strong></p>
        <ul>
            <li>Admin: <code>ADMIN001</code> / <code>admin123</code></li>
            <li>Student: <code>UPSA001</code> / <code>2001-05-14</code></li>
            <li>Officer: <code>STAFF001</code> / <code>staff123</code></li>
        </ul>
    </div>";
    
} catch (PDOException $e) {
    $accessDenied = (strpos($e->getMessage(), 'Access denied') !== false);
    echo "<div class='error'>
        <h2>✗ Database Setup Failed</h2>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    if ($accessDenied) {
        echo "<p><strong>Access denied?</strong> Set your MySQL/MariaDB root password in <code>config/db.php</code>: <code>define('DB_PASS', 'your_password');</code> — use the same password you use in phpMyAdmin for root.</p>";
    } else {
        echo "<p><strong>Possible solutions:</strong></p>
        <ul>
            <li>Make sure WAMP MySQL service is running (check the WAMP icon in system tray - it should be green)</li>
            <li>Verify MySQL credentials in <code>config/db.php</code></li>
            <li>Check if MySQL port (usually 3306) is not blocked by firewall</li>
            <li>Try restarting WAMP services</li>
        </ul>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>
        <h2>✗ Setup Error</h2>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}

echo "</div></body></html>";
?>
