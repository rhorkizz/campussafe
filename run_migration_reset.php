<?php
/**
 * One-time migration: creates the password_reset_tokens table.
 * Open this file in your browser once, then DELETE it for security.
 * URL: http://localhost/CampusSafe/run_migration_reset.php
 */

require_once __DIR__ . '/config/db.php';

$db = getDBConnection();
if (!$db) {
    die('<b style="color:red">❌ Could not connect to database. Check config/db.php</b>');
}

$sql = "
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     VARCHAR(20) NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME    NOT NULL,
    used        BOOLEAN     DEFAULT FALSE,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token   (token),
    INDEX idx_user_id (user_id)
);
";

try {
    $db->exec($sql);
    echo '<p style="font-family:monospace;color:green;font-size:1.1rem;">
            ✅ <strong>password_reset_tokens</strong> table created (or already exists).<br><br>
            ⚠️  <strong>Delete this file</strong> (run_migration_reset.php) from the server now.
          </p>';
} catch (PDOException $e) {
    echo '<p style="font-family:monospace;color:red;">❌ Migration failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
