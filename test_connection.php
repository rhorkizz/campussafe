<?php
/**
 * Database Connection Test Script
 * Tests MySQL connection and provides diagnostic information
 */

require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusSafe - Connection Test</title>
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
        .config {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CampusSafe Connection Test</h1>
        
        <div class="info">
            <h3>Current Configuration:</h3>
            <div class="config">
                <strong>Host:</strong> <?php echo htmlspecialchars(DB_HOST); ?><br>
                <strong>Port:</strong> <?php echo htmlspecialchars(defined('DB_PORT') ? DB_PORT : '3306'); ?> (3306 = MySQL, 3307 = MariaDB in WAMP)<br>
                <strong>Database:</strong> <?php echo htmlspecialchars(DB_NAME); ?><br>
                <strong>User:</strong> <?php echo htmlspecialchars(DB_USER); ?><br>
                <strong>Password:</strong> <?php echo empty(DB_PASS) ? '(empty)' : '***'; ?><br>
                <strong>Charset:</strong> <?php echo htmlspecialchars(DB_CHARSET); ?>
            </div>
        </div>

        <?php
        // Test 1: Check if MySQL extension is loaded
        echo "<h3>Test 1: PHP MySQL Extension</h3>";
        if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
            echo "<div class='success'>✓ PDO and PDO_MySQL extensions are loaded</div>";
        } else {
            echo "<div class='error'>✗ PDO or PDO_MySQL extension is not loaded. Please enable them in php.ini</div>";
        }

        // Test 2: Try connecting to MySQL server (without database)
        echo "<h3>Test 2: MySQL Server Connection</h3>";
        try {
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $dsn_no_db = "mysql:host=" . DB_HOST . ";port=" . $port . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn_no_db, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            echo "<div class='success'>✓ Connected to server at " . htmlspecialchars(DB_HOST) . ":" . htmlspecialchars($port) . " <strong>(" . ($port === '3307' ? 'MariaDB' : 'MySQL') . ")</strong></div>";
            
            // Get MySQL version
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            echo "<div class='info'>MySQL Version: " . htmlspecialchars($version) . "</div>";
            
            $serverConnected = true;
        } catch (PDOException $e) {
            echo "<div class='error'>✗ Failed to connect to MySQL server</div>";
            echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                echo "<div class='info'><strong>Access denied?</strong> Your MySQL/MariaDB <code>root</code> user requires a password. Edit <code>config/db.php</code> and set <code>DB_PASS</code> to the same password you use for root in phpMyAdmin.</div>";
            } else {
                echo "<div class='info'><strong>Solutions:</strong><ul>
                <li>Make sure WAMP MySQL service is running (green icon in system tray)</li>
                <li>Check if MySQL is listening on the correct port (default: 3306)</li>
                <li>Verify the host, username, and password in config/db.php</li>
                <li>Try restarting WAMP services</li>
            </ul></div>";
            }
            $serverConnected = false;
        }

        // Test 3: Check if database exists
        if (isset($serverConnected) && $serverConnected) {
            echo "<h3>Test 3: Database Existence</h3>";
            try {
                $databases = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'")->fetchAll();
                if (count($databases) > 0) {
                    echo "<div class='success'>✓ Database '<code>" . htmlspecialchars(DB_NAME) . "</code>' exists</div>";
                    $dbExists = true;
                } else {
                    echo "<div class='error'>✗ Database '<code>" . htmlspecialchars(DB_NAME) . "</code>' does not exist</div>";
                    echo "<div class='info'>You need to run <a href='setup_database.php'><code>setup_database.php</code></a> to create the database</div>";
                    $dbExists = false;
                }
            } catch (PDOException $e) {
                echo "<div class='error'>✗ Error checking database: " . htmlspecialchars($e->getMessage()) . "</div>";
                $dbExists = false;
            }
        }

        // Test 4: Try connecting to the specific database
        if (isset($dbExists) && $dbExists) {
            echo "<h3>Test 4: Database Connection</h3>";
            try {
                $port = defined('DB_PORT') ? DB_PORT : '3306';
                $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo_db = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                echo "<div class='success'>✓ Successfully connected to database '<code>" . htmlspecialchars(DB_NAME) . "</code>'</div>";
                
                // Check tables
                $tables = $pdo_db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                if (count($tables) > 0) {
                    echo "<div class='success'>✓ Found " . count($tables) . " tables: " . implode(', ', $tables) . "</div>";
                } else {
                    echo "<div class='error'>✗ Database exists but has no tables. Run <a href='setup_database.php'><code>setup_database.php</code></a> to create tables.</div>";
                }
                
            } catch (PDOException $e) {
                echo "<div class='error'>✗ Failed to connect to database</div>";
                echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        // Summary
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<h3>Summary</h3>";
        if (isset($serverConnected) && $serverConnected && isset($dbExists) && $dbExists) {
            echo "<div class='success'><strong>✓ All tests passed! Your database is ready to use.</strong></div>";
            echo "<div class='info' style='margin-top:15px'><strong>Can't see the DB in phpMyAdmin?</strong> WAMP has <strong>MySQL (3306)</strong> and <strong>MariaDB (3307)</strong> as separate servers. CampusSafe uses port <strong>$port</strong>. In phpMyAdmin, choose the server that matches (MySQL or MariaDB) from the dropdown—the database appears only there. <a href='list_databases.php'>List databases</a></div>";
            echo "<p><a href='index.php'>Go to Login Page</a></p>";
        } else {
            echo "<div class='error'><strong>✗ Some tests failed. Please fix the issues above.</strong></div>";
            echo "<p><a href='setup_database.php'>Run Database Setup</a> | <a href='list_databases.php'>List databases</a> | <a href='index.php'>Go to Login Page</a></p>";
        }
        echo "</div>";
        ?>
    </div>
</body>
</html>
