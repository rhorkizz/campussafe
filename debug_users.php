<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Database connection failed.\n");
    }

    echo "--- Users ---\n";
    $stmt = $pdo->query("SELECT u.user_id, u.full_name, u.role_id, r.role_name 
                         FROM users u
                         LEFT JOIN roles r ON u.role_id = r.role_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $u) {
        echo "ID: {$u['user_id']} | Name: {$u['full_name']} | Role: {$u['role_id']} ({$u['role_name']})\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
