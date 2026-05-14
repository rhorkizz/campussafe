<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo = getDBConnection();
    if (!$pdo) die("Failed to connect\n");
    
    // Add 'Deleted' to ENUM
    $sql = "ALTER TABLE incidents MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Resolved', 'Deleted') DEFAULT 'Pending'";
    $pdo->exec($sql);
    echo "Status ENUM updated successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
