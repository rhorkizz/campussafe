<?php
/**
 * List Databases – CampusSafe uses which server?
 * Connects with the same config as the app, creates campus_incident_system if missing,
 * and lists all DBs. Use this to confirm which phpMyAdmin server to use (MySQL vs MariaDB).
 */

require_once __DIR__ . '/config/db.php';

$port = defined('DB_PORT') ? DB_PORT : '3306';
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusSafe – Which server / phpMyAdmin?</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .box { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        h1 { color: #333; border-bottom: 2px solid #4a90e2; padding-bottom: 8px; margin-top: 0; }
        .ok { color: #28a745; background: #d4edda; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .err { color: #dc3545; background: #f8d7da; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .db-list { background: #f8f9fa; padding: 12px; border-radius: 6px; font-family: monospace; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        a { color: #4a90e2; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Which server does CampusSafe use?</h1>
        <p>CampusSafe connects to <strong><?php echo htmlspecialchars($host); ?>:<?php echo htmlspecialchars($port); ?></strong>
            <?php echo $port === '3307' ? '(MariaDB)' : '(MySQL)'; ?>.
            In <strong>phpMyAdmin</strong>, you have two servers: <strong>MySQL</strong> (3306) and <strong>MariaDB</strong> (3307).
            The database <code>campus_incident_system</code> appears only on the server that matches this port.
        </p>

<?php
try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);

    echo "<div class='ok'>✓ Connected to <strong>" . htmlspecialchars($host) . ":" . htmlspecialchars($port) . "</strong> ";
    echo "(" . ($port === '3307' ? 'MariaDB' : 'MySQL') . ").</div>";

    // Create DB if missing
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    $dbs = array_filter($dbs, function ($d) { return !in_array($d, ['information_schema','mysql','performance_schema','sys'], true); });

    echo "<p><strong>Databases on this server (excluding system):</strong></p>";
    echo "<div class='db-list'>" . implode('<br>', array_map('htmlspecialchars', $dbs)) . "</div>";
    echo "<div class='info'>";
    if (in_array($dbname, $dbs, true)) {
        echo "✓ <code>$dbname</code> exists here. In phpMyAdmin, select the <strong>" . ($port === '3307' ? 'MariaDB' : 'MySQL') . "</strong> server to see it.";
    } else {
        echo "✓ <code>$dbname</code> was created. Refresh phpMyAdmin and choose the <strong>" . ($port === '3307' ? 'MariaDB' : 'MySQL') . "</strong> server.";
    }
    echo "</div>";
} catch (PDOException $e) {
    echo "<div class='err'>✗ Connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<div class='info'><strong>Access denied?</strong> Your MySQL/MariaDB <code>root</code> user needs a password. Set it in <code>config/db.php</code>: <code>define('DB_PASS', 'your_password');</code> — use the same password you use to log into phpMyAdmin as root.</div>";
    } else {
        echo "<div class='info'>Check that " . ($port === '3307' ? 'MariaDB' : 'MySQL') . " is running and <code>config/db.php</code> uses the correct port (3306 = MySQL, 3307 = MariaDB).</div>";
    }
}
?>
        <p style="margin-top: 24px;">
            <a href="test_connection.php">Connection test</a> ·
            <a href="setup_database.php">Setup DB &amp; schema</a> ·
            <a href="index.php">Login</a>
        </p>
    </div>
</body>
</html>
